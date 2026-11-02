<?php
// tests/verify_f231.php
// Verificación funcional de F2.31 contra la BD configurada.
// Requiere migración y tasa disponible. Ejecutar sin el job BCV concurrente;
// limpia los datos dummy y restaura el estado exacto de la tasa.
// Los registros de auditoría son inmutables; preferir una BD de desarrollo.
//
// Uso: php src/tests/verify_f231.php
declare(strict_types=1);

require_once __DIR__ . '/../core/Bootstrap.php';

use App\Core\Bootstrap;
use App\Exceptions\ServiceUnavailableException;
use App\Repositories\TasaBcvRepository;
use App\Services\PagoService;
use App\Services\PortalFacturaService;
use App\Services\PortalPagoService;
use App\Services\TasaBcvService;

$pdo = Bootstrap::init();
$passed = 0;
$failed = 0;
$cleanup = [];
$result = function (string $name, bool $ok, string $detail = '') use (&$passed, &$failed) {
    if ($ok) {
        $passed++;
        echo "  ✓ $name\n";
    } else {
        $failed++;
        echo "  ✗ $name" . ($detail !== '' ? " — $detail" : '') . "\n";
    }
};

$clienteId = null;
$otroClienteId = null;
$servicioId = null;
$otroServicioId = null;
$facturaId = null;
$facturaUsdId = null;
$facturaOtroClienteId = null;
$pagoId = null;
$pagoUsdId = null;
$tasaRepo = new TasaBcvRepository($pdo);
$tasaOriginal = $tasaRepo->obtenerDisponible();
$tasaRegistroOriginal = $tasaRepo->obtenerUltima();
$tasaMarcadaFallida = false;

if ($tasaOriginal === null) {
    fwrite(STDERR, "No hay una tasa BCV disponible. Ejecuta primero scripts/update_bcv_rate.php.\n");
    exit(1);
}

