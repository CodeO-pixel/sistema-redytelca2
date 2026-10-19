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
     * Obtiene los servicios de un cliente.
     *
     * @param int $clienteId
     * @return array
     */
    public function findByClienteId(int $clienteId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE cliente_id = ? ORDER BY id ASC";
        $stmt = $this->prepareAndExecute($sql, [$clienteId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
     * Replica la semántica de api_naps.php → total_clientes, que
     * excluye los servicios con estado_comercial = 'retirado'.
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