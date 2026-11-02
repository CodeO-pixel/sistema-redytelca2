<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Equipo.
 */
final class EquipoRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'equipos';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene todos los equipos ordenados por ID ascendente.
     * Incluye el código de la NAP (nap_codigo), nombres, apellidos y cédula del cliente,
     * y el alias del servicio (servicio_alias).
     *
     * @return array
     */
    public function findAll(): array
    {
        $sql = "SELECT eq.*, 
                       na.codigo AS nap_codigo, 
                       c.nombre AS nombres, 
                       c.apellido AS apellidos, 
                       c.cedula AS cedula,
                       s.alias AS servicio_alias
                FROM {$this->table} eq
                INNER JOIN naps na ON na.id = eq.nap_id
                LEFT JOIN servicios s ON s.id = eq.servicio_id
                LEFT JOIN clientes c ON c.id = s.cliente_id
                ORDER BY eq.id ASC";
        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca un equipo por su dirección MAC.
     *
     * @param string $mac
     * @return array|null
     */
    public function findByMac(string $mac): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE direccion_mac = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$mac]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Busca un equipo por su número de serie.
     *
     * @param string $serie
     * @return array|null
     */
    public function findBySerie(string $serie): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE serie = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$serie]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Obtiene los equipos asociados a una NAP.
     *
     * @param int $napId
     * @return array
     */
    public function findByNapId(int $napId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE nap_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$napId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los equipos asignados a un servicio.
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
     * Busca un equipo por su NAP y puerto.
     *
     * Usado para detectar el conflicto de puerto antes de un
     * INSERT/UPDATE (UNIQUE idx_nap_puerto).
     *
     * @param int $napId
     * @param int $puertoNap
     * @return array|null
     */
    public function findByNapIdAndPuertoNap(int $napId, int $puertoNap): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE nap_id = ? AND puerto_nap = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$napId, $puertoNap]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Obtiene los equipos filtrados por estado de inventario.
     *
     * @param string $estado
     * @return array
     */
    public function findByEstadoInventario(string $estado): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE estado_inventario = ?";
        $stmt = $this->prepareAndExecute($sql, [$estado]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}