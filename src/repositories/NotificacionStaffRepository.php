<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad NotificacionStaff.
 */
final class NotificacionStaffRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'notificaciones_staff';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene las notificaciones de un usuario staff, ordenadas por fecha de creación descendente.
     *
     * @param int $usuarioId
     * @return array
     */
    public function findByUsuarioId(int $usuarioId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE usuario_id = ? ORDER BY creado_en DESC";
        $stmt = $this->prepareAndExecute($sql, [$usuarioId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lista paginada de notificaciones del usuario, ordenadas por creado_en DESC.
     *
     * @param int $usuarioId
     * @param int $page
     * @param int $perPage
     * @return array{items: array, total: int}
     */
    public function paginateByUsuarioId(int $usuarioId, int $page, int $perPage): array
    {
        $offset = max(0, ($page - 1) * $perPage);

        $countSql = "SELECT COUNT(*) FROM {$this->table} WHERE usuario_id = ?";
        $stmtCount = $this->prepareAndExecute($countSql, [$usuarioId]);
        $total = (int) $stmtCount->fetchColumn();

        $sql = "SELECT * FROM {$this->table}
                WHERE usuario_id = ?
                ORDER BY creado_en DESC, id DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(1, $usuarioId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(\PDO::FETCH_ASSOC),
            'total' => $total,
        ];
    }

    /**
     * Cuenta las notificaciones no leídas de un usuario staff.
     *
     * @param int $usuarioId
     * @return int
     */
    public function countNoLeidasByUsuarioId(int $usuarioId): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE usuario_id = ? AND estado = 'no leido'";
        $stmt = $this->prepareAndExecute($sql, [$usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Marca una notificación como leída.
     *
     * Idempotente: si ya estaba leída, no cambia `leida_en` (COALESCE
     * preserva el timestamp original). Devuelve `false` si la notificación
     * no existe o no pertenece al usuario.
     *
     * @param int $id
     * @param int $usuarioId
     * @return bool True si la notificación existe y pertenece al usuario.
     */
    public function markAsRead(int $id, int $usuarioId): bool
    {
        $sqlCheck = "SELECT id FROM {$this->table} WHERE id = ? AND usuario_id = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sqlCheck, [$id, $usuarioId]);
        if ($stmt->fetchColumn() === false) {
            return false;
        }

        $sqlUpdate = "UPDATE {$this->table}
                      SET leida_en = COALESCE(leida_en, NOW()),
                          estado = 'leido'
                      WHERE id = ?";
        $this->prepareAndExecute($sqlUpdate, [$id]);

        return true;
    }

    /**
     * Marca todas las notificaciones no leídas del usuario como leídas.
     *
     * @param int $usuarioId
     * @return int Número de notificaciones que pasaron de no leídas a leídas.
     */
    public function markAllAsReadByUsuarioId(int $usuarioId): int
    {
        $sql = "UPDATE {$this->table}
                SET leida_en = COALESCE(leida_en, NOW()),
                    estado = 'leido'
                WHERE usuario_id = ? AND estado = 'no leido'";
        $stmt = $this->prepareAndExecute($sql, [$usuarioId]);
        return $stmt->rowCount();
    }
}