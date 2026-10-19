<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad NotaDebito.
 */
final class NotaDebitoRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'notas_debito';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene las notas de débito de un cliente, ordenadas por fecha de emisión descendente.
     *
     * @param int $clienteId
     * @return array
     */
    public function findByClienteId(int $clienteId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE cliente_id = ? ORDER BY emitida_en DESC";
        $stmt = $this->prepareAndExecute($sql, [$clienteId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las notas de débito asociadas a una factura.
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
}