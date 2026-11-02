<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Tarea.
 */
final class TareaRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'tareas';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene todas las tareas ordenadas por fecha de creación descendente.
     * Incluye nombres, apellidos y cédula del cliente, el username del creador,
     * y el alias + dirección del servicio asociado (si la tarea tiene servicio_id).
     *
     * @return array
     */
    public function findAllOrderedByCreadoEnDesc(): array
    {
        $sql = "SELECT t.*, 
                       c.nombre AS nombres, 
                       c.apellido AS apellidos, 
                       c.cedula AS cedula,
                       u.username AS creador,
                       s.alias AS servicio_alias,
                       ds.calle AS servicio_direccion
                FROM {$this->table} t
                LEFT JOIN clientes c ON c.id = t.cliente_id
                LEFT JOIN usuarios u ON u.id = t.usuario_creador_id
                LEFT JOIN servicios s ON s.id = t.servicio_id
                LEFT JOIN direccion_servicio ds ON ds.id = s.direccion_id
                ORDER BY t.creado_en DESC";
        $stmt = $this->prepareAndExecute($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las tareas cuya creado_en cae en el rango [desde, hasta].
     * Mismo SELECT enriquecido que findAllOrderedByCreadoEnDesc() + WHERE
     * de rango.
     *
     * B3.15 — Reportes con periodo (filter server-side).
     *
     * IMPORTANTE: `tareas.creado_en` es TIMESTAMP, no DATE. El filtro
     * compara contra el rango completo del día ('YYYY-MM-DD 00:00:00'
     * a 'YYYY-MM-DD 23:59:59') para no perder las tareas creadas
     * durante el último día del rango. Mismo criterio que
     * TicketRepository::findAllByCreadoEnRange y
     * PagoRepository::findAllByFechaPagoRange.
     *
     * @param string $desde YYYY-MM-DD (inclusive).
     * @param string $hasta YYYY-MM-DD (inclusive).
     * @return array
     */
    public function findAllByCreadoEnRange(string $desde, string $hasta): array
    {
        $sql = "SELECT t.*, 
                       c.nombre AS nombres, 
                       c.apellido AS apellidos, 
                       c.cedula AS cedula,
                       u.username AS creador,
                       s.alias AS servicio_alias,
                       ds.calle AS servicio_direccion
                FROM {$this->table} t
                LEFT JOIN clientes c ON c.id = t.cliente_id
                LEFT JOIN usuarios u ON u.id = t.usuario_creador_id
                LEFT JOIN servicios s ON s.id = t.servicio_id
                LEFT JOIN direccion_servicio ds ON ds.id = s.direccion_id
                WHERE t.creado_en >= ? AND t.creado_en <= ?
                ORDER BY t.creado_en DESC, t.id DESC";
        $stmt = $this->prepareAndExecute($sql, [$desde . ' 00:00:00', $hasta . ' 23:59:59']);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las tareas de un cliente.
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
     * Obtiene las tareas derivadas de un ticket.
     *
     * @param int $ticketId
     * @return array
     */
    public function findByTicketId(int $ticketId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE ticket_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$ticketId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las tareas de un servicio.
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
     * Obtiene las tareas creadas por un usuario.
     *
     * @param int $usuarioId
     * @return array
     */
    public function findByUsuarioCreadorId(int $usuarioId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE usuario_creador_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$usuarioId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las tareas filtradas por estado.
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
     * Obtiene las tareas filtradas por prioridad.
     *
     * @param string $prioridad
     * @return array
     */
    public function findByPrioridad(string $prioridad): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE prioridad = ?";
        $stmt = $this->prepareAndExecute($sql, [$prioridad]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}