<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Pago.
 */
final class PagoRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'pagos';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene los pagos asociados a una factura.
     *
     * @param int $facturaId
     * @return array
     */
    public function findByFacturaId(int $facturaId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE factura_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$facturaId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los pagos realizados por un cliente.
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
     * Obtiene los pagos filtrados por estado.
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
     * Calcula la suma de los montos de los pagos validados para una factura.
     *
     * @param int $facturaId
     * @return float
     */
    public function sumValidadosByFacturaId(int $facturaId): float
    {
        $sql = "SELECT COALESCE(SUM(monto), 0) FROM {$this->table} WHERE factura_id = ? AND estado = 'validado'";
        $stmt = $this->prepareAndExecute($sql, [$facturaId]);
        return (float) $stmt->fetchColumn();
    }

    /**
     * Obtiene los pagos asociados a un cliente y una factura específicos.
     *
     * @param int $clienteId
     * @param int $facturaId
     * @return array
     */
    public function findByClienteIdAndFacturaId(int $clienteId, int $facturaId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE cliente_id = ? AND factura_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$clienteId, $facturaId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}