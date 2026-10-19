<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\NotFoundException;
use App\Exceptions\DomainException;
use App\Repositories\NapRepository;
use App\Repositories\OltRepository;
use App\Repositories\EquipoRepository;
use App\Repositories\ServicioRepository;

/**
 * Servicio para la gestión de NAPs.
 */
final class NapService extends BaseService
{
    /** @var NapRepository */
    private NapRepository $napRepo;

    /** @var OltRepository */
    private OltRepository $oltRepo;

    /** @var EquipoRepository */
    private EquipoRepository $equipoRepo;

    /** @var ServicioRepository */
    private ServicioRepository $servicioRepo;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->napRepo = new NapRepository($pdo);
        $this->oltRepo = new OltRepository($pdo);
        $this->equipoRepo = new EquipoRepository($pdo);
        $this->servicioRepo = new ServicioRepository($pdo);
    }

    /**
     * Crea una nueva NAP.
     *
     * @param array $datos
     * @return int
     * @throws NotFoundException
     */
    public function crear(array $datos): int
    {
        if (!isset($datos['olt_id']) || !$this->oltRepo->exists((int) $datos['olt_id'])) {
            throw new NotFoundException('La OLT especificada no existe.');
        }

        return $this->napRepo->create($datos);
    }

    /**
     * Actualiza una NAP existente.
     *
     * @param int $id
     * @param array $datos
     * @return void
     * @throws NotFoundException
     */
    public function actualizar(int $id, array $datos): void
    {
        if (!$this->napRepo->exists($id)) {
            throw new NotFoundException('NAP no encontrada.');
        }

        if (isset($datos['olt_id']) && !$this->oltRepo->exists((int) $datos['olt_id'])) {
            throw new NotFoundException('La OLT especificada no existe.');
        }

        $this->napRepo->update($id, $datos);
    }

    /**
     * Elimina una NAP si no tiene equipos ni servicios asociados.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     * @throws DomainException
     */
    public function eliminar(int $id): void
    {
        if (!$this->napRepo->exists($id)) {
            throw new NotFoundException('NAP no encontrada.');
        }

        $equipos = $this->equipoRepo->findByNapId($id);
        if (!empty($equipos)) {
            throw new DomainException('La NAP tiene equipos asignados; reasígnalos primero.');
        }

        if ($this->servicioRepo->countByNapId($id) > 0) {
            throw new DomainException('La NAP tiene servicios activos; reasígnalos primero.');
        }

        $this->napRepo->delete($id);
    }

    /**
     * Busca una NAP por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->napRepo->findById($id);
    }

    /**
     * Busca una NAP por su código.
     *
     * @param string $codigo
     * @return array|null
     */
    public function buscarPorCodigo(string $codigo): ?array
    {
        return $this->napRepo->findByCodigo($codigo);
    }

    /**
     * Obtiene todas las NAPs ordenadas por código.
     *
     * @return array
     */
    public function listar(): array
    {
        return $this->napRepo->findAllOrderedByCodigo();
    }

    /**
     * Obtiene las NAPs asociadas a una OLT.
     *
     * @param int $oltId
     * @return array
     */
    public function listarPorOlt(int $oltId): array
    {
        return $this->napRepo->findByOltId($oltId);
    }
}