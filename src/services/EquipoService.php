<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\EquipoRepository;
use App\Repositories\NapRepository;
use App\Repositories\ServicioRepository;

/**
 * Servicio para la gestión de equipos (CPE).
 */
final class EquipoService extends BaseService
{
    /** @var EquipoRepository */
    private EquipoRepository $equipoRepo;

    /** @var NapRepository */
    private NapRepository $napRepo;

    /** @var ServicioRepository */
    private ServicioRepository $servicioRepo;

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->equipoRepo = new EquipoRepository($pdo);
        $this->napRepo = new NapRepository($pdo);
        $this->servicioRepo = new ServicioRepository($pdo);
    }

    public function crear(array $datos): int
    {
        if (!isset($datos['nap_id']) || !$this->napRepo->exists((int) $datos['nap_id'])) {
            throw new NotFoundException('La NAP especificada no existe.');
        }

        if (isset($datos['servicio_id']) && !$this->servicioRepo->exists((int) $datos['servicio_id'])) {
            throw new NotFoundException('El servicio especificado no existe.');
        }

        $this->validarCoherencia($datos);

        return $this->equipoRepo->create($datos);
    }

    public function actualizar(int $id, array $datos): void
    {
        $equipo = $this->equipoRepo->findById($id);
        if (!$equipo) {
            throw new NotFoundException('Equipo no encontrado.');
        }

        if (isset($datos['nap_id']) && !$this->napRepo->exists((int) $datos['nap_id'])) {
            throw new NotFoundException('La NAP especificada no existe.');
        }

        if (isset($datos['servicio_id']) && !$this->servicioRepo->exists((int) $datos['servicio_id'])) {
            throw new NotFoundException('El servicio especificado no existe.');
        }

        $mergedDatos = array_merge($equipo, $datos);
        $this->validarCoherencia($mergedDatos);

        $this->equipoRepo->update($id, $datos);
    }

    public function eliminar(int $id): void
    {
        if (!$this->equipoRepo->exists($id)) {
            throw new NotFoundException('Equipo no encontrado.');
        }

        $this->equipoRepo->delete($id);
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->equipoRepo->findById($id);
    }

    public function buscarPorMac(string $mac): ?array
    {
        return $this->equipoRepo->findByMac($mac);
    }

    public function buscarPorSerie(string $serie): ?array
    {
        return $this->equipoRepo->findBySerie($serie);
    }

    public function listarPorNap(int $napId): array
    {
        return $this->equipoRepo->findByNapId($napId);
    }

    public function listarPorServicio(int $servicioId): array
    {
        return $this->equipoRepo->findByServicioId($servicioId);
    }

    public function listarPorEstado(string $estado): array
    {
        return $this->equipoRepo->findByEstadoInventario($estado);
    }

    /**
     * Obtiene todos los equipos sin filtro.
     *
     * 140c: usado por EquipoController::index cuando no hay filtro.
     *
     * @return array
     */
    public function listarTodos(): array
    {
        return $this->equipoRepo->findAll();
    }

    public function buscarPorNapPuerto(int $napId, int $puertoNap): ?array
    {
        return $this->equipoRepo->findByNapIdAndPuertoNap($napId, $puertoNap);
    }

    private function validarCoherencia(array $datos): void
    {
        if (isset($datos['tipo']) && !in_array($datos['tipo'], ['ONU', 'COMBO', 'ROUTER'], true)) {
            throw new ValidationException(['tipo' => 'Tipo de equipo inválido.']);
        }

        if (isset($datos['estado_inventario']) && !in_array($datos['estado_inventario'], ['stock', 'asignado', 'devuelto', 'averiado', 'baja'], true)) {
            throw new ValidationException(['estado_inventario' => 'Estado de inventario inválido.']);
        }

        if (isset($datos['estado_fisico']) && !in_array($datos['estado_fisico'], ['operativo', 'averiado', 'stock'], true)) {
            throw new ValidationException(['estado_fisico' => 'Estado físico inválido.']);
        }

        if (isset($datos['propiedad']) && !in_array($datos['propiedad'], ['cliente', 'empresa'], true)) {
            throw new ValidationException(['propiedad' => 'Propiedad inválida.']);
        }

        $estadoInventario = $datos['estado_inventario'] ?? null;
        $servicioId = array_key_exists('servicio_id', $datos) ? $datos['servicio_id'] : null;

        if ($estadoInventario === 'stock' && $servicioId !== null) {
            throw new ValidationException(['servicio_id' => 'Un equipo en stock no puede tener un servicio asignado.']);
        }

        if ($estadoInventario === 'asignado' && $servicioId === null) {
            throw new ValidationException(['servicio_id' => 'Un equipo asignado debe tener un servicio asociado.']);
        }

        $tipo = $datos['tipo'] ?? null;
        $propiedad = $datos['propiedad'] ?? null;

        if ($tipo === 'ONU' && $propiedad !== 'empresa') {
            throw new ValidationException(['propiedad' => 'Un equipo tipo ONU siempre debe ser propiedad de la empresa.']);
        }
    }
}