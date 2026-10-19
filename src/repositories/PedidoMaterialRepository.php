<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad PedidoMaterial.
 */
final class PedidoMaterialRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'pedido_materiales';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene los materiales de un pedido, ordenados por ID ascendente.
     *
     * @param int $pedidoId
     * @return array
     */
    public function findByPedidoId(int $pedidoId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE pedido_id = ? ORDER BY id ASC";
        $stmt = $this->prepareAndExecute($sql, [$pedidoId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los materiales que referencian a un equipo.
     *
     * @param int $equipoId
     * @return array
     */
    public function findByEquipoId(int $equipoId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE equipo_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$equipoId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}