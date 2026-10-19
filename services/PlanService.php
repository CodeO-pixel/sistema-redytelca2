<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\NotFoundException;
use App\Exceptions\DomainException;
use App\Repositories\PlanRepository;
use App\Repositories\ServicioRepository;

/**
 * Servicio para la gestión de planes.
 */
final class PlanService extends BaseService
{
    /** @var PlanRepository */
    private PlanRepository $planRepo;

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
        $this->planRepo = new PlanRepository($pdo);
        $this->servicioRepo = new ServicioRepository($pdo);
    }

    /**
     * Crea un nuevo plan.
     *
     * @param array $datos
     * @return int
     */
    public function crear(array $datos): int
    {
        return $this->planRepo->create($datos);
    }

    /**
     * Actualiza un plan existente.
     *
     * @param int $id
     * @param array $datos
     * @return void
     * @throws NotFoundException
     */
    public function actualizar(int $id, array $datos): void
    {
        if (!$this->planRepo->exists($id)) {
            throw new NotFoundException('Plan no encontrado.');
        }

        $this->planRepo->update($id, $datos);
    }

    /**
     * Elimina un plan si no tiene servicios asociados.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     * @throws DomainException
     */
    public function eliminar(int $id): void
    {
        if (!$this->planRepo->exists($id)) {
            throw new NotFoundException('Plan no encontrado.');
        }

        if ($this->servicioRepo->countByPlanId($id) > 0) {
            throw new DomainException('No se puede eliminar: este plan tiene servicios asociados.');
        }

        $this->planRepo->delete($id);
    }

    /**
     * Obtiene todos los planes ordenados por precio.
     *
     * @return array
     */
    public function listar(): array
    {
        return $this->planRepo->findAllOrderedByPrecio();
    }

    /**
     * Busca un plan por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->planRepo->findById($id);
    }

    /**
     * Cuenta los servicios asociados a un plan.
     *
     * @param int $id
     * @return int
     */
    public function contarServicios(int $id): int
    {
        return $this->planRepo->countServiciosByPlanId($id);
    }
}