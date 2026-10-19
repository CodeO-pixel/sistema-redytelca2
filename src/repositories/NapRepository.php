<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Nap.
 */
final class NapRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'naps';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene todas las NAPs ordenadas por código ascendente.
     *
     * @return array
     */
    public function findAllOrderedByCodigo(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY codigo ASC";
        $stmt = $this->prepareAndExecute($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca una NAP por su código.
     *
     * @param string $codigo
     * @return array|null
     */
    public function findByCodigo(string $codigo): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE codigo = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$codigo]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Obtiene las NAPs asociadas a una OLT.
     *
     * @param int $oltId
     * @return array
     */
    public function findByOltId(int $oltId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE olt_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$oltId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}