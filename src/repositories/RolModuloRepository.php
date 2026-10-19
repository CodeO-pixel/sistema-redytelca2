<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la tabla pivote rol_modulo.
 */
final class RolModuloRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'rol_modulo';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene todos los módulos asignados a un rol.
     *
     * @param int $rolId
     * @return array
     */
    public function findByRolId(int $rolId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE rol_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$rolId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca la fila pivote para un rol y un módulo.
     *
     * @param int $rolId
     * @param int $moduloId
     * @return array|null
     */
    public function findByRolIdAndModuloId(int $rolId, int $moduloId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE rol_id = ? AND modulo_id = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$rolId, $moduloId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Devuelve el id de la fila pivote rol_modulo, creándola si no existe.
     *
     * @param int $rolId
     * @param int $moduloId
     * @return int
     */
    public function ensure(int $rolId, int $moduloId): int
    {
        $row = $this->findByRolIdAndModuloId($rolId, $moduloId);
        if ($row) {
            return (int) $row['id'];
        }
        return $this->create(['rol_id' => $rolId, 'modulo_id' => $moduloId]);
    }
}