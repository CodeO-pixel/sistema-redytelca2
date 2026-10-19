<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\NotFoundException;
use App\Exceptions\ConflictException;
use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;
use App\Repositories\FacturaRepository;
use App\Repositories\ServicioRepository;
use App\Repositories\PagoRepository;

/**
 * Servicio para la gestión de facturas.
 */
final class FacturaService extends BaseService
{
    /** @var FacturaRepository */
    private FacturaRepository $facturaRepo;

    /** @var ServicioRepository */
    private ServicioRepository $servicioRepo;

    /** @var PagoRepository */
    private PagoRepository $pagoRepo;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->facturaRepo = new FacturaRepository($pdo);
        $this->servicioRepo = new ServicioRepository($pdo);
        $this->pagoRepo = new PagoRepository($pdo);
    }

    /**
     * Crea una nueva factura.
     *
     * @param array $datos
     * @return int
     * @throws NotFoundException
     * @throws ConflictException
     * @throws ValidationException
     */
    public function crear(array $datos): int
    {
        if (!isset($datos['servicio_id']) || !$this->servicioRepo->exists((int) $datos['servicio_id'])) {
            throw new NotFoundException('Servicio no encontrado.');
        }

        $moneda = $datos['moneda'] ?? 'USD';
        if ($moneda === 'VES') {
            if (!isset($datos['tasa_cambio']) || (float) $datos['tasa_cambio'] <= 0) {
                throw new ValidationException(['tasa_cambio' => 'La tasa de cambio es obligatoria y debe ser mayor a 0 para moneda VES.']);
            }
        } else {
            $datos['tasa_cambio'] = null;
        }

        if (isset($datos['periodo'])) {
            $existente = $this->facturaRepo->findByServicioIdAndPeriodo((int) $datos['servicio_id'], $datos['periodo']);
            if ($existente) {
                throw new ConflictException('Ya existe una factura para este servicio en el periodo indicado.');
            }
        }

        $datos['estado'] = 'pendiente';

        return $this->facturaRepo->create($datos);
    }

    /**
     * Actualiza una factura existente.
     *
     * @param int $id
     * @param array $datos
     * @return void
     * @throws NotFoundException
     * @throws ConflictException
     * @throws ValidationException
     * @throws DomainException
     */
    public function actualizar(int $id, array $datos): void
    {
        $factura = $this->facturaRepo->findById($id);
        if (!$factura) {
            throw new NotFoundException('Factura no encontrada.');
        }

        if (isset($datos['monto']) && (float) $datos['monto'] !== (float) $factura['monto']) {
            $totalValidado = $this->pagoRepo->sumValidadosByFacturaId($id);
            if ($totalValidado > 0) {
                throw new DomainException('No se puede modificar el monto de una factura con pagos validados. Emite una nota de crédito o débito.');
            }
        }

        $servicioId = isset($datos['servicio_id']) ? (int) $datos['servicio_id'] : (int) $factura['servicio_id'];
        $periodo = $datos['periodo'] ?? $factura['periodo'];

        if ($servicioId !== (int) $factura['servicio_id'] || $periodo !== $factura['periodo']) {
            $existente = $this->facturaRepo->findByServicioIdAndPeriodo($servicioId, $periodo);
            if ($existente && (int) $existente['id'] !== $id) {
                throw new ConflictException('Ya existe otra factura para este servicio en el periodo indicado.');
            }
        }

        $moneda = $datos['moneda'] ?? $factura['moneda'];
        if ($moneda === 'VES') {
            $tasa = array_key_exists('tasa_cambio', $datos) ? (float) $datos['tasa_cambio'] : (float) $factura['tasa_cambio'];
            if ($tasa <= 0) {
                throw new ValidationException(['tasa_cambio' => 'La tasa de cambio es obligatoria y debe ser mayor a 0 para moneda VES.']);
            }
        } else {
            $datos['tasa_cambio'] = null;
        }

        $this->facturaRepo->update($id, $datos);
    }

    /**
     * Elimina una factura si no tiene pagos asociados.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     * @throws DomainException
     */
    public function eliminar(int $id): void
    {
        if (!$this->facturaRepo->exists($id)) {
            throw new NotFoundException('Factura no encontrada.');
        }

        $pagos = $this->pagoRepo->findByFacturaId($id);
        if (count($pagos) > 0) {
            throw new DomainException('La factura tiene pagos asociados, anúlala en vez de eliminarla.');
        }

        $this->facturaRepo->delete($id);
    }

    /**
     * Busca una factura por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->facturaRepo->findById($id);
    }

    /**
     * Obtiene las facturas de un servicio.
     *
     * @param int $servicioId
     * @return array
     */
    public function listarPorServicio(int $servicioId): array
    {
        return $this->facturaRepo->findByServicioId($servicioId);
    }

    /**
     * Obtiene las facturas pendientes cuya fecha de vencimiento ya pasó.
     *
     * @return array
     */
    public function listarPendientesVencidas(): array
    {
        return $this->facturaRepo->findPendientesVencidas();
    }

    /**
     * Materializa los vencimientos de las facturas pendientes cuya fecha de vencimiento ya pasó.
     *
     * @return int Número de facturas actualizadas.
     */
    public function materializarVencimientos(): int
    {
        $vencidas = $this->facturaRepo->findPendientesVencidas();
        $count = 0;

        foreach ($vencidas as $factura) {
            $this->facturaRepo->update((int) $factura['id'], ['estado' => 'vencida']);
            $count++;
        }

        return $count;
    }
}