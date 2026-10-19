<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Permiso.
 */
final class PermisoRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'permisos';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene todos los permisos ordenados por ID ascendente.
     *
     * @return array
     */
    public function findAllOrderedById(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY id ASC";
        $stmt = $this->prepareAndExecute($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca un permiso por su nombre.
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
}