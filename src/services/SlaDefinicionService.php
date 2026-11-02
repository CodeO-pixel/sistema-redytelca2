<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\NotFoundException;
use App\Exceptions\ConflictException;
use App\Repositories\SlaDefinicionRepository;
use App\Repositories\CategoriaTicketRepository;

/**
 * Servicio para la gestión de definiciones SLA.
 */
final class SlaDefinicionService extends BaseService
{
    /** @var SlaDefinicionRepository */
    private SlaDefinicionRepository $slaRepo;

    /** @var CategoriaTicketRepository */
    private CategoriaTicketRepository $categoriaRepo;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->slaRepo = new SlaDefinicionRepository($pdo);
        $this->categoriaRepo = new CategoriaTicketRepository($pdo);
    }

    /**
     * Crea una nueva definición SLA.
     *
     * @param array $datos
     * @return int
     * @throws NotFoundException
     * @throws ConflictException
     */
    public function crear(array $datos): int
    {
        if (!isset($datos['categoria_id']) || !$this->categoriaRepo->exists((int) $datos['categoria_id'])) {
            throw new NotFoundException('La categoría especificada no existe.');
        }

        $existente = $this->slaRepo->findByCategoriaIdAndPrioridad((int) $datos['categoria_id'], $datos['prioridad']);
        if ($existente) {
            throw new ConflictException('Ya existe una definición SLA para esa categoría y prioridad.');
        }

        return $this->slaRepo->create($datos);
    }

    /**
     * Actualiza una definición SLA existente.
     *
     * @param int $id
     * @param array $datos
     * @return void
     * @throws NotFoundException
     * @throws ConflictException
     */
    public function actualizar(int $id, array $datos): void
    {
        $sla = $this->slaRepo->findById($id);
        if (!$sla) {
            throw new NotFoundException('Definición SLA no encontrada.');
        }

        if (isset($datos['categoria_id']) && !$this->categoriaRepo->exists((int) $datos['categoria_id'])) {
            throw new NotFoundException('La categoría especificada no existe.');
        }

        $catId = isset($datos['categoria_id']) ? (int) $datos['categoria_id'] : (int) $sla['categoria_id'];
        $prioridad = $datos['prioridad'] ?? $sla['prioridad'];

        if ($catId !== (int) $sla['categoria_id'] || $prioridad !== $sla['prioridad']) {
            $existente = $this->slaRepo->findByCategoriaIdAndPrioridad($catId, $prioridad);
            if ($existente && (int) $existente['id'] !== $id) {
                throw new ConflictException('Ya existe una definición SLA para esa categoría y prioridad.');
            }
        }

        $this->slaRepo->update($id, $datos);
    }

    /**
     * Elimina una definición SLA.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     */
    public function eliminar(int $id): void
    {
        if (!$this->slaRepo->exists($id)) {
            throw new NotFoundException('Definición SLA no encontrada.');
        }

        $this->slaRepo->delete($id);
    }

    /**
     * Obtiene todas las definiciones SLA ordenadas por categoría y prioridad.
     *
     * @return array
     */
    public function listar(): array
    {
        return $this->slaRepo->findAllOrderedByCategoria();
    }
}