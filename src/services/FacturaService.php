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
 *
 * A2-B.1: integra NotificacionService en crear() (factura emitida) y en
 * materializarVencimientos() (factura vencida). Ambas operaciones son
 * transaccionales: si la notificación falla, el cambio de factura hace
 * rollback.
 */
final class FacturaService extends BaseService
{
    /** @var FacturaRepository */
    private FacturaRepository $facturaRepo;

    /** @var ServicioRepository */
    private ServicioRepository $servicioRepo;

    /** @var PagoRepository */
    private PagoRepository $pagoRepo;

    /** @var NotificacionService */
    private NotificacionService $notificacionService;

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
        $this->notificacionService = new NotificacionService($pdo);
    }

    /**
     * Crea una nueva factura.
     *
     * Notifica al cliente vía `eventoFacturaEmitida`.
     * Transaccional: si la notificación falla, la factura hace rollback.
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

        $closure = function () use ($datos) {
            $facturaId = $this->facturaRepo->create($datos);

            $servicio = $this->servicioRepo->findById((int) $datos['servicio_id']);
            if ($servicio && !empty($servicio['cliente_id'])) {
                $this->notificacionService->eventoFacturaEmitida(
                    $facturaId,
                    (int) $servicio['cliente_id'],
                    (string) ($datos['periodo'] ?? ''),
                    (float) ($datos['monto'] ?? 0),
                    (string) ($datos['moneda'] ?? 'USD')
                );
            }

            return $facturaId;
        };

        if ($this->pdo->inTransaction()) {
            return $closure();
        }
        return $this->transaction($closure);
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
     * Materializa los vencimientos de las facturas pendientes cuya fecha
     * de vencimiento ya pasó.
     *
     * Notifica al cliente vía `eventoFacturaVencida` por cada factura
     * actualizada. La operación completa es transaccional: si la
     * notificación falla en cualquier factura, todas las actualizaciones
     * de este lote hacen rollback y el caller recibe la excepción.
     * Datos consistentes > resiliencia en tesis.
     *
     * @return int Número de facturas actualizadas.
     */
    public function materializarVencimientos(): int
    {
        $vencidas = $this->facturaRepo->findPendientesVencidas();
        if (empty($vencidas)) {
            return 0;
        }

        $closure = function () use ($vencidas) {
            $count = 0;
            foreach ($vencidas as $factura) {
                $this->facturaRepo->update((int) $factura['id'], ['estado' => 'vencida']);

                $servicio = $this->servicioRepo->findById((int) $factura['servicio_id']);
                if ($servicio && !empty($servicio['cliente_id'])) {
                    $this->notificacionService->eventoFacturaVencida(
                        (int) $factura['id'],
                        (int) $servicio['cliente_id'],
                        (string) $factura['periodo'],
                        (float) $factura['monto'],
                        (string) ($factura['moneda'] ?? 'USD')
                    );
                }
                $count++;
            }
            return $count;
        };

        if ($this->pdo->inTransaction()) {
            return $closure();
        }
        return $this->transaction($closure);
    }
}