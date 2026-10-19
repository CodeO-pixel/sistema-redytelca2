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
}