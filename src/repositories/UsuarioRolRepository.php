<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la tabla pivote usuario_rol (N:M usuario-rol).
 */
final class UsuarioRolRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'usuario_rol';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene las filas pivote de un usuario.
     *
     * @param int $usuarioId
     * @return array
     */
    public function findByUsuarioId(int $usuarioId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE usuario_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$usuarioId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los roles (id, nombre_rol) asignados a un usuario.
     *
     * @param int $usuarioId
     * @return array
     */
    public function findRolesByUsuarioId(int $usuarioId): array
    {
        $sql = "SELECT r.id, r.nombre_rol FROM rol r INNER JOIN {$this->table} ur ON ur.rol_id = r.id WHERE ur.usuario_id = ? ORDER BY r.id";
        $stmt = $this->prepareAndExecute($sql, [$usuarioId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene TODOS los roles agrupados por usuario_id, ordenados por rol_id ASC.
     * Devuelve [usuario_id => [['id_rol' => int, 'nombre_rol' => string], ...], ...].
     * Usado por UsuarioService::listarConRoles().
     *
     * @return array
     */
    public function findAllGrouped(): array
    {
        $sql = "SELECT ur.usuario_id, r.id AS id_rol, r.nombre_rol
                FROM {$this->table} ur
                INNER JOIN rol r ON r.id = ur.rol_id
                ORDER BY r.id";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($rows as $row) {
            $uid = (int) $row['usuario_id'];
            if (!isset($grouped[$uid])) {
                $grouped[$uid] = [];
            }
            $grouped[$uid][] = [
                'id_rol' => (int) $row['id_rol'],
                'nombre_rol' => $row['nombre_rol']
            ];
        }
        return $grouped;
    }

    /**
     * Elimina todos los roles asignados a un usuario.
     *
     * @param int $usuarioId
     * @return int
     */
    public function deleteByUsuarioId(int $usuarioId): int
    {
        $sql = "DELETE FROM {$this->table} WHERE usuario_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$usuarioId]);
        return $stmt->rowCount();
    }

    /**
     * Inserta ignorando duplicados (UNIQUE usuario_id, rol_id).
     *
     * @param int $usuarioId
     * @param int $rolId
     * @return bool
     */
    public function insertIgnore(int $usuarioId, int $rolId): bool
    {
        $sql = "INSERT IGNORE INTO {$this->table} (usuario_id, rol_id) VALUES (?, ?)";
        $stmt = $this->prepareAndExecute($sql, [$usuarioId, $rolId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Cuenta los usuarios únicos asignados a un rol.
     *
     * @param int $rolId
     * @return int
     */
    public function countByRolId(int $rolId): int
    {
        $sql = "SELECT COUNT(DISTINCT usuario_id) FROM {$this->table} WHERE rol_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$rolId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Cuenta otros administradores activos distintos del usuario indicado.
     *
     * @param int $idUsuario
     * @return int
     */
    public function countOtrosAdminsActivos(int $idUsuario): int
    {
        $sql = "SELECT COUNT(DISTINCT u.id) FROM usuarios u INNER JOIN {$this->table} ur ON ur.usuario_id = u.id WHERE ur.rol_id = 1 AND u.id <> ? AND u.estado = 'Activo'";
        $stmt = $this->prepareAndExecute($sql, [$idUsuario]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Devuelve los IDs de los usuarios activos que tienen un rol específico,
     * excluyendo opcionalmente a uno.
     *
     * Usado por NotificacionService::notificarStaffAdmins() para notificar
     * a todos los administradores activos excepto al generador del evento.
     *
     * @param int      $rolId
     * @param int|null $excluirUsuarioId
     * @return int[]
     */
    public function findActivosByRolId(int $rolId, ?int $excluirUsuarioId = null): array
    {
        $sql = "SELECT DISTINCT u.id
                FROM usuarios u
                INNER JOIN {$this->table} ur ON ur.usuario_id = u.id
                WHERE ur.rol_id = ? AND u.estado = 'Activo'";
        $params = [$rolId];

        if ($excluirUsuarioId !== null && $excluirUsuarioId > 0) {
            $sql .= " AND u.id <> ?";
            $params[] = $excluirUsuarioId;
        }

        $sql .= " ORDER BY u.id";

        $stmt = $this->prepareAndExecute($sql, $params);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN, 0));
    }
}