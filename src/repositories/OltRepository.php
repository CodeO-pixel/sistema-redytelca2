<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Olt.
 */
final class OltRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'olts';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene todas las OLTs ordenadas por ID ascendente.
     * Incluye el nombre del nodo (nombre_nodo) y la cantidad de NAPs asociadas (total_naps).
     *
     * @return array
     */
    public function findAllOrderedById(): array
    {
        $sql = "SELECT o.*, 
                       nd.nombre AS nombre_nodo, 
                       (SELECT COUNT(*) FROM naps na WHERE na.olt_id = o.id) AS total_naps 
                FROM {$this->table} o 
                INNER JOIN nodos nd ON nd.id = o.nodo_id 
                ORDER BY o.id ASC";
        $stmt = $this->prepareAndExecute($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca una OLT por su código.
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
     * Busca una OLT por su IP de gestión.
     *
     * @param string $ipGestion
     * @return array|null
     */
    public function findByIpGestion(string $ipGestion): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE ip_gestion = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$ipGestion]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Obtiene las OLTs asociadas a un nodo.
     *
     * @param int $nodoId
     * @return array
     */
    public function findByNodoId(int $nodoId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE nodo_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$nodoId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta el total de NAPs asociadas a una OLT.
     *
     * Consulta cross-tabla: usa la tabla `naps` (no `$this->table`,
     * que es `olts`). Sin filtro de estado, replicando
     * api_olts.php → total_naps.
     *
     * @param int $oltId
     * @return int
     */
    public function countNapsByOltId(int $oltId): int
    {
        $sql = "SELECT COUNT(*) FROM naps WHERE olt_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$oltId]);
        return (int) $stmt->fetchColumn();
    }
}