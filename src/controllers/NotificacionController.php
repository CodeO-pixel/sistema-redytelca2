<?php
// controllers/NotificacionController.php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Repositories\NotificacionStaffRepository;
use App\Validators\NotificacionValidator;
use App\Exceptions\NotFoundException;

/**
 * Controller de la bandeja de notificaciones del staff.
 *
 * Gate: `Auth::requireStaffAuth` (NO `requirePageAccess`). La campana
 * vive en el header de cualquier página, no en una página del sidebar
 * del catálogo. El usuario autenticado ve SOLO sus propias notificaciones.
 */
final class NotificacionController extends BaseController
{
    /** @var NotificacionStaffRepository */
    private NotificacionStaffRepository $notifRepo;

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->notifRepo = new NotificacionStaffRepository($pdo);
    }

    /**
     * GET /api_notificaciones.php
     * GET /api_notificaciones.php?count_only=1
     *
     * @param array $staffSession
     * @return void
     */
    public function index(array $staffSession): void
    {
        $usuarioId = (int) ($staffSession['id_usuario'] ?? 0);
        if ($usuarioId <= 0) {
            $this->render(['status' => 'error', 'message' => 'Sesión inválida.'], 401);
            return;
        }

        // Modo "solo conteo" para el badge de la campana.
        if (!empty($_GET['count_only'])) {
            $noLeidas = $this->notifRepo->countNoLeidasByUsuarioId($usuarioId);
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

        $resultado = $this->notifRepo->paginateByUsuarioId($usuarioId, $page, $perPage);
        $total = (int) $resultado['total'];
        $pages = max(1, (int) ceil($total / $perPage));
        $noLeidas = $this->notifRepo->countNoLeidasByUsuarioId($usuarioId);

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
     * PUT /api_notificaciones.php
     * Body: { id_notificacion: N } o { mark_all: true }
     *
     * @param array $staffSession
     * @return void
     */
    public function update(array $staffSession): void
    {
        $usuarioId = (int) ($staffSession['id_usuario'] ?? 0);
        if ($usuarioId <= 0) {
            $this->render(['status' => 'error', 'message' => 'Sesión inválida.'], 401);
            return;
        }

        $input = $this->input();

        if (!empty($input['mark_all'])) {
            $count = $this->notifRepo->markAllAsReadByUsuarioId($usuarioId);
            $this->render([
                'status' => 'success',
                'message' => $count . ' notificación(es) marcada(s) como leída(s)',
                'marked' => $count,
            ], 200);
            return;
        }

        $validated = NotificacionValidator::validateUpdate($input);
        $id = (int) $validated['id_notificacion'];

        if (!$this->notifRepo->markAsRead($id, $usuarioId)) {
            throw new NotFoundException('Notificación no encontrada.');
        }

        $this->render(['status' => 'success', 'message' => 'Notificación marcada como leída'], 200);
    }
}