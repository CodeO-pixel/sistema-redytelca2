<?php
// services/PortalFacturaService.php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\FacturaLineaRepository;
use App\Repositories\FacturaRepository;
use App\Repositories\PagoRepository;

/**
 * Servicio del portal de cliente para facturas.
 *
 * A2-B.4.b — backend del endpoint GET api_portal_facturas.php.
 *
 * Patrón espejo de PortalServicioService (A2-B.4.a) y
 * NotificacionPortalController (A2-B.1): el `cliente_id` entra al
 * service SOLO desde `$clienteSession['id_cliente']`. El service no
 * toca `$_GET`/`$_POST`.
 *
 * Defensa BOLA/IDOR (OWASP API Security Top 10 2023, API1:2023):
 *   - `listar($clienteId, ...)` filtra `WHERE s.cliente_id = ?`.
 *   - `detalle($id, $clienteId)` filtra `WHERE f.id = ? AND s.cliente_id = ?`.
 *   - Si la factura existe pero es de otro cliente, retorna `null`.
 *     El controller lo convierte en 404.
 *
 * `total_pagado` y `saldo` se calculan en batch con
 * `PagoRepository::sumValidadosByFacturaIds` (1 query, no N+1).
 * OWASP ASVS 5.0 §V1.4.3 / §V11.1.4 prohíben queries dentro de loops
 * (CWE-400). El patrón es el mismo que `ReporteService::calcularKPIs`
 * post-fix A2-B.4.c.
 *
 * `saldo = max(0, monto - total_pagado)`. Patrón Stripe
 * `invoice.amount_remaining`. Nunca negativo, aunque el cliente haya
 * pagado de más (el excedente va a `pagos_a_cuenta` en el KPI, no a
 * la factura).
 */
final class PortalFacturaService extends BaseService
{
    /** @var FacturaRepository */
    private FacturaRepository $facturaRepo;

    /** @var PagoRepository */
    private PagoRepository $pagoRepo;

    /** @var FacturaLineaRepository */
    private FacturaLineaRepository $facturaLineaRepo;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->facturaRepo = new FacturaRepository($pdo);
        $this->pagoRepo = new PagoRepository($pdo);
        $this->facturaLineaRepo = new FacturaLineaRepository($pdo);
    }

    /**
     * Lista paginada de facturas del cliente con `total_pagado` y `saldo`
     * calculados en batch.
     *
     * @param int $clienteId ID del cliente autenticado (de la sesión).
     * @param int $page Número de página (1-based).
     * @param int $perPage Registros por página.
     * @param array $filtros Filtros opcionales: estado, periodo.
     * @return array{items: array, total: int, page: int, pages: int, per_page: int}
     */
    public function listar(int $clienteId, int $page, int $perPage, array $filtros = []): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $filtrosLimpios = $this->limpiarFiltros($filtros);

        $items = $this->facturaRepo->paginateByClienteId($clienteId, $perPage, $offset, $filtrosLimpios);
        $total = $this->facturaRepo->countByClienteId($clienteId, $filtrosLimpios);
        $pages = max(1, (int) ceil($total / $perPage));

        $items = $this->enriquecerConPagos($items);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }

    /**
     * Detalle de una factura del cliente + sus líneas.
     *
     * Retorna `null` si la factura no existe o no pertenece al cliente.
     * El controller lo convierte en 404.
     *
     * @param int $id
     * @param int $clienteId
     * @return array{factura: array, lineas: array}|null
     */
    public function detalle(int $id, int $clienteId): ?array
    {
        $factura = $this->facturaRepo->findByIdAndClienteId($id, $clienteId);
        if ($factura === null) {
            return null;
        }

        $lineas = $this->facturaLineaRepo->findByFacturaId($id);

        $totalPagado = 0.0;
        $totales = $this->pagoRepo->sumValidadosByFacturaIds([$id]);
        if (isset($totales[$id])) {
            $totalPagado = $totales[$id];
        }

        $monto = (float) ($factura['monto'] ?? 0);
        $factura['total_pagado'] = $totalPagado;
        $factura['saldo'] = max(0.0, $monto - $totalPagado);
        $factura['total_lineas'] = count($lineas);

        return ['factura' => $factura, 'lineas' => $lineas];
    }

    /**
     * Enriquece un lote de facturas con `total_pagado` y `saldo` en UNA
     * sola query batch (evita N+1).
     *
     * `saldo = max(0, monto - total_pagado)`. Nunca negativo.
     *
     * @param array $facturas Filas de `facturaRepo->paginateByClienteId`.
     * @return array Mismo array, cada fila con `total_pagado` y `saldo`.
     */
    private function enriquecerConPagos(array $facturas): array
    {
        if (empty($facturas)) {
            return $facturas;
        }

        $ids = array_map(fn($f) => (int) $f['id'], $facturas);
        $totales = $this->pagoRepo->sumValidadosByFacturaIds($ids);

        foreach ($facturas as &$f) {
            $id = (int) $f['id'];
            $totalPagado = $totales[$id] ?? 0.0;
            $f['total_pagado'] = $totalPagado;
            $f['saldo'] = max(0.0, (float) ($f['monto'] ?? 0) - $totalPagado);
        }
        unset($f);

        return $facturas;
    }

    /**
     * Limpia los filtros opcionales. Si un filtro no matchea el formato
     * o el ENUM, se descarta (el cliente recibe el listado sin ese
     * filtro, no un 422). Patrón Salesforce REST API: filtros inválidos
     * en query string se ignoran, no rompen.
     *
     * @param array $filtros
     * @return array
     */
    private function limpiarFiltros(array $filtros): array
    {
        $limpios = [];

        if (isset($filtros['estado'])) {
            $estadosValidos = ['pendiente', 'pagada', 'parcial', 'vencida', 'anulada'];
            if (in_array((string) $filtros['estado'], $estadosValidos, true)) {
                $limpios['estado'] = $filtros['estado'];
            }
        }

        if (isset($filtros['periodo'])) {
            if (preg_match('/^\d{4}-\d{2}$/', (string) $filtros['periodo'])) {
                $limpios['periodo'] = $filtros['periodo'];
            }
        }

        return $limpios;
    }
}