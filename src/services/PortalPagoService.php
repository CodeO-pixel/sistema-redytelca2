<?php
// services/PortalPagoService.php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\PagoRepository;

/**
 * Servicio del portal de cliente para pagos.
 *
 * A2-B.4.c — backend de los endpoints GET/POST api_portal_pagos.php.
 *
 * Patrón espejo de PortalFacturaService (A2-B.4.b) y
 * NotificacionPortalController (A2-B.1): el `cliente_id` entra al
 * service SOLO desde `$clienteSession['id_cliente']`. El service no
 * toca `$_GET`/`$_POST`/`$_FILES`.
 *
 * Defensa BOLA/IDOR (OWASP API Security Top 10 2023, API1:2023):
 *   - `listar($clienteId, ...)` filtra `WHERE p.cliente_id = ?`.
 *   - `detalle($id, $clienteId)` filtra `WHERE p.id = ? AND p.cliente_id = ?`.
 *   - Si el pago existe pero es de otro cliente, retorna `null`.
 *     El controller lo convierte en 404.
 *   - `crear()` fuerza `cliente_id` de la sesión y delega en
 *     `PagoService::crear()`, que valida ownership de `servicio_id` y
 *     `factura_id`.
 *
 * Sin `update`/`delete`: el cliente NO edita ni elimina pagos. Solo
 * crea y lee. La validación/rechazo es acción del admin.
 */
final class PortalPagoService extends BaseService
{
    /** @var PagoRepository */
    private PagoRepository $pagoRepo;

    /** @var PagoService */
    private PagoService $pagoService;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->pagoRepo = new PagoRepository($pdo);
        $this->pagoService = new PagoService($pdo);
    }

    /**
     * Lista paginada de pagos del cliente.
     *
     * @param int $clienteId ID del cliente autenticado (de la sesión).
     * @param int $page Número de página (1-based).
     * @param int $perPage Registros por página.
     * @param array $filtros Filtros opcionales: estado.
     * @return array{items: array, total: int, page: int, pages: int, per_page: int}
     */
    public function listar(int $clienteId, int $page, int $perPage, array $filtros = []): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $filtrosLimpios = $this->limpiarFiltros($filtros);

        $items = $this->pagoRepo->paginateEnrichedByClienteId($clienteId, $perPage, $offset, $filtrosLimpios);
        $total = $this->pagoRepo->countByClienteId($clienteId, $filtrosLimpios);
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
     * Detalle de un pago del cliente.
     *
     * Retorna `null` si el pago no existe o no pertenece al cliente.
     * El controller lo convierte en 404.
     *
     * @param int $id
     * @param int $clienteId
     * @return array|null
     */
    public function detalle(int $id, int $clienteId): ?array
    {
        return $this->pagoRepo->findEnrichedByIdAndClienteId($id, $clienteId);
    }

    /**
     * Crea un nuevo pago desde el portal.
     *
     * Fuerza `cliente_id` de la sesión, `origen='portal_cliente'` y
     * `comprobante_path` generado por el controller. Delega en
     * `PagoService::crear()`, que valida ownership de `servicio_id` y
     * `factura_id` y dispara `eventoPagoSubido` (dentro de la
     * transacción del cambio principal).
     *
     * `factura_id` vacío (`''` o `null`) se normaliza a `null` para
     * que `PagoService::crear` lo trate como pago a cuenta.
     *
     * @param int $clienteId ID del cliente autenticado (de la sesión).
     * @param array $datos Datos validados del body.
     * @param string $comprobantePath Ruta relativa a `public/`, generada
     *                                por `PortalPagoController`.
     * @return int ID del pago creado.
     * @throws \App\Exceptions\NotFoundException
     * @throws \App\Exceptions\ValidationException
     */
    public function crear(int $clienteId, array $datos, string $comprobantePath): int
    {
        $datos['cliente_id'] = $clienteId;
        $datos['origen'] = 'portal_cliente';
        $datos['comprobante_path'] = $comprobantePath;

        if (!isset($datos['factura_id']) || $datos['factura_id'] === '' || $datos['factura_id'] === null) {
            $datos['factura_id'] = null;
        }

        return $this->pagoService->crear($datos);
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
            $estadosValidos = ['pendiente', 'validado', 'rechazado'];
            if (in_array((string) $filtros['estado'], $estadosValidos, true)) {
                $limpios['estado'] = $filtros['estado'];
            }
        }

        return $limpios;
    }
}