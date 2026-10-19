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
     *
     * @return array
     */
    public function findAllOrderedByCreadoEnDesc(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY creado_en DESC";
        $stmt = $this->prepareAndExecute($sql);
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