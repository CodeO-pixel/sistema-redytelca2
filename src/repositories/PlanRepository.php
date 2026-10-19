<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Plan.
 */
final class PlanRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'planes';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene los planes filtrados por estado.
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
     * Obtiene todos los planes ordenados por precio mensual ascendente.
     *
     * @return array
     */
    public function findAllOrderedByPrecio(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY precio_mensual ASC";
        $stmt = $this->prepareAndExecute($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta el total de servicios asociados a un plan.
     *
     * Consulta cross-tabla: usa la tabla `servicios` (no `$this->table`,
     * que es `planes`), porque el conteo se hace sobre los servicios
     * que apuntan al plan.
     *
     * @param int $planId
     * @return int
     */
    public function countServiciosByPlanId(int $planId): int
    {
        $sql = "SELECT COUNT(*) FROM servicios WHERE plan_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$planId]);
        return (int) $stmt->fetchColumn();
    }
}