<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Rol.
 */
final class RolRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'rol';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene todos los roles ordenados por ID ascendente.
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
     * Busca un rol por su nombre.
     *
     * @param string $nombreRol
     * @return array|null
     */
    public function findByNombreRol(string $nombreRol): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE nombre_rol = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$nombreRol]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }
}