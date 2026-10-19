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