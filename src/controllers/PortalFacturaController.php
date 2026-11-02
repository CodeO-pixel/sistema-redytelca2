<?php
// controllers/PortalFacturaController.php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\PortalFacturaService;

/**
 * Controlador del portal de cliente para facturas.
 *
 * A2-B.4.b — backend del endpoint GET api_portal_facturas.php.
 *
 * Recibe `$clienteSession` desde `Auth::requireClientAuth($pdo)` en
 * el thin file. El `cliente_id` viene de la sesión, NUNCA de la
 * query string ni del body.
 *
 * Contrato:
 *   - GET api_portal_facturas.php?page=1&per_page=20&estado=pendiente&periodo=2026-10
 *       → 200 + {status, facturas:[...], total, page, pages, per_page}
 *   - GET api_portal_facturas.php?id=N
 *       → 200 + {status, factura:{...}, lineas:[...]}
 *       → 404 si la factura no pertenece al cliente
 *   - Cualquier otro verbo → 405 (lo maneja el thin).
 *
 * Los filtros `estado` y `periodo` se validan en el service. Si no
 * matchean el formato o el ENUM, se ignoran (el cliente recibe el
 * listado sin ese filtro). No se devuelve 422 por un query param
 * inválido. Patrón Salesforce REST API.
 */
final class PortalFacturaController extends BaseController
{
    /** @var PortalFacturaService */
    private PortalFacturaService $portalFacturaService;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->portalFacturaService = new PortalFacturaService($pdo);
    }

    /**
     * GET api_portal_facturas.php
     *
     * Modo lista (default): pagina las facturas del cliente con
     * `total_pagado` y `saldo`. Modo detalle (`?id=N`): devuelve una
     * factura + sus líneas, o 404 si no pertenece al cliente.
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
            $resultado = $this->portalFacturaService->detalle($id, $clienteId);
            if ($resultado === null) {
                $this->render(['status' => 'error', 'message' => 'Factura no encontrada.'], 404);
                return;
            }
            $this->render([
                'status' => 'success',
                'factura' => $resultado['factura'],
                'lineas' => $resultado['lineas'],
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
            'periodo' => isset($_GET['periodo']) ? trim((string) $_GET['periodo']) : '',
        ];

        $r = $this->portalFacturaService->listar($clienteId, $page, $perPage, $filtros);

        $this->render([
            'status' => 'success',
            'facturas' => $r['items'],
            'total' => $r['total'],
            'page' => $r['page'],
            'pages' => $r['pages'],
            'per_page' => $r['per_page'],
        ], 200);
    }
}