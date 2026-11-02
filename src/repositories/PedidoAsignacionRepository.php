<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad PedidoAsignacion.
 */
final class PedidoAsignacionRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'pedido_asignaciones';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene las asignaciones de un pedido, ordenadas por fecha de asignación descendente.
     * Incluye el username del técnico (tecnico_username) y el username del
     * usuario que asignó (asignador_username) para que la UI no muestre IDs crudos.
     *
     * @param int $pedidoId
     * @return array
     */
    public function findByPedidoId(int $pedidoId): array
    {
        $sql = "SELECT pa.*, 
                       t.username AS tecnico_username,
                       a.username AS asignador_username
                FROM {$this->table} pa
                LEFT JOIN usuarios t ON t.id = pa.tecnico_id
                LEFT JOIN usuarios a ON a.id = pa.usuario_asignador_id
                WHERE pa.pedido_id = ?
                ORDER BY pa.asignado_en DESC";
        $stmt = $this->prepareAndExecute($sql, [$pedidoId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las asignaciones de un técnico.
     *
     * @param int $tecnicoId
     * @return array
     */
    public function findByTecnicoId(int $tecnicoId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE tecnico_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$tecnicoId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las asignaciones filtradas por estado.
     *
     * @param string $estado
     * @return array
     */
    public function findByEstado(string $estado): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE estado = ?";
        $stmt = $this->prepareAndExecute($sql, [$estado]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca la asignación activa de un pedido.
     *
     * No hay constraint en BD que garantice una sola asignación
     * activa por pedido. El flujo del service (spec 137) debe
     * garantizarlo antes de crear una nueva.
     *
     * @param int $pedidoId
     * @return array|null
     */
    public function findActivaByPedidoId(int $pedidoId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE pedido_id = ? AND estado = 'activa' LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$pedidoId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }
}