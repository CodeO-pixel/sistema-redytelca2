<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\NotFoundException;
use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;
use App\Repositories\PagoRepository;
use App\Repositories\FacturaRepository;
use App\Repositories\ClienteRepository;
use App\Repositories\ServicioRepository;
use App\Repositories\MetodoPagoRepository;

/**
 * Servicio para la gestión de pagos.
 */
final class PagoService extends BaseService
{
    /** @var PagoRepository */
    private PagoRepository $pagoRepo;

    /** @var FacturaRepository */
    private FacturaRepository $facturaRepo;

    /** @var ClienteRepository */
    private ClienteRepository $clienteRepo;

    /** @var ServicioRepository */
    private ServicioRepository $servicioRepo;

    /** @var MetodoPagoRepository */
    private MetodoPagoRepository $metodoRepo;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->pagoRepo = new PagoRepository($pdo);
        $this->facturaRepo = new FacturaRepository($pdo);
        $this->clienteRepo = new ClienteRepository($pdo);
        $this->servicioRepo = new ServicioRepository($pdo);
        $this->metodoRepo = new MetodoPagoRepository($pdo);
    }

    /**
     * Crea un nuevo pago.
     *
     * @param array $datos
     * @return int
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function crear(array $datos): int
    {
        if (!isset($datos['cliente_id']) || !$this->clienteRepo->exists((int) $datos['cliente_id'])) {
            throw new NotFoundException('Cliente no encontrado.');
        }

        if (!isset($datos['servicio_id']) || !$this->servicioRepo->exists((int) $datos['servicio_id'])) {
            throw new NotFoundException('Servicio no encontrado.');
        }

        if (!empty($datos['factura_id'])) {
            $factura = $this->facturaRepo->findById((int) $datos['factura_id']);
            if (!$factura) {
                throw new NotFoundException('Factura no encontrada.');
            }
            // Prevención BOLA/IDOR: la factura debe pertenecer al cliente
            // del pago. Usa findByClienteIdAndFacturaId (136c).
            $pertenece = $this->pagoRepo->findByClienteIdAndFacturaId(
                (int) $datos['cliente_id'],
                (int) $datos['factura_id']
            );
            if (empty($pertenece)) {
                // Verificación alternativa: la factura referencia un servicio
                // que pertenece al cliente del pago.
                $servicioFactura = $this->servicioRepo->findById((int) $factura['servicio_id']);
                if (!$servicioFactura || (int) $servicioFactura['cliente_id'] !== (int) $datos['cliente_id']) {
                    throw new ValidationException(['factura_id' => 'La factura no pertenece al cliente del pago.']);
                }
            }

            if (isset($datos['servicio_id'])) {
                if ((int) $factura['servicio_id'] !== (int) $datos['servicio_id']) {
                    throw new ValidationException(['factura_id' => 'La factura no pertenece al servicio indicado.']);
                }
            }
        }

        if (!empty($datos['metodo_id'])) {
            $metodo = $this->metodoRepo->findById((int) $datos['metodo_id']);
            if (!$metodo || (int) $metodo['activo'] !== 1) {
                throw new ValidationException(['metodo_id' => 'El método de pago no existe o no está activo.']);
            }
        }

        $moneda = $datos['moneda_pago'] ?? 'USD';
        if ($moneda === 'VES') {
            if (!isset($datos['tasa_cambio']) || (float) $datos['tasa_cambio'] <= 0) {
                throw new ValidationException(['tasa_cambio' => 'La tasa de cambio es obligatoria y debe ser mayor a 0 para moneda VES.']);
            }
        } else {
            $datos['tasa_cambio'] = null;
        }

        $datos['estado'] = 'pendiente';

        $origen = $datos['origen'] ?? 'backoffice';
        if (!in_array($origen, ['backoffice', 'portal_cliente'], true)) {
            $origen = 'backoffice';
        }
        $datos['origen'] = $origen;

        return $this->pagoRepo->create($datos);
    }

    /**
     * Actualiza un pago existente y reconcilia las facturas afectadas.
     *
     * @param int $id
     * @param array $datos
     * @param int|null $idUsuarioValido
     * @return void
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function actualizar(int $id, array $datos, ?int $idUsuarioValido = null): void
    {
        $pago = $this->pagoRepo->findById($id);
        if (!$pago) {
            throw new NotFoundException('Pago no encontrado.');
        }

        $estadoActual = $pago['estado'];
        $nuevoEstado = $datos['estado'] ?? $estadoActual;
        if (!in_array($nuevoEstado, ['pendiente', 'validado', 'rechazado'], true)) {
            $nuevoEstado = $estadoActual;
        }
        $datos['estado'] = $nuevoEstado;

        if ($nuevoEstado !== $estadoActual && in_array($nuevoEstado, ['validado', 'rechazado', 'pendiente'], true)) {
            if ($idUsuarioValido === null) {
                throw new ValidationException(['usuario_valido_id' => 'Se requiere el usuario validador para cambiar el estado del pago.']);
            }
            $datos['fecha_validacion'] = date('Y-m-d H:i:s');
            $datos['usuario_valido_id'] = $idUsuarioValido;
        }

        // Prevención BOLA/IDOR: si el caller reasigna la factura, la nueva
        // factura debe pertenecer al mismo cliente del pago.
        if (array_key_exists('factura_id', $datos) && !empty($datos['factura_id'])) {
            $factura = $this->facturaRepo->findById((int) $datos['factura_id']);
            if (!$factura) {
                throw new NotFoundException('Factura no encontrada.');
            }
            $servicioFactura = $this->servicioRepo->findById((int) $factura['servicio_id']);
            if (!$servicioFactura || (int) $servicioFactura['cliente_id'] !== (int) $pago['cliente_id']) {
                throw new ValidationException(['factura_id' => 'La factura no pertenece al cliente del pago.']);
            }
        }

        $moneda = $datos['moneda_pago'] ?? $pago['moneda_pago'];
        if ($moneda === 'VES') {
            $tasa = array_key_exists('tasa_cambio', $datos) ? (float) $datos['tasa_cambio'] : (float) $pago['tasa_cambio'];
            if ($tasa <= 0) {
                throw new ValidationException(['tasa_cambio' => 'La tasa de cambio es obligatoria y debe ser mayor a 0 para moneda VES.']);
            }
        } else {
            $datos['tasa_cambio'] = null;
        }

        $this->transaction(function () use ($id, $datos, $pago) {
            $this->pagoRepo->update($id, $datos);

            $facturaAnteriorId = $pago['factura_id'] ? (int) $pago['factura_id'] : null;
            $facturaNuevaId = array_key_exists('factura_id', $datos)
                ? ($datos['factura_id'] ? (int) $datos['factura_id'] : null)
                : $facturaAnteriorId;

            if ($facturaAnteriorId !== null && $facturaAnteriorId !== $facturaNuevaId) {
                $this->reconciliarFactura($facturaAnteriorId);
            }
            if ($facturaNuevaId !== null) {
                $this->reconciliarFactura($facturaNuevaId);
            }
        });
    }

    /**
     * Elimina un pago si no está validado y reconcilia la factura afectada.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     * @throws DomainException
     */
    public function eliminar(int $id): void
    {
        $pago = $this->pagoRepo->findById($id);
        if (!$pago) {
            throw new NotFoundException('Pago no encontrado.');
        }

        if ($pago['estado'] === 'validado') {
            throw new DomainException('No se puede eliminar un pago validado; márcalo como rechazado.');
        }

        $this->transaction(function () use ($id, $pago) {
            $facturaId = $pago['factura_id'] ? (int) $pago['factura_id'] : null;
            $this->pagoRepo->delete($id);

            if ($facturaId !== null) {
                $this->reconciliarFactura($facturaId);
            }
        });
    }

    /**
     * Busca un pago por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->pagoRepo->findById($id);
    }

    /**
     * Obtiene los pagos de un cliente.
     *
     * @param int $clienteId
     * @return array
     */
    public function listarPorCliente(int $clienteId): array
    {
        return $this->pagoRepo->findByClienteId($clienteId);
    }

    /**
     * Obtiene los pagos de una factura.
     *
     * @param int $facturaId
     * @return array
     */
    public function listarPorFactura(int $facturaId): array
    {
        return $this->pagoRepo->findByFacturaId($facturaId);
    }

    /**
     * Calcula el saldo pendiente de una factura.
     *
     * @param int $facturaId
     * @return float
     * @throws NotFoundException
     */
    public function calcularSaldo(int $facturaId): float
    {
        $factura = $this->facturaRepo->findById($facturaId);
        if (!$factura) {
            throw new NotFoundException('Factura no encontrada.');
        }

        if ($factura['estado'] === 'anulada') {
            return 0.0;
        }

        $totalValidado = $this->pagoRepo->sumValidadosByFacturaId($facturaId);
        return max(0.0, (float) $factura['monto'] - $totalValidado);
    }

    /**
     * Método privado. Replica reconciliarFactura() de api_pagos.php.
     * No se llama desde fuera del service.
     *
     * @param int $facturaId
     * @return void
     */
    private function reconciliarFactura(int $facturaId): void
    {
        $factura = $this->facturaRepo->findById($facturaId);
        if (!$factura || $factura['estado'] === 'anulada') {
            return;
        }

        $totalValidado = $this->pagoRepo->sumValidadosByFacturaId($facturaId);
        $monto = (float) $factura['monto'];

        if ($totalValidado >= $monto && $monto > 0) {
            $nuevoEstado = 'pagada';
        } elseif ($totalValidado > 0) {
            $nuevoEstado = 'parcial';
        } else {
            $nuevoEstado = ($factura['estado'] === 'vencida') ? 'vencida' : 'pendiente';
        }

        if ($nuevoEstado !== $factura['estado']) {
            $this->facturaRepo->update($facturaId, ['estado' => $nuevoEstado]);
        }
    }
}