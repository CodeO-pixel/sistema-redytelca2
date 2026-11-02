<?php
// controllers/NotificacionPortalController.php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Repositories\NotificacionRepository;
use App\Validators\NotificacionValidator;
use App\Exceptions\NotFoundException;

/**
 * Controller de la bandeja de notificaciones del cliente (portal).
 *
 * Gate: `Auth::requireClientAuth`. El `cliente_id` se toma SIEMPRE de la
 * sesión, nunca del body (BOLA/IDOR, OWASP API Security Top 10 #1).
 *
 * El portal de cliente (UI) se construye en Fase 5. Este controller
 * existe desde A2-B.1 porque es backend puro. Fase 5 solo lo consume.
 */
final class NotificacionPortalController extends BaseController
{
    /** @var NotificacionRepository */
    private NotificacionRepository $notifRepo;

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->notifRepo = new NotificacionRepository($pdo);
    }

    /**
     * GET /api_portal_notificaciones.php
     * GET /api_portal_notificaciones.php?count_only=1
     *
     * @param array $clienteSession
     * @return void
     */
    public function index(array $clienteSession): void
    {
        $clienteId = (int) ($clienteSession['id_cliente'] ?? 0);
        if ($clienteId <= 0) {
            $this->render(['status' => 'error', 'message' => 'Sesión inválida.'], 401);
            return;
        }

        if (!empty($_GET['count_only'])) {
            $noLeidas = $this->notifRepo->countNoLeidasByClienteId($clienteId);
            $this->render(['status' => 'success', 'no_leidas' => $noLeidas], 200);
            return;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;
        if ($perPage < 1) {
            $perPage = 1;
        } elseif ($perPage > 100) {
            $perPage = 100;
        }

        $resultado = $this->notifRepo->paginateByClienteId($clienteId, $page, $perPage);
        $total = (int) $resultado['total'];
        $pages = max(1, (int) ceil($total / $perPage));
        $noLeidas = $this->notifRepo->countNoLeidasByClienteId($clienteId);

        $this->render([
            'status' => 'success',
            'notificaciones' => $resultado['items'],
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
            'no_leidas' => $noLeidas,
        ], 200);
    }

    /**
     * PUT /api_portal_notificaciones.php
     * Body: { id_notificacion: N } o { mark_all: true }
     *
     * @param array $clienteSession
     * @return void
     */
    public function update(array $clienteSession): void
    {
        $clienteId = (int) ($clienteSession['id_cliente'] ?? 0);
        if ($clienteId <= 0) {
            $this->render(['status' => 'error', 'message' => 'Sesión inválida.'], 401);
            return;
        }

        $input = $this->input();

        if (!empty($input['mark_all'])) {
            $count = $this->notifRepo->markAllAsReadByClienteId($clienteId);
            $this->render([
                'status' => 'success',
                'message' => $count . ' notificación(es) marcada(s) como leída(s)',
                'marked' => $count,
            ], 200);
            return;
        }

        $validated = NotificacionValidator::validateUpdate($input);
        $id = (int) $validated['id_notificacion'];

        if (!$this->notifRepo->markAsRead($id, $clienteId)) {
            // 404, no 403: no filtrar existencia de notificaciones de otros clientes.
            throw new NotFoundException('Notificación no encontrada.');
        }

        $this->render(['status' => 'success', 'message' => 'Notificación marcada como leída'], 200);
    }
}