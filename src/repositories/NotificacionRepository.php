<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Notificacion.
 */
final class NotificacionRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'notificaciones';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene las notificaciones de un cliente, ordenadas por fecha de envío descendente.
     *
     * @param int $clienteId
     * @return array
     */
    public function findByClienteId(int $clienteId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE cliente_id = ? ORDER BY fecha_envio DESC";
        $stmt = $this->prepareAndExecute($sql, [$clienteId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lista paginada de notificaciones del cliente, ordenadas por fecha_envio DESC.
     *
     * @param int $clienteId
     * @param int $page
     * @param int $perPage
     * @return array{items: array, total: int}
     */
    public function paginateByClienteId(int $clienteId, int $page, int $perPage): array
    {
        $offset = max(0, ($page - 1) * $perPage);

        $countSql = "SELECT COUNT(*) FROM {$this->table} WHERE cliente_id = ?";
        $stmtCount = $this->prepareAndExecute($countSql, [$clienteId]);
        $total = (int) $stmtCount->fetchColumn();

        $sql = "SELECT * FROM {$this->table}
                WHERE cliente_id = ?
                ORDER BY fecha_envio DESC, id DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(1, $clienteId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(\PDO::FETCH_ASSOC),
            'total' => $total,
        ];
    }

    /**
     * Cuenta las notificaciones no leídas de un cliente.
     *
     * @param int $clienteId
     * @return int
     */
    public function countNoLeidasByClienteId(int $clienteId): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE cliente_id = ? AND estado = 'no leido'";
        $stmt = $this->prepareAndExecute($sql, [$clienteId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Marca una notificación como leída.
     *
     * Idempotente: si ya estaba leída, no cambia `leida_en` (COALESCE
     * preserva el timestamp original). Devuelve `false` si la notificación
     * no existe o no pertenece al cliente.
     *
     * @param int $id
     * @param int $clienteId
     * @return bool True si la notificación existe y pertenece al cliente.
     */
    public function markAsRead(int $id, int $clienteId): bool
    {
        $sqlCheck = "SELECT id FROM {$this->table} WHERE id = ? AND cliente_id = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sqlCheck, [$id, $clienteId]);
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
     * Marca todas las notificaciones no leídas del cliente como leídas.
     *
     * @param int $clienteId
     * @return int Número de notificaciones que pasaron de no leídas a leídas.
     */
    public function markAllAsReadByClienteId(int $clienteId): int
    {
        $sql = "UPDATE {$this->table}
                SET leida_en = COALESCE(leida_en, NOW()),
                    estado = 'leido'
                WHERE cliente_id = ? AND estado = 'no leido'";
        $stmt = $this->prepareAndExecute($sql, [$clienteId]);
        return $stmt->rowCount();
    }
}