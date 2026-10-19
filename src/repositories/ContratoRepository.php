<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Contrato.
 */
final class ContratoRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'contratos';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene los contratos de un servicio.
     *
     * @param int $servicioId
     * @return array
     */
    public function findByServicioId(int $servicioId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE servicio_id = ? ORDER BY fecha_inicio DESC";
        $stmt = $this->prepareAndExecute($sql, [$servicioId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el contrato vigente de un servicio, si existe.
     *
     * @param int $servicioId
     * @return array|null
     */
    public function findVigenteByServicioId(int $servicioId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE servicio_id = ? AND estado = 'vigente' LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$servicioId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Obtiene los contratos filtrados por estado.
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
     * Cuenta el total de contratos de un servicio.
     *
     * @param int $servicioId
     * @return int
     */
    public function countByServicioId(int $servicioId): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE servicio_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$servicioId]);
        return (int) $stmt->fetchColumn();
    }
}