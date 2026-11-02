<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Servicio.
 */
final class ServicioRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'servicios';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene todos los servicios con la dirección resuelta, plan_nombre,
     * nap_codigo, nombres, apellidos, cédula y equipo asignado (si lo tiene).
     *
     * El LEFT JOIN con `equipos` usa `estado_inventario = 'asignado'` para
     * traer el equipo activo del servicio. Si un servicio tuviera más de un
     * equipo asignado (no debería, pero el esquema no lo impide con UNIQUE),
     * se trae el de menor id (el primero en asignarse).
     *
     * Fix B3.13 — bug 2 (servicio no mostraba su equipo).
     *
     * @return array
     */
    public function findAll(): array
    {
        $sql = "SELECT s.*,
                       ds.calle AS direccion_texto,
                       ds.latitud,
                       ds.longitud,
                       p.nombre AS plan_nombre,
                       p.precio_mensual AS plan_precio,
                       na.codigo AS nap_codigo,
                       c.nombre AS nombres,
                       c.apellido AS apellidos,
                       c.cedula AS cedula,
                       eq.id AS equipo_id,
                       eq.tipo AS equipo_tipo,
                       eq.marca AS equipo_marca,
                       eq.modelo AS equipo_modelo,
                       eq.direccion_mac AS equipo_mac,
                       eq.estado_inventario AS equipo_estado
                FROM {$this->table} s
                LEFT JOIN direccion_servicio ds ON ds.id = s.direccion_id
                INNER JOIN clientes c ON c.id = s.cliente_id
                INNER JOIN planes p ON p.id = s.plan_id
                LEFT JOIN naps na ON na.id = s.nap_id
                LEFT JOIN equipos eq ON eq.servicio_id = s.id AND eq.estado_inventario = 'asignado'
                ORDER BY s.id ASC";
        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los servicios de un cliente con la dirección resuelta,
     * plan_nombre, nap_codigo, nombres, apellidos, cédula y equipo
     * asignado (si lo tiene).
     *
     * @param int $clienteId
     * @return array
     */
    public function findByClienteId(int $clienteId): array
    {
        $sql = "SELECT s.*,
                       ds.calle AS direccion_texto,
                       ds.latitud,
                       ds.longitud,
                       p.nombre AS plan_nombre,
                       p.precio_mensual AS plan_precio,
                       na.codigo AS nap_codigo,
                       c.nombre AS nombres,
                       c.apellido AS apellidos,
                       c.cedula AS cedula,
                       eq.id AS equipo_id,
                       eq.tipo AS equipo_tipo,
                       eq.marca AS equipo_marca,
                       eq.modelo AS equipo_modelo,
                       eq.direccion_mac AS equipo_mac,
                       eq.estado_inventario AS equipo_estado
                FROM {$this->table} s
                LEFT JOIN direccion_servicio ds ON ds.id = s.direccion_id
                INNER JOIN clientes c ON c.id = s.cliente_id
                INNER JOIN planes p ON p.id = s.plan_id
                LEFT JOIN naps na ON na.id = s.nap_id
                LEFT JOIN equipos eq ON eq.servicio_id = s.id AND eq.estado_inventario = 'asignado'
                WHERE s.cliente_id = ?
                ORDER BY s.id ASC";
        $stmt = $this->prepareAndExecute($sql, [$clienteId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lista paginada de servicios del cliente con datos enriquecidos
     * para el portal: plan, NAP, OLT, nodo, dirección y equipo asignado.
     *
     * Solo expone los campos públicos. NO incluye `cliente_id` ni
     * `nombres`/`apellidos`/`cedula` (el cliente ya se conoce a sí mismo).
     * El portal nunca debe ver datos administrativos ni PII ajena.
     *
     * A2-B.4.a — endpoint GET api_portal_servicios.php.
     *
     * @param int $clienteId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function paginateByClienteId(int $clienteId, int $limit, int $offset): array
    {
        $sql = "SELECT s.id,
                       s.alias,
                       s.estado_comercial,
                       s.creado_en,
                       s.actualizado_en,
                       ds.calle AS direccion_texto,
                       ds.latitud,
                       ds.longitud,
                       p.nombre AS plan_nombre,
                       p.precio_mensual AS plan_precio,
                       na.codigo AS nap_codigo,
                       o.codigo AS olt_codigo,
                       nd.nombre AS nodo_nombre,
                       eq.id AS equipo_id,
                       eq.tipo AS equipo_tipo,
                       eq.marca AS equipo_marca,
                       eq.modelo AS equipo_modelo,
                       eq.direccion_mac AS equipo_mac,
                       eq.estado_inventario AS equipo_estado
                FROM {$this->table} s
                LEFT JOIN direccion_servicio ds ON ds.id = s.direccion_id
                INNER JOIN planes p ON p.id = s.plan_id
                LEFT JOIN naps na ON na.id = s.nap_id
                LEFT JOIN olts o ON o.id = na.olt_id
                LEFT JOIN nodos nd ON nd.id = o.nodo_id
                LEFT JOIN equipos eq ON eq.servicio_id = s.id AND eq.estado_inventario = 'asignado'
                WHERE s.cliente_id = ?
                ORDER BY s.id ASC
                LIMIT ? OFFSET ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(1, $clienteId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve un servicio del cliente (con datos enriquecidos para el
     * portal), o `null` si no existe o no pertenece a ese cliente.
     *
     * El filtro `AND s.cliente_id = ?` es la defensa BOLA/IDOR
     * (OWASP API Security Top 10 2023, API1:2023). Si el servicio
     * existe pero es de otro cliente, retorna `null`. El controller
     * lo convierte en 404 (no 403, para no filtrar existencia).
     *
     * A2-B.4.a — endpoint GET api_portal_servicios.php?id=N.
     *
     * @param int $id
     * @param int $clienteId
     * @return array|null
     */
    public function findByIdAndClienteId(int $id, int $clienteId): ?array
    {
        $sql = "SELECT s.id,
                       s.alias,
                       s.estado_comercial,
                       s.creado_en,
                       s.actualizado_en,
                       ds.calle AS direccion_texto,
                       ds.latitud,
                       ds.longitud,
                       p.nombre AS plan_nombre,
                       p.precio_mensual AS plan_precio,
                       na.codigo AS nap_codigo,
                       o.codigo AS olt_codigo,
                       nd.nombre AS nodo_nombre,
                       eq.id AS equipo_id,
                       eq.tipo AS equipo_tipo,
                       eq.marca AS equipo_marca,
                       eq.modelo AS equipo_modelo,
                       eq.direccion_mac AS equipo_mac,
                       eq.estado_inventario AS equipo_estado
                FROM {$this->table} s
                LEFT JOIN direccion_servicio ds ON ds.id = s.direccion_id
                INNER JOIN planes p ON p.id = s.plan_id
                LEFT JOIN naps na ON na.id = s.nap_id
                LEFT JOIN olts o ON o.id = na.olt_id
                LEFT JOIN nodos nd ON nd.id = o.nodo_id
                LEFT JOIN equipos eq ON eq.servicio_id = s.id AND eq.estado_inventario = 'asignado'
                WHERE s.id = ? AND s.cliente_id = ?
                LIMIT 1";

        $stmt = $this->prepareAndExecute($sql, [$id, $clienteId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Obtiene los servicios filtrados por estado comercial.
     *
     * @param string $estado
     * @return array
     */
    public function findByEstadoComercial(string $estado): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE estado_comercial = ?";
        $stmt = $this->prepareAndExecute($sql, [$estado]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los servicios asociados a un plan.
     *
     * @param int $planId
     * @return array
     */
    public function findByPlanId(int $planId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE plan_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$planId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta el total de servicios de un cliente.
     *
     * @param int $clienteId
     * @return int
     */
    public function countByClienteId(int $clienteId): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE cliente_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$clienteId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Cuenta el total de servicios asociados a un plan.
     *
     * @param int $planId
     * @return int
     */
    public function countByPlanId(int $planId): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE plan_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$planId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Cuenta los servicios activos (no retirados) asociados a una NAP.
     *
     * @param int $napId
     * @return int
     */
    public function countByNapId(int $napId): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE nap_id = ? AND estado_comercial != 'retirado'";
        $stmt = $this->prepareAndExecute($sql, [$napId]);
        return (int) $stmt->fetchColumn();
    }
}