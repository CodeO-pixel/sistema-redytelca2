<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad FacturaLinea.
 */
final class FacturaLineaRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'factura_lineas';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene las líneas de una factura, ordenadas por ID ascendente.
     *
     * @param int $facturaId
     * @return array
     */
    public function findByFacturaId(int $facturaId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE factura_id = ? ORDER BY id ASC";
        $stmt = $this->prepareAndExecute($sql, [$facturaId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}