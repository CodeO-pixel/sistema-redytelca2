<?php
// services/PortalServicioService.php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\ServicioRepository;

/**
 * Servicio del portal de cliente para servicios.
 *
 * A2-B.4.a — backend del endpoint GET api_portal_servicios.php.
 *
 * Patrón espejo de PortalAuthService y NotificacionPortalController
 * (A2-B.1 / A2-B.3): el `cliente_id` entra al service SOLO desde
 * `$clienteSession['id_cliente']`. El service no toca `$_GET`/`$_POST`.
 * El controller es el único que lee la query string.
 *
 * Defensa BOLA/IDOR (OWASP API Security Top 10 2023, API1:2023):
 *   - `listar($clienteId, ...)` filtra `WHERE s.cliente_id = ?`.
 *   - `detalle($id, $clienteId)` filtra `WHERE s.id = ? AND s.cliente_id = ?`.
 *   - Si el servicio existe pero es de otro cliente, retorna `null`.
 *     El controller lo convierte en 404 (no 403, para no filtrar
 *     existencia).
 *
 * Sin reglas de negocio: los servicios del cliente son de solo lectura
 * desde el portal. El cliente no crea, edita ni elimina servicios.
 */
final class PortalServicioService extends BaseService
{
    /** @var ServicioRepository */
    private ServicioRepository $servicioRepo;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->servicioRepo = new ServicioRepository($pdo);
    }

    /**
     * Lista paginada de servicios del cliente.
     *
     * `$page` se clampea a >= 1. `$perPage` se clampea a [1, 100].
     * Sigue el mismo patrón que `ClienteController::index` y
     * `NotificacionPortalController::index`.
     *
     * @param int $clienteId ID del cliente autenticado (de la sesión).
     * @param int $page Número de página (1-based).
     * @param int $perPage Registros por página.
     * @return array{items: array, total: int, page: int, pages: int, per_page: int}
     */
    public function listar(int $clienteId, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $items = $this->servicioRepo->paginateByClienteId($clienteId, $perPage, $offset);
        $total = $this->servicioRepo->countByClienteId($clienteId);
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
     * Detalle de un servicio del cliente.
     *
     * Retorna `null` si el servicio no existe o no pertenece al cliente.
     * El controller lo convierte en 404.
     *
     * @param int $id
     * @param int $clienteId
     * @return array|null
     */
    public function detalle(int $id, int $clienteId): ?array
    {
        return $this->servicioRepo->findByIdAndClienteId($id, $clienteId);
    }
}