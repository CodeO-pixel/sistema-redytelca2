<?php
// controllers/PortalServicioController.php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\PortalServicioService;

/**
 * Controlador del portal de cliente para servicios.
 *
 * A2-B.4.a — backend del endpoint GET api_portal_servicios.php.
 *
 * Recibe `$clienteSession` desde `Auth::requireClientAuth($pdo)` en
 * el thin file. El `cliente_id` viene de la sesión, NUNCA de la
 * query string ni del body. El controller es el único que lee `$_GET`.
 *
 * Contrato:
 *   - GET api_portal_servicios.php?page=1&per_page=20
 *       → 200 + {status, servicios:[...], total, page, pages, per_page}
 *   - GET api_portal_servicios.php?id=N
 *       → 200 + {status, servicio:{...}} | 404 si no pertenece
 *   - Cualquier otro verbo → 405 (lo maneja el thin).
 *
 * Sin catch de excepciones de dominio: el thin file hace
 * `try/catch → Response::fromException`.
 */
final class PortalServicioController extends BaseController
{
    /** @var PortalServicioService */
    private PortalServicioService $portalServicioService;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->portalServicioService = new PortalServicioService($pdo);
    }

    /**
     * GET api_portal_servicios.php
     *
     * Modo lista (default): pagina los servicios del cliente.
     * Modo detalle (`?id=N`): devuelve un servicio del cliente o 404.
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
            $servicio = $this->portalServicioService->detalle($id, $clienteId);
            if ($servicio === null) {
                $this->render(['status' => 'error', 'message' => 'Servicio no encontrado.'], 404);
                return;
            }
            $this->render(['status' => 'success', 'servicio' => $servicio], 200);
            return;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;
        if ($perPage < 1) {
            $perPage = 20;
        } elseif ($perPage > 100) {
            $perPage = 100;
        }

        $r = $this->portalServicioService->listar($clienteId, $page, $perPage);

        $this->render([
            'status' => 'success',
            'servicios' => $r['items'],
            'total' => $r['total'],
            'page' => $r['page'],
            'pages' => $r['pages'],
            'per_page' => $r['per_page'],
        ], 200);
    }
}