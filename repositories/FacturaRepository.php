<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Factura.
 */
final class FacturaRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'facturas';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene las facturas de un servicio, ordenadas por fecha de vencimiento descendente.
     *
     * @param int $servicioId
     * @return array
     */
    public function findByServicioId(int $servicioId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE servicio_id = ? ORDER BY fecha_vencimiento DESC";
        $stmt = $this->prepareAndExecute($sql, [$servicioId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca una factura específica por su servicio y periodo.
     *
     * @param int $servicioId
     * @param string $periodo
     * @return array|null
     */
    public function findByServicioIdAndPeriodo(int $servicioId, string $periodo): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE servicio_id = ? AND periodo = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$servicioId, $periodo]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Obtiene las facturas filtradas por estado.
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
     * Obtiene las facturas pendientes cuya fecha de vencimiento ya pasó.
     *
     * @return array
     */
    public function findPendientesVencidas(): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE estado = 'pendiente' AND fecha_vencimiento < CURDATE()";
        $stmt = $this->prepareAndExecute($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta el total de facturas asociadas a un servicio.
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