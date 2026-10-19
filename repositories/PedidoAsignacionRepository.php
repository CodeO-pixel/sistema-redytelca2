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
     *
     * @param int $pedidoId
     * @return array
     */
    public function findByPedidoId(int $pedidoId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE pedido_id = ? ORDER BY asignado_en DESC";
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