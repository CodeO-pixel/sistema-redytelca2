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
     * Incluye el código de la OLT (olt_codigo), el nombre del nodo (nodo_nombre)
     * y la cantidad de servicios no retirados (total_clientes).
     *
     * @return array
     */
    public function findAllOrderedByCodigo(): array
    {
        $sql = "SELECT na.*, 
                       o.codigo AS olt_codigo, 
                       nd.nombre AS nodo_nombre, 
                       (SELECT COUNT(*) FROM servicios s WHERE s.nap_id = na.id AND s.estado_comercial != 'retirado') AS total_clientes 
                FROM {$this->table} na 
                INNER JOIN olts o ON o.id = na.olt_id 
                INNER JOIN nodos nd ON nd.id = o.nodo_id 
                ORDER BY na.codigo ASC";
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