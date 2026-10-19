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

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->equipoRepo = new EquipoRepository($pdo);
        $this->napRepo = new NapRepository($pdo);
        $this->servicioRepo = new ServicioRepository($pdo);
    }

    /**
     * Crea un nuevo equipo.
     *
     * @param array $datos
     * @return int
     * @throws NotFoundException
     * @throws ValidationException
     */
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

    /**
     * Actualiza un equipo existente.
     *
     * @param int $id
     * @param array $datos
     * @return void
     * @throws NotFoundException
     * @throws ValidationException
     */
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

    /**
     * Elimina un equipo.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     */
    public function eliminar(int $id): void
    {
        if (!$this->equipoRepo->exists($id)) {
            throw new NotFoundException('Equipo no encontrado.');
        }

        $this->equipoRepo->delete($id);
    }

    /**
     * Busca un equipo por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->equipoRepo->findById($id);
    }

    /**
     * Busca un equipo por su dirección MAC.
     *
     * @param string $mac
     * @return array|null
     */
    public function buscarPorMac(string $mac): ?array
    {
        return $this->equipoRepo->findByMac($mac);
    }

    /**
     * Busca un equipo por su número de serie.
     *
     * @param string $serie
     * @return array|null
     */
    public function buscarPorSerie(string $serie): ?array
    {
        return $this->equipoRepo->findBySerie($serie);
    }

    /**
     * Obtiene los equipos asociados a una NAP.
     *
     * @param int $napId
     * @return array
     */
    public function listarPorNap(int $napId): array
    {
        return $this->equipoRepo->findByNapId($napId);
    }

    /**
     * Obtiene los equipos asignados a un servicio.
     *
     * @param int $servicioId
     * @return array
     */
    public function listarPorServicio(int $servicioId): array
    {
        return $this->equipoRepo->findByServicioId($servicioId);
    }

    /**
     * Obtiene los equipos filtrados por estado de inventario.
     *
     * @param string $estado
     * @return array
     */
    public function listarPorEstado(string $estado): array
    {
        return $this->equipoRepo->findByEstadoInventario($estado);
    }

    /**
     * Busca un equipo por su NAP y puerto.
     *
     * @param int $napId
     * @param int $puertoNap
     * @return array|null
     */
    public function buscarPorNapPuerto(int $napId, int $puertoNap): ?array
    {
        return $this->equipoRepo->findByNapIdAndPuertoNap($napId, $puertoNap);
    }

    /**
     * Método privado. Valida los ENUM y los CHECK de la tabla equipos antes de persistir.
     * No se llama desde fuera del service.
     *
     * @param array $datos
     * @return void
     * @throws ValidationException
     */
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