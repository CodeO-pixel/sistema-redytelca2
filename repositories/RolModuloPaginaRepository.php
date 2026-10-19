<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la tabla pivote rol_modulo_pagina (visibilidad nivel 1 RBAC).
 */
final class RolModuloPaginaRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'rol_modulo_pagina';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene las páginas visibles de un rol_módulo.
     *
     * @param int $rolModuloId
     * @return array
     */
    public function findByRolModuloId(int $rolModuloId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE rol_modulo_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$rolModuloId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Elimina las páginas visibles de un rol_módulo.
     *
     * @param int $rolModuloId
     * @return int
     */
    public function deleteByRolModuloId(int $rolModuloId): int
    {
        $sql = "DELETE FROM {$this->table} WHERE rol_modulo_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$rolModuloId]);
        return $stmt->rowCount();
    }

    /**
     * Inserta ignorando duplicados (UNIQUE rol_modulo_id, pagina_id).
     *
     * @param int $rolModuloId
     * @param int $moduloId
     * @param int $paginaId
     * @return bool
     */
    public function insertIgnore(int $rolModuloId, int $moduloId, int $paginaId): bool
    {
        $sql = "INSERT IGNORE INTO {$this->table} (rol_modulo_id, modulo_id, pagina_id) VALUES (?, ?, ?)";
        $stmt = $this->prepareAndExecute($sql, [$rolModuloId, $moduloId, $paginaId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Obtiene las filas pivote de un rol.
     *
     * Join con rol_modulo para filtrar por rol_id. Reemplaza el SQL
     * inline de permisos_listar.php (versión pre-139d).
     *
     * @param int $rolId
     * @return array
     */
    public function findByRolId(int $rolId): array
    {
        $sql = "SELECT rmpag.* FROM {$this->table} rmpag
                INNER JOIN rol_modulo rm ON rm.id = rmpag.rol_modulo_id
                WHERE rm.rol_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$rolId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}