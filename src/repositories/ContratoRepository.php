<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Contrato.
 */
final class ContratoRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'contratos';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene todos los contratos.
     * Incluye nombres, apellidos y cédula del cliente, el alias del servicio
     * (servicio_alias), la dirección del servicio (direccion_texto) y el
     * estado comercial del servicio (servicio_estado) para que la UI pueda
     * marcar los contratos de servicios retirados (bug B3.7).
     *
     * @return array
     */
    public function findAll(): array
    {
        $sql = "SELECT ct.*, 
                       c.nombre AS nombres, 
                       c.apellido AS apellidos, 
                       c.cedula AS cedula,
                       s.alias AS servicio_alias, 
                       s.estado_comercial AS servicio_estado,
                       ds.calle AS direccion_texto
                FROM {$this->table} ct
                INNER JOIN servicios s ON s.id = ct.servicio_id
                INNER JOIN clientes c ON c.id = s.cliente_id
                LEFT JOIN direccion_servicio ds ON ds.id = s.direccion_id";
        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los contratos de un servicio.
     *
     * @param int $servicioId
     * @return array
     */
    public function findByServicioId(int $servicioId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE servicio_id = ? ORDER BY fecha_inicio DESC";
        $stmt = $this->prepareAndExecute($sql, [$servicioId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el contrato vigente de un servicio, si existe.
     *
     * @param int $servicioId
     * @return array|null
     */
    public function findVigenteByServicioId(int $servicioId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE servicio_id = ? AND estado = 'vigente' LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$servicioId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Obtiene los contratos filtrados por estado.
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
     * Cuenta el total de contratos de un servicio.
     *
     * @param int $servicioId
     * @return int
     */
    public function countByServicioId(int $servicioId): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE servicio_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$servicioId]);
        return (int) $stmt->fetchColumn();
    }
}