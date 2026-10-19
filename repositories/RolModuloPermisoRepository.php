<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la tabla pivote rol_modulo_permiso (operación nivel 2 RBAC).
 */
final class RolModuloPermisoRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'rol_modulo_permiso';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene los permisos de un rol_módulo.
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
     * Elimina los permisos de un rol_módulo.
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
     * Inserta ignorando duplicados (UNIQUE rol_modulo_id, pagina_id, permiso_id).
     *
     * @param int $rolModuloId
     * @param int $moduloId
     * @param int $paginaId
     * @param int $permisoId
     * @return bool
     */
    public function insertIgnore(int $rolModuloId, int $moduloId, int $paginaId, int $permisoId): bool
    {
        $sql = "INSERT IGNORE INTO {$this->table} (rol_modulo_id, modulo_id, pagina_id, permiso_id) VALUES (?, ?, ?, ?)";
        $stmt = $this->prepareAndExecute($sql, [$rolModuloId, $moduloId, $paginaId, $permisoId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Cuenta cuántas filas usan un permiso del catálogo.
     *
     * @param int $permisoId
     * @return int
     */
    public function countByPermisoId(int $permisoId): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE permiso_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$permisoId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Query cross-tabla: devuelve los módulos y permisos visibles para un
     * conjunto de roles. Usa rol_modulo_pagina, rol_modulo, modulos, paginas,
     * rol_modulo_permiso y permisos. Reemplaza el SQL crudo de
     * AuthService::cargarModulosPermitidos (deuda 136f-bis).
     *
     * @param array $idsRoles
     * @return array
     */
    public function findModulosPermitidosByRoles(array $idsRoles): array
    {
        if (empty($idsRoles)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($idsRoles), '?'));
        $sql = "SELECT
                    m.id AS id_modulo,
                    m.nombre_modulo AS modulo,
                    p.nombre_pagina AS vista,
                    p.url_pagina AS url,
                    GROUP_CONCAT(DISTINCT pe.nombre ORDER BY pe.id SEPARATOR ',') AS permisos
                FROM rol_modulo_pagina rmpag
                INNER JOIN rol_modulo rm ON rm.id = rmpag.rol_modulo_id
                INNER JOIN modulos m ON m.id = rmpag.modulo_id
                INNER JOIN paginas p ON p.id = rmpag.pagina_id
                LEFT JOIN rol_modulo_permiso rmp ON rmp.rol_modulo_id = rmpag.rol_modulo_id
                                                AND rmp.pagina_id = rmpag.pagina_id
                LEFT JOIN permisos pe ON pe.id = rmp.permiso_id
                WHERE rm.rol_id IN ($placeholders)
                GROUP BY m.id, m.nombre_modulo, p.id, p.nombre_pagina, p.url_pagina
                ORDER BY m.id, p.id";
        $stmt = $this->prepareAndExecute($sql, $idsRoles);
        $resultado = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($resultado as &$fila) {
            $fila['permisos'] = !empty($fila['permisos']) ? explode(',', $fila['permisos']) : [];
        }
        unset($fila);
        return $resultado;
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
        $sql = "SELECT rmp.* FROM {$this->table} rmp
                INNER JOIN rol_modulo rm ON rm.id = rmp.rol_modulo_id
                WHERE rm.rol_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$rolId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}