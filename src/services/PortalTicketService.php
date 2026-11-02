<?php
// services/PortalTicketService.php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\DomainException;
use App\Exceptions\NotFoundException;
use App\Repositories\TicketRepository;
use App\Repositories\TicketRespuestaRepository;

/**
 * Servicio del portal de cliente para tickets (A2-B.5).
 *
 * A2-B.5.a — endpoints GET lista / GET detalle / POST crear.
 * A2-B.5.b — endpoint POST responder (respuestas del cliente).
 *
 * Patrón espejo de PortalServicioService, PortalFacturaService y
 * PortalPagoService (A2-B.4): el `cliente_id` entra al service SOLO
 * desde `$clienteSession['id_cliente']`. El service no toca
 * `$_GET`/`$_POST`.
 *
 * Defensa BOLA/IDOR (OWASP API Security Top 10 2023, API1:2023):
 *   - `listar($clienteId, ...)` filtra `WHERE t.cliente_id = ?`.
 *   - `detalle($id, $clienteId)` filtra `WHERE t.id = ? AND
 *     t.cliente_id = ?`. Si el ticket existe pero es de otro cliente,
 *     retorna `null`. El controller lo convierte en 404 (no 403, para
 *     no filtrar existencia).
 *   - `responder($idTicket, $clienteId, ...)` valida ownership ANTES
 *     de insertar la respuesta.
 */
final class PortalTicketService extends BaseService
{
    /** @var TicketRepository */
    private TicketRepository $ticketRepo;

    /** @var TicketRespuestaRepository */
    private TicketRespuestaRepository $ticketRespuestaRepo;

    /** @var TicketService */
    private TicketService $ticketService;

