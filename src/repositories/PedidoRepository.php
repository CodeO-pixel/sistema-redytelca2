<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Pedido.
 */
final class PedidoRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'pedidos';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene todos los pedidos ordenados por fecha de solicitud descendente.
     *
     * @return array
     */
    public function findAllOrderedByFechaSolicitudDesc(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY fecha_solicitud DESC";
        $stmt = $this->prepareAndExecute($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los pedidos de un cliente.
     *
     * @param int $clienteId
     * @return array
     */
    public function findByClienteId(int $clienteId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE cliente_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$clienteId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los pedidos de un servicio.
     *
     * @param int $servicioId
     * @return array
     */
    public function findByServicioId(int $servicioId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE servicio_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$servicioId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los pedidos asignados a un técnico.
     *
     * @param int $tecnicoId
     * @return array
     */
    public function findByTecnicoAsignadoId(int $tecnicoId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE tecnico_asignado_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$tecnicoId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los pedidos filtrados por estado.
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
     * Obtiene los pedidos filtrados por tipo.
     *
     * @param string $tipo
     * @return array
     */
    public function findByTipo(string $tipo): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE tipo = ?";
        $stmt = $this->prepareAndExecute($sql, [$tipo]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}