try {
    $planId = (int) $pdo->query("SELECT id FROM planes WHERE estado = 'activo' ORDER BY id LIMIT 1")->fetchColumn();
    $metodoId = (int) $pdo->query("SELECT id FROM metodos_pago WHERE activo = 1 AND requiere_referencia = 0 ORDER BY id LIMIT 1")->fetchColumn();
    $adminId = (int) $pdo->query("
        SELECT u.id
        FROM usuarios u
        INNER JOIN usuario_rol ur ON ur.usuario_id = u.id
        WHERE ur.rol_id = 1 AND u.estado = 'Activo'
        ORDER BY u.id
        LIMIT 1
    ")->fetchColumn();
    if ($planId <= 0 || $metodoId <= 0 || $adminId <= 0) {
        throw new RuntimeException('Falta un plan activo, método de pago activo o administrador activo para la prueba.');
    }

    $suffix = bin2hex(random_bytes(4));
    $stmt = $pdo->prepare("
        INSERT INTO clientes (nombre, apellido, cedula, correo, estado)
        VALUES (?, 'Prueba', ?, ?, 'activo')
    ");
    $stmt->execute(['F231', 'V-' . random_int(100000000, 999999999), 'f231-' . $suffix . '@local.test']);
    $clienteId = (int) $pdo->lastInsertId();
    $cleanup[] = static function () use ($pdo, $clienteId): void {
        $stmt = $pdo->prepare('DELETE FROM notificaciones WHERE cliente_id = ?');
        $stmt->execute([$clienteId]);
        $stmt = $pdo->prepare('DELETE FROM clientes WHERE id = ?');
        $stmt->execute([$clienteId]);
    };

    $stmt = $pdo->prepare("INSERT INTO servicios (cliente_id, estado_comercial, plan_id) VALUES (?, 'pendiente', ?)");
    $stmt->execute([$clienteId, $planId]);
    $servicioId = (int) $pdo->lastInsertId();
    $pdo->prepare("UPDATE servicios SET estado_comercial = 'activo' WHERE id = ?")->execute([$servicioId]);
    $cleanup[] = static function () use ($pdo, $servicioId): void {
        $pdo->prepare('DELETE FROM servicios WHERE id = ?')->execute([$servicioId]);
    };

    $stmt = $pdo->prepare("
        INSERT INTO facturas (servicio_id, periodo, monto, moneda, fecha_emision, fecha_vencimiento, estado)
        VALUES (?, ?, 50.00, 'USD', '2099-01-01', '2099-01-31', 'pendiente')
    ");
    $stmt->execute([$servicioId, sprintf('2099-%02d', random_int(1, 12))]);
    $facturaId = (int) $pdo->lastInsertId();
    $cleanup[] = static function () use ($pdo, $facturaId): void {
        $pdo->prepare('DELETE FROM facturas WHERE id = ?')->execute([$facturaId]);
    };

    $stmt = $pdo->prepare("
        INSERT INTO facturas (servicio_id, periodo, monto, moneda, fecha_emision, fecha_vencimiento, estado)
        VALUES (?, '2098-01', 15.25, 'USD', '2098-01-01', '2098-01-31', 'pendiente')
    ");
    $stmt->execute([$servicioId]);
    $facturaUsdId = (int) $pdo->lastInsertId();
    $cleanup[] = static function () use ($pdo, $facturaUsdId): void {
        $pdo->prepare('DELETE FROM facturas WHERE id = ?')->execute([$facturaUsdId]);
    };

    $stmt = $pdo->prepare("
        INSERT INTO clientes (nombre, apellido, cedula, correo, estado)
        VALUES ('F231 Otro', 'Prueba', ?, ?, 'activo')
    ");
    $suffixOtro = bin2hex(random_bytes(4));
    $stmt->execute(['V-' . random_int(100000000, 999999999), 'f231-otro-' . $suffixOtro . '@local.test']);
    $otroClienteId = (int) $pdo->lastInsertId();
    $cleanup[] = static function () use ($pdo, $otroClienteId): void {
        $pdo->prepare('DELETE FROM clientes WHERE id = ?')->execute([$otroClienteId]);
    };

    $stmt = $pdo->prepare("INSERT INTO servicios (cliente_id, estado_comercial, plan_id) VALUES (?, 'pendiente', ?)");
    $stmt->execute([$otroClienteId, $planId]);
    $otroServicioId = (int) $pdo->lastInsertId();
    $pdo->prepare("UPDATE servicios SET estado_comercial = 'activo' WHERE id = ?")->execute([$otroServicioId]);
    $cleanup[] = static function () use ($pdo, $otroServicioId): void {
        $pdo->prepare('DELETE FROM servicios WHERE id = ?')->execute([$otroServicioId]);
    };

    $stmt = $pdo->prepare("
        INSERT INTO facturas (servicio_id, periodo, monto, moneda, fecha_emision, fecha_vencimiento, estado)
        VALUES (?, '2098-02', 20.00, 'USD', '2098-02-01', '2098-02-28', 'pendiente')
    ");
    $stmt->execute([$otroServicioId]);
    $facturaOtroClienteId = (int) $pdo->lastInsertId();
    $cleanup[] = static function () use ($pdo, $facturaOtroClienteId): void {
        $pdo->prepare('DELETE FROM facturas WHERE id = ?')->execute([$facturaOtroClienteId]);
    };

    $datosPago = [
        'cliente_id' => 999999,
        'servicio_id' => $servicioId,
        'factura_id' => $facturaId,
        'monto' => '5.00',
        'moneda_pago' => 'VES',
        'tasa_cambio' => '1.00000000',
        'tasa_fecha_valor' => '2000-01-01',
        'fecha_pago' => gmdate('Y-m-d H:i:s'),
        'metodo_id' => $metodoId,
        'origen' => 'backoffice',
        'estado' => 'validado',
    ];

    $portalPagoService = new PortalPagoService($pdo);

    $tasaRepo->marcarConsultaFallida();
    $tasaMarcadaFallida = true;
    try {
        $portalPagoService->crear($clienteId, $datosPago, 'uploads/pagos/f231-fail.png');
        $result('La indisponibilidad BCV rechaza nuevos pagos VES con HTTP 503', false, 'no se lanzó excepción');
    } catch (ServiceUnavailableException $e) {
        $result('La indisponibilidad BCV rechaza nuevos pagos VES con HTTP 503', $e->getHttpStatus() === 503);
        $detalleSinTasa = (new PortalFacturaService($pdo))->detalle($facturaId, $clienteId);
        $result(
            'La cotización VES de factura queda nula cuando BCV está indisponible',
            $detalleSinTasa !== null
                && $detalleSinTasa['factura']['saldo_ves'] === null
                && $detalleSinTasa['factura']['tasa_bcv'] === null
        );

        $datosPagoUsd = array_merge($datosPago, [
            'factura_id' => $facturaUsdId,
            'moneda_pago' => 'USD',
        ]);
        try {
            $pagoUsd = $portalPagoService->crear($clienteId, $datosPagoUsd, 'uploads/pagos/f231-usd.png');
            $pagoUsdId = $pagoUsd['id'];
            $cleanup[] = static function () use ($pdo, $pagoUsdId): void {
                $stmt = $pdo->prepare('DELETE FROM notificaciones_staff WHERE url_destino = ?');
                $stmt->execute(['/pagos?id=' . $pagoUsdId]);
                $pdo->prepare('DELETE FROM pagos WHERE id = ?')->execute([$pagoUsdId]);
            };
            $result(
                'Se puede pagar en USD aunque BCV esté indisponible',
                $pagoUsd['monto_usd'] === '15.25'
                    && $pagoUsd['monto_moneda'] === '15.25'
                    && $pagoUsd['tasa_cambio'] === null
                    && $pagoUsd['tasa_fecha_valor'] === null
            );
        } catch (\Throwable $e) {
            $result('Se puede pagar en USD aunque BCV esté indisponible', false, get_class($e) . ': ' . $e->getMessage());
        }
    } finally {
        $stmt = $pdo->prepare('UPDATE tasas_bcv SET consultado_en = ?, estado_consulta = ? WHERE id = ?');
        $stmt->execute([
            $tasaRegistroOriginal['consultado_en'],
            $tasaRegistroOriginal['estado_consulta'],
            $tasaRegistroOriginal['id'],
        ]);
        $tasaMarcadaFallida = false;
    }

    $cotizacion = (new PortalFacturaService($pdo))->detalle($facturaId, $clienteId);
    $saldoVesEsperado = TasaBcvService::convertirUsdAVes(50.00, $tasaOriginal['tasa']);
    $result(
        'La factura expone saldo USD y cotización VES calculada en el servidor',
        $cotizacion !== null
            && $cotizacion['factura']['saldo_usd'] === '50.00'
            && $cotizacion['factura']['saldo_ves'] === $saldoVesEsperado
            && $cotizacion['factura']['tasa_bcv'] === $tasaOriginal['tasa']
            && $cotizacion['factura']['tasa_fecha_valor'] === $tasaOriginal['fecha_valor']
    );
    $facturasPortal = (new PortalFacturaService($pdo))->listar($clienteId, 1, 100);
    $facturaLista = null;
    foreach ($facturasPortal['items'] as $facturaItem) {
        if ((int) $facturaItem['id'] === $facturaId) {
            $facturaLista = $facturaItem;
            break;
        }
    }
    $result(
        'La lista de facturas publica la misma cotización oficial',
        $facturaLista !== null
            && $facturaLista['saldo_usd'] === '50.00'
            && $facturaLista['saldo_ves'] === $saldoVesEsperado
            && $facturaLista['tasa_bcv'] === $tasaOriginal['tasa']
            && $facturaLista['tasa_fecha_valor'] === $tasaOriginal['fecha_valor']
    );

    try {
        $portalPagoService->crear(
            $clienteId,
            array_merge($datosPago, ['factura_id' => $facturaOtroClienteId]),
            'uploads/pagos/f231-ownership.png'
        );
        $result('No se puede asociar un pago a la factura de otro cliente', false, 'no se rechazó factura ajena');
    } catch (\App\Exceptions\NotFoundException $e) {
        $result('No se puede asociar un pago a la factura de otro cliente', true);
    }

    $pagoCreado = $portalPagoService->crear($clienteId, $datosPago, 'uploads/pagos/f231-test.png');
    $pagoId = $pagoCreado['id'];
    $cleanup[] = static function () use ($pdo, $pagoId): void {
        $stmt = $pdo->prepare('DELETE FROM notificaciones_staff WHERE url_destino = ?');
        $stmt->execute(['/pagos?id=' . $pagoId]);
        $pdo->prepare('DELETE FROM pagos WHERE id = ?')->execute([$pagoId]);
    };

    $stmt = $pdo->prepare('SELECT cliente_id, factura_id, monto, moneda_pago, tasa_cambio, tasa_fecha_valor, estado, origen FROM pagos WHERE id = ?');
    $stmt->execute([$pagoId]);
    $pago = $stmt->fetch(\PDO::FETCH_ASSOC);
    $snapshotCorrecto = $pago
        && (int) $pago['cliente_id'] === $clienteId
        && (int) $pago['factura_id'] === $facturaId
        && $pago['monto'] === '50.00'
        && $pago['moneda_pago'] === 'VES'
        && $pago['tasa_cambio'] === $tasaOriginal['tasa']
        && $pago['tasa_fecha_valor'] === $tasaOriginal['fecha_valor']
        && $pago['estado'] === 'pendiente'
        && $pago['origen'] === 'portal_cliente';
    $result(
        'El pago VES ignora la tasa del cliente y congela la tasa/Fecha Valor vigentes',
        (bool) $snapshotCorrecto && $pagoCreado['monto_moneda'] === $saldoVesEsperado,
        $pago ? json_encode(['pago' => $pago, 'resultado' => $pagoCreado], JSON_UNESCAPED_UNICODE) : 'pago no encontrado'
    );
    $pagoEnPortal = $portalPagoService->detalle($pagoId, $clienteId);
    $pagosPortal = $portalPagoService->listar($clienteId, 1, 100);
    $pagoLista = null;
    foreach ($pagosPortal['items'] as $pagoItem) {
        if ((int) $pagoItem['id'] === $pagoId) {
            $pagoLista = $pagoItem;
            break;
        }
    }
    $result(
        'Detalle y lista de pagos derivan VES del snapshot guardado',
        $pagoEnPortal !== null
            && $pagoEnPortal['monto_moneda'] === $saldoVesEsperado
            && $pagoLista !== null
            && $pagoLista['monto_moneda'] === $saldoVesEsperado
    );

    try {
        $portalPagoService->crear($clienteId, $datosPago, 'uploads/pagos/f231-duplicado.png');
        $result('Una factura con pago pendiente no admite un segundo comprobante', false, 'no se rechazó el duplicado');
    } catch (\App\Exceptions\DomainException $e) {
        $result('Una factura con pago pendiente no admite un segundo comprobante', true);
    }

    (new PagoService($pdo))->actualizar($pagoId, ['estado' => 'validado'], $adminId);
    $stmt->execute([$pagoId]);
    $pagoValidado = $stmt->fetch(\PDO::FETCH_ASSOC);
    $snapshotConservado = $pagoValidado
        && $pagoValidado['tasa_cambio'] === $tasaOriginal['tasa']
        && $pagoValidado['tasa_fecha_valor'] === $tasaOriginal['fecha_valor']
        && $pagoValidado['estado'] === 'validado';
    $result(
        'La validación manual conserva la tasa/Fecha Valor originales',
        (bool) $snapshotConservado,
        $pagoValidado ? json_encode($pagoValidado, JSON_UNESCAPED_UNICODE) : 'pago no encontrado'
    );
} catch (\Throwable $e) {
    $result('Ejecución funcional F2.31', false, get_class($e) . ': ' . $e->getMessage());
} finally {
    if ($tasaMarcadaFallida) {
        try {
            $stmt = $pdo->prepare('UPDATE tasas_bcv SET consultado_en = ?, estado_consulta = ? WHERE id = ?');
            $stmt->execute([
                $tasaRegistroOriginal['consultado_en'],
                $tasaRegistroOriginal['estado_consulta'],
                $tasaRegistroOriginal['id'],
            ]);
        } catch (\Throwable $e) {
            $failed++;
            fwrite(STDERR, '[BCV] No se pudo restaurar la disponibilidad original: ' . get_class($e) . "\n");
        }
    }

    foreach (array_reverse($cleanup) as $cleanupFn) {
        try {
            $cleanupFn();
        } catch (\Throwable $e) {
            $failed++;
            fwrite(STDERR, '[F2.31] Falló la limpieza de datos dummy: ' . get_class($e) . "\n");
        }
    }
}

echo "\nResultado F2.31: $passed pasaron, $failed fallaron\n";
exit($failed === 0 ? 0 : 1);