    /** @var TicketRespuestaService */
    private TicketRespuestaService $ticketRespuestaService;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->ticketRepo = new TicketRepository($pdo);
        $this->ticketRespuestaRepo = new TicketRespuestaRepository($pdo);
        $this->ticketService = new TicketService($pdo);
        $this->ticketRespuestaService = new TicketRespuestaService($pdo);
    }

    /**
     * Lista paginada de tickets del cliente.
     *
     * @param int $clienteId ID del cliente autenticado (de la sesión).
     * @param int $page Número de página (1-based).
     * @param int $perPage Registros por página.
     * @param array $filtros Filtros opcionales: estado, prioridad.
     * @return array{items: array, total: int, page: int, pages: int, per_page: int}
     */
    public function listar(int $clienteId, int $page, int $perPage, array $filtros = []): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $filtrosLimpios = $this->limpiarFiltros($filtros);

        $items = $this->ticketRepo->paginateByClienteId($clienteId, $perPage, $offset, $filtrosLimpios);
        $total = $this->ticketRepo->countByClienteId($clienteId, $filtrosLimpios);
        $pages = max(1, (int) ceil($total / $perPage));

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }

    /**
     * Detalle de un ticket del cliente + hilo de respuestas.
     *
     * Retorna `null` si el ticket no existe o no pertenece al cliente.
     * El controller lo convierte en 404.
     *
     * @param int $id
     * @param int $clienteId
     * @return array{ticket: array, respuestas: array}|null
     */
    public function detalle(int $id, int $clienteId): ?array
    {
        $ticket = $this->ticketRepo->findByIdAndClienteId($id, $clienteId);
        if ($ticket === null) {
            return null;
        }

        $respuestasRaw = $this->ticketRespuestaRepo->findByTicketIdEnriched($id);
        $respuestas = array_map([$this, 'mapearRespuesta'], $respuestasRaw);

        return [
            'ticket' => $ticket,
            'respuestas' => $respuestas,
        ];
    }

    /**
     * Crea un ticket desde el portal.
     *
     * Fuerza `cliente_id` de la sesión, `origen='portal_cliente'`,
     * `estado='Abierto'`, `usuario_creador_id=NULL`. El ownership de
     * `servicio_id` lo valida `TicketService::crear` (A2-B.5-5).
     *
     * @param int $clienteId ID del cliente autenticado (de la sesión).
     * @param array $datos Datos validados del body.
     * @return int ID del ticket creado.
     * @throws NotFoundException
     * @throws \App\Exceptions\ValidationException
     */
    public function crear(int $clienteId, array $datos): int
    {
        $datos['cliente_id'] = $clienteId;
        $datos['origen'] = 'portal_cliente';
        $datos['estado'] = 'Abierto';
        unset($datos['usuario_creador_id']);

        return $this->ticketService->crear($datos, null);
    }

    /**
     * Responde un ticket desde el portal.
     *
     * A2-B.5.b — método nuevo.
     *
     * Validaciones:
     *   1. Ownership del ticket: `findByIdAndClienteId($idTicket, $clienteId)`.
     *      Si null → 404.
     *   2. Estado no cerrado: `Cerrado` es terminal para el cliente.
     *      Si cerrado → 422 con mensaje que invita a crear uno nuevo.
     *      Patrón Sonar ("closed ticket cannot be reopened; create new").
     *      Deuda PORTAL-TICKET-REABRIR a Fase 7 si se añade `Resuelto`.
     *
     * Delega en `TicketRespuestaService::crearRespuestaCliente`, que
     * hardcodea `tipo_autor='cliente'`, `credencial_id=$idCredencial`,
     * `usuario_id=NULL`. Dispara `ticket_respondido_por_cliente`
     * (notifica a admins). Transaccional.
     *
     * NO cambia el estado del ticket. Patrón Zendesk/Jira SM/
     * Salesforce/ServiceNow/Splynx: el cliente responde, el staff
     * decide si mueve el estado.
     *
     * @param int $idTicket
     * @param int $clienteId ID del cliente autenticado (de la sesión).
     * @param int $idCredencial ID de la credencial de la sesión.
     * @param string $mensaje
     * @return int ID de la respuesta creada.
     * @throws NotFoundException
     * @throws DomainException
     * @throws \App\Exceptions\ValidationException
     */
    public function responder(int $idTicket, int $clienteId, int $idCredencial, string $mensaje): int
    {
        $ticket = $this->ticketRepo->findByIdAndClienteId($idTicket, $clienteId);
        if ($ticket === null) {
            throw new NotFoundException('Ticket no encontrado.');
        }

        if (strtolower((string) ($ticket['estado'] ?? '')) === 'cerrado') {
            throw new DomainException(
                'No puedes responder un ticket cerrado. Si necesitas reabrir la conversación, crea un ticket nuevo.'
            );
        }

        return $this->ticketRespuestaService->crearRespuestaCliente($idTicket, $idCredencial, $mensaje);
    }

    /**
     * Mapea una fila de `ticket_respuestas` enriquecida al contrato del
     * portal.
     *
     * Construye `autor_label` legible:
     *   - staff   → username (o fallback 'Staff #N').
     *   - cliente → nombre + apellido (o fallback 'Cliente #N').
     *   - otro    → 'Sistema'.
     *
     * @param array $r
     * @return array
     */
    private function mapearRespuesta(array $r): array
    {
        $tipo = (string) ($r['tipo_autor'] ?? '');

        if ($tipo === 'staff') {
            $label = !empty($r['autor_username'])
                ? (string) $r['autor_username']
                : 'Staff #' . (string) ($r['usuario_id'] ?? '?');
        } elseif ($tipo === 'cliente') {
            $nombre = trim(
                ((string) ($r['autor_nombre'] ?? '')) . ' ' . ((string) ($r['autor_apellido'] ?? ''))
            );
            $label = $nombre !== ''
                ? $nombre
                : 'Cliente #' . (string) ($r['credencial_id'] ?? '?');
        } else {
            $label = 'Sistema';
        }

        return [
            'id' => (int) ($r['id'] ?? 0),
            'tipo_autor' => $tipo,
            'autor_label' => $label,
            'mensaje' => (string) ($r['mensaje'] ?? ''),
            'adjunto_path' => $r['adjunto_path'] ?? null,
            'creado_en' => $r['creado_en'] ?? null,
        ];
    }

    /**
     * Limpia los filtros opcionales. Si un filtro no matchea el ENUM,
     * se descarta (el cliente recibe el listado sin ese filtro, no un
     * 422). Patrón Salesforce REST API.
     *
     * @param array $filtros
     * @return array
     */
    private function limpiarFiltros(array $filtros): array
    {
        $limpios = [];

        if (isset($filtros['estado'])) {
            $estadosValidos = ['Abierto', 'En proceso', 'Cerrado'];
            if (in_array((string) $filtros['estado'], $estadosValidos, true)) {
                $limpios['estado'] = $filtros['estado'];
            }
        }

        if (isset($filtros['prioridad'])) {
            $prioridadesValidas = ['Alta', 'Media', 'Baja'];
            if (in_array((string) $filtros['prioridad'], $prioridadesValidas, true)) {
                $limpios['prioridad'] = $filtros['prioridad'];
            }
        }

        return $limpios;
    }
}