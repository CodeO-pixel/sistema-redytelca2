<?php
// services/TicketRespuestaService.php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Core\Validator;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\TicketRespuestaRepository;
use App\Repositories\TicketRepository;

/**
 * Servicio para la gestión de respuestas de tickets.
 *
 * A2-B.5.b (2026-11-01):
 *   - crear() sigue siendo la vía del staff (tipo_autor='staff').
 *   - crearRespuestaCliente() es la vía del portal (tipo_autor='cliente',
 *     credencial_id de la sesión, usuario_id=NULL). Dispara el evento
 *     nuevo `ticket_respondido_por_cliente` (notifica a admins).
 *
 * El CHECK `chk_ticket_respuestas_discriminador` garantiza que
 * `tipo_autor='staff'` implica `usuario_id` not null y `credencial_id`
 * null, y viceversa. Los dos métodos hardcodean el discriminador
 * correcto, el caller NO puede pasarlo por body.
 */
final class TicketRespuestaService extends BaseService
{
    /** @var TicketRespuestaRepository */
    private TicketRespuestaRepository $respuestaRepo;

    /** @var TicketRepository */
    private TicketRepository $ticketRepo;

    /** @var NotificacionService */
    private NotificacionService $notificacionService;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->respuestaRepo = new TicketRespuestaRepository($pdo);
        $this->ticketRepo = new TicketRepository($pdo);
        $this->notificacionService = new NotificacionService($pdo);
    }

    /**
     * Obtiene las respuestas de un ticket.
     *
     * @param int $idTicket
     * @return array
     * @throws NotFoundException
     */
    public function listarPorTicket(int $idTicket): array
    {
        if (!$this->ticketRepo->exists($idTicket)) {
            throw new NotFoundException('El ticket especificado no existe.');
        }

        return $this->respuestaRepo->findByTicketId($idTicket);
    }

    /**
     * Crea una nueva respuesta en un ticket (vía staff).
     *
     * Notifica al cliente dueño del ticket vía `eventoTicketRespondido`.
     * Transaccional: si la notificación falla, la respuesta hace rollback.
     *
     * @param array $datos
     * @param int $usuarioId ID del staff autenticado.
     * @return int
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function crear(array $datos, int $usuarioId): int
    {
        $validated = Validator::make($datos)
            ->required('id_ticket', 'ticket')
            ->int('id_ticket', 1)
            ->required('mensaje', 'mensaje')
            ->string('mensaje', 1)
            ->string('adjunto_path', null, 255)
            ->validateOrFail();

        if (!$this->ticketRepo->exists((int) $validated['id_ticket'])) {
            throw new NotFoundException('El ticket especificado no existe.');
        }

        $insertData = [
            'ticket_id' => (int) $validated['id_ticket'],
            'tipo_autor' => 'staff',
            'mensaje' => $validated['mensaje'],
            'adjunto_path' => $validated['adjunto_path'] ?? null,
            'usuario_id' => $usuarioId,
            'credencial_id' => null
        ];

        $closure = function () use ($insertData, $validated, $usuarioId) {
            $idRespuesta = $this->respuestaRepo->create($insertData);

            $ticket = $this->ticketRepo->findById((int) $validated['id_ticket']);
            if ($ticket && !empty($ticket['cliente_id'])) {
                $this->notificacionService->eventoTicketRespondido(
                    (int) $validated['id_ticket'],
                    (int) $ticket['cliente_id'],
                    (string) ($ticket['asunto'] ?? ''),
                    (string) $validated['mensaje'],
                    $usuarioId
                );
            }

            return $idRespuesta;
        };

        if ($this->pdo->inTransaction()) {
            return $closure();
        }
        return $this->transaction($closure);
    }

    /**
     * Crea una nueva respuesta en un ticket (vía cliente del portal).
     *
     * A2-B.5.b — método nuevo. Hardcodea `tipo_autor='cliente'`,
     * `credencial_id=$idCredencial`, `usuario_id=NULL`, `adjunto_path=NULL`.
     * El caller (PortalTicketService::responder) ya validó ownership
     * del ticket y estado no cerrado.
     *
     * Dispara `eventoTicketRespondidoPorCliente` (A2-B.5.b): notifica a
     * todos los admins activos (rol 1). NO notifica al cliente (ya sabe
     * que respondió). NO cambia el estado del ticket (patrón
     * Zendesk/Jira SM/Salesforce/ServiceNow/Splynx: el cliente responde,
     * el staff decide si mueve el estado).
     *
     * Transaccional: si la notificación falla, la respuesta hace rollback.
     *
     * @param int $idTicket
     * @param int $idCredencial ID de la credencial de la sesión del cliente.
     * @param string $mensaje
     * @return int ID de la respuesta creada.
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function crearRespuestaCliente(int $idTicket, int $idCredencial, string $mensaje): int
    {
        $mensaje = trim($mensaje);
        if ($mensaje === '') {
            throw new ValidationException(['mensaje' => 'El mensaje es obligatorio.']);
        }
        if (mb_strlen($mensaje) > 65535) {
            throw new ValidationException(['mensaje' => 'El mensaje no debe exceder 65535 caracteres.']);
        }

        if ($idCredencial <= 0) {
            throw new ValidationException(['id_credencial' => 'Credencial inválida.']);
        }

        if (!$this->ticketRepo->exists($idTicket)) {
            throw new NotFoundException('El ticket especificado no existe.');
        }

        $insertData = [
            'ticket_id' => $idTicket,
            'tipo_autor' => 'cliente',
            'mensaje' => $mensaje,
            'adjunto_path' => null,
            'usuario_id' => null,
            'credencial_id' => $idCredencial,
        ];

        $closure = function () use ($insertData, $idTicket) {
            $idRespuesta = $this->respuestaRepo->create($insertData);

            $ticket = $this->ticketRepo->findById($idTicket);
            if ($ticket && !empty($ticket['cliente_id'])) {
                $this->notificacionService->eventoTicketRespondidoPorCliente(
                    $idTicket,
                    (int) $ticket['cliente_id'],
                    (string) ($ticket['asunto'] ?? ''),
                    (string) $insertData['mensaje'],
                    (int) $insertData['credencial_id']
                );
            }

            return $idRespuesta;
        };

        if ($this->pdo->inTransaction()) {
            return $closure();
        }
        return $this->transaction($closure);
    }
}