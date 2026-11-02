<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Pedido.
 *
 * Un pedido puede tener:
 *   - Un equipo RESERVADO (en `pedido_materiales.equipo_id`) mientras
 *     está pendiente. El equipo sigue en `estado_inventario = 'stock'`.
 *   - Un equipo ASIGNADO (en `equipos.servicio_id` con
 *     `estado_inventario = 'asignado'`) una vez completado.
 *
 * El listado expone ambos, con prefijo `equipo_*` para el asignado y
 * `equipo_reservado_*` para el reservado. La UI decide qué mostrar
 * según el estado del pedido.
 */
final class PedidoRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'pedidos';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene todos los pedidos ordenados por fecha de solicitud descendente.
     * Incluye nombres, apellidos y cédula del cliente, el username del técnico
     * asignado, el alias + dirección + coordenadas del servicio, el equipo
     * actualmente asignado al servicio (si lo tiene) y el equipo reservado
     * en el pedido (si lo tiene y aún no se ha completado).
     *
     * @return array
     */
    public function findAllOrderedByFechaSolicitudDesc(): array
    {
        $sql = $this->baseSelectSql() . " ORDER BY pd.fecha_solicitud DESC";
        $stmt = $this->prepareAndExecute($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los pedidos asignados a un técnico, con los mismos joins
     * que findAllOrderedByFechaSolicitudDesc.
     *
     * Fix B3.13 — filtro por técnico. El rol Técnico solo ve sus
     * pedidos asignados. Defensa en backend (NIST SP 800-53 AC-6).
     *
     * @param int $tecnicoId
     * @return array
     */
    public function findAllByTecnicoId(int $tecnicoId): array
    {
        $sql = $this->baseSelectSql() . " WHERE pd.tecnico_asignado_id = ? ORDER BY pd.fecha_solicitud DESC";
        $stmt = $this->prepareAndExecute($sql, [$tecnicoId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * SELECT base compartido por findAllOrderedByFechaSolicitudDesc y
     * findAllByTecnicoId. Centraliza los joins para que no diverjan.
     *
     * @return string
     */
    private function baseSelectSql(): string
    {
        return "SELECT pd.*, 
                       c.nombre AS nombres, 
                       c.apellido AS apellidos, 
                       c.cedula AS cedula,
                       u.username AS tecnico_username,
                       s.alias AS servicio_alias,
                       ds.calle AS servicio_direccion,
                       ds.latitud AS servicio_latitud,
                       ds.longitud AS servicio_longitud,
                       eq.id AS equipo_id,
                       eq.tipo AS equipo_tipo,
                       eq.marca AS equipo_marca,
                       eq.modelo AS equipo_modelo,
                       eq.direccion_mac AS equipo_mac,
                       eq.estado_inventario AS equipo_estado,
                       eq_res.id AS equipo_reservado_id,
                       eq_res.tipo AS equipo_reservado_tipo,
                       eq_res.marca AS equipo_reservado_marca,
                       eq_res.modelo AS equipo_reservado_modelo,
                       eq_res.direccion_mac AS equipo_reservado_mac
                FROM {$this->table} pd
                INNER JOIN clientes c ON c.id = pd.cliente_id
                LEFT JOIN usuarios u ON u.id = pd.tecnico_asignado_id
                LEFT JOIN servicios s ON s.id = pd.servicio_id
                LEFT JOIN direccion_servicio ds ON ds.id = s.direccion_id
                LEFT JOIN equipos eq ON eq.servicio_id = pd.servicio_id AND eq.estado_inventario = 'asignado'
                LEFT JOIN pedido_materiales pm_res ON pm_res.id = (
                    SELECT pm2.id FROM pedido_materiales pm2
                    WHERE pm2.pedido_id = pd.id AND pm2.equipo_id IS NOT NULL
                    ORDER BY pm2.id ASC LIMIT 1
                )
                LEFT JOIN equipos eq_res ON eq_res.id = pm_res.equipo_id";
    }

    /**
     * Obtiene los pedidos de un cliente.
     *
     * @param int $clienteId
     * @return array
     */
    public function findByClienteId(int $clienteId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE cliente_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$clienteId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los pedidos de un servicio.
     *
     * @param int $servicioId
     * @return array
     */
    public function findByServicioId(int $servicioId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE servicio_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$servicioId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los pedidos asignados a un técnico (SELECT plano, sin joins).
     * Para listado enriquecido usar findAllByTecnicoId.
     *
     * @param int $tecnicoId
     * @return array
     */
    public function findByTecnicoAsignadoId(int $tecnicoId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE tecnico_asignado_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$tecnicoId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los pedidos filtrados por estado.
     *
     * @param string $estado
     * @return array
     */
    public function findByEstado(string $estado): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE estado = ?";
        $stmt = $this->prepareAndExecute($sql, [$estado]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los pedidos filtrados por tipo.
     *
     * @param string $tipo
     * @return array
     */
    public function findByTipo(string $tipo): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE tipo = ?";
        $stmt = $this->prepareAndExecute($sql, [$tipo]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}