<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Nodo.
 */
final class NodoRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'nodos';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene todos los nodos ordenados por nombre ascendente.
     * Incluye la columna adicional `total_olts` con la cantidad de OLTs asociadas.
     *
     * @return array
     */
    public function findAllOrderedByNombre(): array
    {
        $sql = "SELECT n.*, 
                       (SELECT COUNT(*) FROM olts o WHERE o.nodo_id = n.id) AS total_olts 
                FROM {$this->table} n 
                ORDER BY n.nombre ASC";
        $stmt = $this->prepareAndExecute($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca un nodo por su nombre.
     *
     * @param string $nombre
     * @return array|null
     */
    public function findByNombre(string $nombre): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE nombre = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$nombre]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Obtiene los nodos filtrados por estado.
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
     * Cuenta el total de OLTs asociadas a un nodo.
     *
     * Consulta cross-tabla: usa la tabla `olts` (no `$this->table`,
     * que es `nodos`). Sin filtro de estado, replicando
     * api_nodos.php → total_olts.
     *
     * @param int $nodoId
     * @return int
     */
    public function countOltsByNodoId(int $nodoId): int
    {
        $sql = "SELECT COUNT(*) FROM olts WHERE nodo_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$nodoId]);
        return (int) $stmt->fetchColumn();
    }
}