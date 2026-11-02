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
 *
 * B3.6-hotfix: crear() valida que el monto del pago no exceda el saldo
 * pendiente en USD de la factura. La tasa BCV del pago es libre (tasa
 * del día del pago); no se compara con la tasa de la factura porque
 * cada pago se registra a la tasa vigente en su fecha. La diferencia
 * de tasa entre factura y pago es una ganancia/pérdida cambiaria, no
 * un error. Práctica estándar en Splynx, ERP multi-moneda y facturación
 * venezolana.
 *
 * A2-B.1: integra NotificacionService en crear() (si origen='portal_cliente')
 * y actualizar() (si estado cambia a validado/rechazado). Transaccional:
 * si la notificación falla, el cambio de pago hace rollback.
 *
 * A2-B.4.c: crear() valida ownership de `servicio_id` (que el servicio
 * pertenezca al cliente del pago). Cierra un bug latente donde un
 * admin (o un cliente vía portal) podía mandar `cliente_id` de A y
 * `servicio_id` de B. Defensa BOLA/IDOR (OWASP API Security Top 10
 * 2023, API1:2023). El cliente del portal siempre manda el `cliente_id`
 * de su propia sesión, pero el fix protege también el path admin.
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
        $this->pagoRepo = new PagoRepository($pdo);
        $this->facturaRepo = new FacturaRepository($pdo);
        $this->clienteRepo = new ClienteRepository($pdo);
        $this->servicioRepo = new ServicioRepository($pdo);
        $this->metodoRepo = new MetodoPagoRepository($pdo);
        $this->notificacionService = new NotificacionService($pdo);
    }

    /**
     * Crea un nuevo pago.
     *
     * Si `origen='portal_cliente'`, dispara `eventoPagoSubido`: notifica
     * al cliente (confirmación) y a todos los admins activos (excepto
     * al generador, si lo hubiera). En backoffice, no notifica.
     *
     * Transaccional: si la notificación falla, el pago hace rollback.
     *
     * Validaciones de ownership (A2-B.4.c):
     *   - `cliente_id` debe existir.
     *   - `servicio_id` debe existir Y pertenecer al cliente.
     *   - Si `factura_id`, debe existir Y pertenecer al cliente Y al
     *     servicio.
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

        $clienteId = (int) $datos['cliente_id'];

        if (!isset($datos['servicio_id']) || !$this->servicioRepo->exists((int) $datos['servicio_id'])) {
            throw new NotFoundException('Servicio no encontrado.');
        }

        $servicioId = (int) $datos['servicio_id'];

        // A2-B.4.c — ownership de servicio_id: el servicio debe pertenecer
        // al cliente del pago. Cierra BOLA/IDOR.
        $servicio = $this->servicioRepo->findById($servicioId);
        if ($servicio === null || (int) $servicio['cliente_id'] !== $clienteId) {
            throw new ValidationException([
                'servicio_id' => 'El servicio no pertenece al cliente del pago.'
            ]);
        }

        if (!empty($datos['factura_id'])) {
            $factura = $this->facturaRepo->findById((int) $datos['factura_id']);
            if (!$factura) {
                throw new NotFoundException('Factura no encontrada.');
            }

            // Prevención BOLA/IDOR: la factura debe pertenecer al cliente
            // del pago. Se valida vía el servicio de la factura, no vía
            // el cliente_id del pago (que podría estar manipulado).
            $servicioFactura = $this->servicioRepo->findById((int) $factura['servicio_id']);
            if (!$servicioFactura || (int) $servicioFactura['cliente_id'] !== $clienteId) {
                throw new ValidationException(['factura_id' => 'La factura no pertenece al cliente del pago.']);
            }

            if ((int) $factura['servicio_id'] !== $servicioId) {
                throw new ValidationException(['factura_id' => 'La factura no pertenece al servicio indicado.']);
            }

            $totalValidado = $this->pagoRepo->sumValidadosByFacturaId((int) $datos['factura_id']);
            $saldoPendiente = max(0.0, (float) $factura['monto'] - $totalValidado);
            $montoPago = (float) ($datos['monto'] ?? 0);

            if ($montoPago > $saldoPendiente + 0.01) {
                throw new ValidationException([
                    'monto' => sprintf(
                        'El monto del pago ($%.2f) excede el saldo pendiente de la factura ($%.2f). Registra el exceso como pago a cuenta (sin factura) o reduce el monto.',
                        $montoPago,
                        $saldoPendiente
                    )
                ]);
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

        $closure = function () use ($datos) {
            $pagoId = $this->pagoRepo->create($datos);

            if ($datos['origen'] === 'portal_cliente') {
                $this->notificacionService->eventoPagoSubido(
                    $pagoId,
                    (int) $datos['cliente_id'],
                    (float) $datos['monto'],
                    (string) ($datos['moneda_pago'] ?? 'USD')
                );
            }

            return $pagoId;
        };

        if ($this->pdo->inTransaction()) {
            return $closure();
        }
        return $this->transaction($closure);
    }

    /**
     * Actualiza un pago existente y reconcilia las facturas afectadas.
     *
     * Si el estado cambia a `validado` o `rechazado`, notifica al cliente
     * vía `eventoPagoValidado` / `eventoPagoRechazado`. La notificación va
     * dentro de la transacción existente: si falla, la actualización del
     * pago y la reconciliación de la factura hacen rollback.
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

        if (array_key_exists('servicio_id', $datos) && $datos['servicio_id'] !== null && $datos['servicio_id'] !== '') {
            $facturaRefId = array_key_exists('factura_id', $datos)
                ? ($datos['factura_id'] ? (int) $datos['factura_id'] : 0)
                : ($pago['factura_id'] ? (int) $pago['factura_id'] : 0);

            if ($facturaRefId > 0) {
                $facturaRef = $this->facturaRepo->findById($facturaRefId);
                if ($facturaRef && (int) $facturaRef['servicio_id'] !== (int) $datos['servicio_id']) {
                    throw new ValidationException(['servicio_id' => 'El servicio_id no coincide con el servicio de la factura del pago.']);
                }
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

        $this->transaction(function () use ($id, $datos, $pago, $estadoActual, $nuevoEstado, $idUsuarioValido) {
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

            // Notificación al cliente si el estado del pago cambió.
            if ($nuevoEstado !== $estadoActual && in_array($nuevoEstado, ['validado', 'rechazado'], true)) {
                $clienteId = (int) $pago['cliente_id'];
                $monto = (float) $pago['monto'];
                $monedaPago = (string) ($pago['moneda_pago'] ?? 'USD');
                $adminId = (int) ($idUsuarioValido ?? 0);

                if ($nuevoEstado === 'validado') {
                    $this->notificacionService->eventoPagoValidado($id, $clienteId, $monto, $monedaPago, $adminId);
                } else {
                    $motivo = (string) ($datos['motivo_rechazo'] ?? $pago['motivo_rechazo'] ?? 'Sin motivo especificado');
                    $this->notificacionService->eventoPagoRechazado($id, $clienteId, $monto, $monedaPago, $adminId, $motivo);
                }
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