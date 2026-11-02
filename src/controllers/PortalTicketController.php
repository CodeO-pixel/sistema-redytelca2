<?php
// controllers/PortalTicketController.php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\PortalTicketService;
use App\Validators\PortalTicketValidator;

/**
 * Controlador del portal de cliente para tickets (A2-B.5).
 *
 * A2-B.5.a — GET lista / GET detalle / POST crear.
 * A2-B.5.b — POST responder (respuestas del cliente).
 *
 * Recibe `$clienteSession` desde `Auth::requireClientAuth($pdo)` en el
 * thin file. El `cliente_id` y `credencial_id` vienen de la sesión,
 * NUNCA de la query string ni del body.
 */
final class PortalTicketController extends BaseController
{
    /** @var PortalTicketService */
    private PortalTicketService $portalTicketService;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->portalTicketService = new PortalTicketService($pdo);
    }

    /**
     * GET api_portal_tickets.php
     *
     * Modo lista (default): pagina los tickets del cliente con filtros
     * opcionales. Modo detalle (`?id=N`): devuelve el ticket + hilo de
     * respuestas, o 404 si no pertenece al cliente.
     *
     * @param array $clienteSession {id_credencial, id_cliente}
     * @return void
     */
    public function index(array $clienteSession): void
    {
        $clienteId = (int) ($clienteSession['id_cliente'] ?? 0);
        if ($clienteId <= 0) {
            $this->render(['status' => 'error', 'message' => 'Sesión inválida.'], 401);
            return;
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id > 0) {
            $resultado = $this->portalTicketService->detalle($id, $clienteId);
            if ($resultado === null) {
                $this->render(['status' => 'error', 'message' => 'Ticket no encontrado.'], 404);
                return;
            }
            $this->render([
                'status' => 'success',
                'ticket' => $resultado['ticket'],
                'respuestas' => $resultado['respuestas'],
            ], 200);
            return;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;
        if ($perPage < 1) {
            $perPage = 20;
        } elseif ($perPage > 100) {
            $perPage = 100;
        }

        $filtros = [
            'estado' => isset($_GET['estado']) ? trim((string) $_GET['estado']) : '',
            'prioridad' => isset($_GET['prioridad']) ? trim((string) $_GET['prioridad']) : '',
        ];

        $r = $this->portalTicketService->listar($clienteId, $page, $perPage, $filtros);

        $this->render([
            'status' => 'success',
            'tickets' => $r['items'],
            'total' => $r['total'],
            'page' => $r['page'],
            'pages' => $r['pages'],
            'per_page' => $r['per_page'],
        ], 200);
    }

    /**
     * POST api_portal_tickets.php (JSON)
     *
     * Valida el body, fuerza `origen='portal_cliente'` y
     * `usuario_creador_id=NULL` (vía service), y delega en
     * `PortalTicketService::crear`.
     *
     * @param array $clienteSession
     * @return void
     */
    public function store(array $clienteSession): void
    {
        $clienteId = (int) ($clienteSession['id_cliente'] ?? 0);
        if ($clienteId <= 0) {
            $this->render(['status' => 'error', 'message' => 'Sesión inválida.'], 401);
            return;
        }

        $input = $this->input();
        $validated = PortalTicketValidator::validateCreate($input);

        $ticketId = $this->portalTicketService->crear($clienteId, $validated);

        $this->render([
            'status' => 'success',
            'message' => 'Ticket creado correctamente.',
            'ticket_id' => $ticketId,
        ], 200);
    }

    /**
     * POST api_portal_ticket_respuestas.php (JSON)
     *
     * Valida el body, fuerza `tipo_autor='cliente'`, `credencial_id` de
     * la sesión, `usuario_id=NULL` (vía service), y delega en
     * `PortalTicketService::responder`. `eventoTicketRespondidoPorCliente`
     * (A2-B.5.b) se dispara dentro de la transacción.
     *
     * @param array $clienteSession
     * @return void
     */
    public function responder(array $clienteSession): void
    {
        $clienteId = (int) ($clienteSession['id_cliente'] ?? 0);
        $idCredencial = (int) ($clienteSession['id_credencial'] ?? 0);

        if ($clienteId <= 0 || $idCredencial <= 0) {
            $this->render(['status' => 'error', 'message' => 'Sesión inválida.'], 401);
            return;
        }

        $input = $this->input();
        $validated = PortalTicketValidator::validateResponder($input);

        $idRespuesta = $this->portalTicketService->responder(
            (int) $validated['id_ticket'],
            $clienteId,
            $idCredencial,
            (string) $validated['mensaje']
        );

        $this->render([
            'status' => 'success',
            'message' => 'Respuesta enviada correctamente.',
            'id_respuesta' => $idRespuesta,
        ], 200);
    }
}