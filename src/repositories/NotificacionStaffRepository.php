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
}