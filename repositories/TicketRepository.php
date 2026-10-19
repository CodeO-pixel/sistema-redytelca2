<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Ticket.
 */
final class TicketRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'tickets';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene todos los tickets ordenados por fecha de creación descendente.
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
     * Obtiene los tickets de un cliente.
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
     * Obtiene los tickets de un servicio.
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
     * Obtiene los tickets filtrados por estado.
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
     * Obtiene los tickets filtrados por prioridad.
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

    /**
     * Obtiene los tickets de una categoría.
     *
     * @param int $categoriaId
     * @return array
     */
    public function findByCategoriaId(int $categoriaId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE categoria_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$categoriaId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los tickets filtrados por origen.
     *
     * @param string $origen
     * @return array
     */
    public function findByOrigen(string $origen): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE origen = ?";
        $stmt = $this->prepareAndExecute($sql, [$origen]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta los tickets abiertos de un cliente.
     *
     * Replica la semántica de v_clientes_360 → tickets_abiertos,
     * que excluye los tickets con estado = 'Cerrado'.
     *
     * @param int $clienteId
     * @return int
     */
    public function countAbiertosByClienteId(int $clienteId): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE cliente_id = ? AND estado != 'Cerrado'";
        $stmt = $this->prepareAndExecute($sql, [$clienteId]);
        return (int) $stmt->fetchColumn();
    }
}