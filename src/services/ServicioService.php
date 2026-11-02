<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\NotFoundException;
use App\Exceptions\DomainException;
use App\Repositories\ServicioRepository;
use App\Repositories\ClienteRepository;
use App\Repositories\PlanRepository;
use App\Repositories\NapRepository;
use App\Repositories\ContratoRepository;
use App\Repositories\FacturaRepository;
use App\Repositories\EquipoRepository;
use App\Repositories\DireccionServicioRepository;

/**
 * Servicio para la gestión de servicios de clientes.
 */
final class ServicioService extends BaseService
{
    /** @var ServicioRepository */
    private ServicioRepository $servicioRepo;

    /** @var ClienteRepository */
    private ClienteRepository $clienteRepo;

    /** @var PlanRepository */
    private PlanRepository $planRepo;

    /** @var NapRepository */
    private NapRepository $napRepo;

    /** @var ContratoRepository */
    private ContratoRepository $contratoRepo;

    /** @var FacturaRepository */
    private FacturaRepository $facturaRepo;

    /** @var EquipoRepository */
    private EquipoRepository $equipoRepo;

    /** @var DireccionServicioRepository */
    private DireccionServicioRepository $direccionServicioRepo;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->servicioRepo = new ServicioRepository($pdo);
        $this->clienteRepo = new ClienteRepository($pdo);
        $this->planRepo = new PlanRepository($pdo);
        $this->napRepo = new NapRepository($pdo);
        $this->contratoRepo = new ContratoRepository($pdo);
        $this->facturaRepo = new FacturaRepository($pdo);
        $this->equipoRepo = new EquipoRepository($pdo);
        $this->direccionServicioRepo = new DireccionServicioRepository($pdo);
    }

    /**
     * Crea un nuevo servicio.
     *
     * @param array $datos
     * @return int
     * @throws NotFoundException
     */
    public function crear(array $datos): int
    {
        if (!isset($datos['cliente_id']) || !$this->clienteRepo->exists((int) $datos['cliente_id'])) {
            throw new NotFoundException('Cliente no encontrado.');
        }

        if (!isset($datos['plan_id']) || !$this->planRepo->exists((int) $datos['plan_id'])) {
            throw new NotFoundException('Plan no encontrado.');
        }

        if (isset($datos['nap_id']) && !$this->napRepo->exists((int) $datos['nap_id'])) {
            throw new NotFoundException('NAP no encontrada.');
        }

        $datos['estado_comercial'] = 'pendiente';

        return $this->servicioRepo->create($datos);
    }

    /**
     * Actualiza la dirección de un servicio.
     *
     * @param int $servicioId
     * @param array $datosDireccion
     * @return void
     */
    public function actualizarDireccion(int $servicioId, array $datosDireccion): void
    {
        $calle = trim($datosDireccion['calle'] ?? '');
        $latRaw = $datosDireccion['latitud'] ?? null;
        $lonRaw = $datosDireccion['longitud'] ?? null;

        if ($calle === '' && $latRaw === null && $lonRaw === null) {
            return;
        }

        $lat = $latRaw !== null ? (float) str_replace(',', '.', (string) $latRaw) : null;
        $lon = $lonRaw !== null ? (float) str_replace(',', '.', (string) $lonRaw) : null;

        $servicio = $this->servicioRepo->findById($servicioId);
        if (!$servicio) {
            return;
        }

        $dirId = $servicio['direccion_id'] ? (int) $servicio['direccion_id'] : null;

        if ($dirId) {
            $this->direccionServicioRepo->update($dirId, [
                'calle' => $calle ?: null,
                'latitud' => $lat,
                'longitud' => $lon
            ]);
        } else {
            $newDirId = $this->direccionServicioRepo->create([
                'calle' => $calle ?: null,
                'latitud' => $lat,
                'longitud' => $lon
            ]);
            $this->servicioRepo->update($servicioId, ['direccion_id' => $newDirId]);
        }
    }

    /**
     * Actualiza un servicio existente.
     *
     * @param int $id
     * @param array $datos
     * @return void
     * @throws NotFoundException
     * @throws DomainException
     */
    public function actualizar(int $id, array $datos): void
    {
        if (!$this->servicioRepo->exists($id)) {
            throw new NotFoundException('Servicio no encontrado.');
        }

        $dirData = [
            'calle' => $datos['direccion_texto'] ?? null,
            'latitud' => $datos['latitud'] ?? null,
            'longitud' => $datos['longitud'] ?? null,
        ];
        unset($datos['direccion_texto'], $datos['latitud'], $datos['longitud']);

        if (isset($datos['plan_id']) && !$this->planRepo->exists((int) $datos['plan_id'])) {
            throw new NotFoundException('Plan no encontrado.');
        }

        if (isset($datos['nap_id']) && !$this->napRepo->exists((int) $datos['nap_id'])) {
            throw new NotFoundException('NAP no encontrada.');
        }

        $this->transaction(function() use ($id, $datos, $dirData) {
            $this->actualizarDireccion($id, $dirData);
            if (!empty($datos)) {
                $this->servicioRepo->update($id, $datos);
            }
        });
    }

    /**
     * Elimina un servicio si no tiene dependencias financieras o contractuales.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     * @throws DomainException
     */
    public function eliminar(int $id): void
    {
        if (!$this->servicioRepo->exists($id)) {
            throw new NotFoundException('Servicio no encontrado.');
        }

        $equipos = $this->equipoRepo->findByServicioId($id);
        if (!empty($equipos)) {
            throw new DomainException('El servicio tiene equipos asignados; desasígnalos primero.');
        }

        if ($this->facturaRepo->countByServicioId($id) > 0) {
            throw new DomainException('El servicio tiene facturas asociadas, márcalo como retirado en vez de eliminarlo.');
        }

        if ($this->contratoRepo->countByServicioId($id) > 0) {
            throw new DomainException('El servicio tiene contratos asociados, márcalo como retirado en vez de eliminarlo.');
        }

        $this->servicioRepo->delete($id);
    }

    /**
     * Busca un servicio por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->servicioRepo->findById($id);
    }

    /**
     * Obtiene los servicios de un cliente.
     *
     * @param int $clienteId
     * @return array
     */
    public function listarPorCliente(int $clienteId): array
    {
        return $this->servicioRepo->findByClienteId($clienteId);
    }

    /**
     * Cuenta los servicios de un cliente.
     *
     * @param int $clienteId
     * @return int
     */
    public function contarPorCliente(int $clienteId): int
    {
        return $this->servicioRepo->countByClienteId($clienteId);
    }
}