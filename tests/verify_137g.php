<?php
declare(strict_types=1);

/**
 * tests/verify_137g.php
 * Verificación funcional de spec 137g (3 tandas, 15 deudas).
 *
 * Ejecuta los métodos nuevos de los services contra la BD real.
 * Crea datos dummy con prefijo '137G', verifica, y limpia en finally.
 *
 * Uso: php tests/verify_137g.php
 */

require_once __DIR__ . '/../core/Bootstrap.php';

use App\Core\Bootstrap;
use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;
use App\Services\ClienteService;
use App\Services\UsuarioService;
use App\Services\ServicioService;
use App\Services\FacturaService;
use App\Services\PagoService;
use App\Services\PedidoService;
use App\Services\TicketService;
use App\Services\EmpresaService;

$pdo = Bootstrap::init();

$passed = 0;
$failed = 0;
$cleanup = [];
$result = function (string $name, bool $ok, string $detail = '') use (&$passed, &$failed) {
    if ($ok) { $passed++; echo "  ✓ $name\n"; }
    else     { $failed++; echo "  ✗ $name" . ($detail ? " — $detail" : '') . "\n"; }
};

echo "\n▶ Verificación estática de BD\n";
$nTrans = (int) $pdo->query("SELECT COUNT(*) FROM transicion_estado WHERE tabla = 'tickets'")->fetchColumn();
$result('5 filas en transicion_estado para tickets', $nTrans === 5, "encontradas: $nTrans");

$nTrig = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = 'bd_redytelca' AND TRIGGER_NAME IN ('trg_tickets_validar_insert','trg_tickets_validar_transicion')")->fetchColumn();
$result('2 triggers de tickets existen', $nTrig === 2, "encontrados: $nTrig");

echo "\n▶ Setup de datos dummy\n";
try {
    // --- Cliente dummy ---
    $pdo->exec("INSERT INTO clientes (nombre, apellido, cedula, correo, estado) VALUES ('Test137G','Dummy','V-99999001','test137g-dummy@local.test','activo')");
    $clienteId = (int) $pdo->lastInsertId();
    $cleanup[] = fn() => $pdo->exec("DELETE FROM clientes WHERE id = $clienteId");
    echo "  · cliente id=$clienteId\n";

    // --- Usuario dummy (para PedidoService y sesiones) ---
    $hash = password_hash('test137g', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO usuarios (username, password, email, estado) VALUES ('test_137g_user','$hash','test137g-user@local.test','Activo')");
    $usuarioId = (int) $pdo->lastInsertId();
    $cleanup[] = fn() => $pdo->exec("DELETE FROM usuario_rol WHERE usuario_id = $usuarioId");
    $cleanup[] = fn() => $pdo->exec("DELETE FROM usuarios WHERE id = $usuarioId");
    echo "  · usuario id=$usuarioId\n";

    // --- Servicio dummy (plan 1, nap 1 existentes) ---
    // Trigger trg_servicios_validar_insert solo permite INSERT con estado
    // 'pendiente'. Se inserta como pendiente y se transiciona por UPDATE.
    $pdo->exec("INSERT INTO servicios (cliente_id, estado_comercial, plan_id) VALUES ($clienteId, 'pendiente', 1)");
    $servicioId = (int) $pdo->lastInsertId();
    $pdo->exec("UPDATE servicios SET estado_comercial='activo' WHERE id = $servicioId");
    $cleanup[] = fn() => $pdo->exec("DELETE FROM servicios WHERE id = $servicioId");
    echo "  · servicio id=$servicioId\n";

    // --- Equipo dummy asignado al servicio (para ServicioService::eliminar) ---
    $pdo->exec("INSERT INTO equipos (tipo, direccion_mac, puerto_nap, estado_inventario, propiedad, nap_id, servicio_id) VALUES ('COMBO','AA:BB:CC:DD:EE:99',1,'asignado','empresa',1,$servicioId)");
    $equipoId = (int) $pdo->lastInsertId();
    $cleanup[] = fn() => $pdo->exec("DELETE FROM equipos WHERE id = $equipoId");
    echo "  · equipo id=$equipoId\n";

    // --- Factura dummy + pago validado (para FacturaService::actualizar y PagoService) ---
    $pdo->exec("INSERT INTO facturas (servicio_id, periodo, monto, fecha_emision, fecha_vencimiento, estado) VALUES ($servicioId, '2099-01', 100.00, '2099-01-01', '2099-01-31', 'pendiente')");
    $facturaId = (int) $pdo->lastInsertId();
    $cleanup[] = fn() => $pdo->exec("DELETE FROM facturas WHERE id = $facturaId");
    echo "  · factura id=$facturaId\n";

    $pdo->exec("INSERT INTO pagos (factura_id, cliente_id, servicio_id, monto, fecha_pago, estado) VALUES ($facturaId, $clienteId, $servicioId, 100.00, '2099-01-15 10:00:00', 'pendiente')");
    $pagoId = (int) $pdo->lastInsertId();
    $pdo->exec("UPDATE pagos SET estado='validado', fecha_validacion=NOW(), usuario_valido_id=1 WHERE id = $pagoId");
    $cleanup[] = fn() => $pdo->exec("DELETE FROM pagos WHERE id = $pagoId");
    echo "  · pago validado id=$pagoId\n";

    // --- Pedido dummy en estado completado (para PedidoService::asignarTecnico) ---
    $pdo->exec("INSERT INTO pedidos (cliente_id, tipo, estado) VALUES ($clienteId, 'instalacion', 'completado')");
    $pedidoId = (int) $pdo->lastInsertId();
    $cleanup[] = fn() => $pdo->exec("DELETE FROM pedidos WHERE id = $pedidoId");
    echo "  · pedido completado id=$pedidoId\n";

    // --- Ticket dummy en estado Cerrado (para TicketService::actualizar y trigger) ---
    $pdo->exec("INSERT INTO tickets (asunto, estado, cliente_id) VALUES ('Test137G ticket', 'Abierto', $clienteId)");
    $ticketId = (int) $pdo->lastInsertId();
    $pdo->exec("UPDATE tickets SET estado='Cerrado' WHERE id = $ticketId");
    $cleanup[] = fn() => $pdo->exec("DELETE FROM tickets WHERE id = $ticketId");
    echo "  · ticket cerrado id=$ticketId\n";

    echo "\n▶ Tests funcionales (services contra BD real)\n";

    // --- 1. ClienteService::desactivar (D1, deuda 01/02/03) ---
    try {
        (new ClienteService($pdo))->desactivar($clienteId);
        $estado = $pdo->query("SELECT estado FROM clientes WHERE id = $clienteId")->fetchColumn();
        $result('ClienteService::desactivar → estado=inactivo', $estado === 'inactivo', "estado=$estado");
        // restauramos para los siguientes tests
        $pdo->exec("UPDATE clientes SET estado='activo' WHERE id = $clienteId");
    } catch (\Throwable $e) {
        $result('ClienteService::desactivar', false, $e->getMessage());
    }

    // --- 2. UsuarioService::crear sin password (D1, deuda 05) ---
    try {
        (new UsuarioService($pdo))->crear(['username' => 'test_137g_nopass', 'email' => 'nopass@local.test']);
        $result('UsuarioService::crear sin password → ValidationException', false, 'no lanzó excepción');
    } catch (ValidationException $e) {
        $result('UsuarioService::crear sin password → ValidationException', true);
    } catch (\Throwable $e) {
        $result('UsuarioService::crear sin password', false, get_class($e) . ': ' . $e->getMessage());
    }

    // --- 3. ServicioService::eliminar con equipo (deuda 04) ---
    try {
        (new ServicioService($pdo))->eliminar($servicioId);
        $result('ServicioService::eliminar con equipo → DomainException', false, 'no lanzó excepción');
    } catch (DomainException $e) {
        $result('ServicioService::eliminar con equipo → DomainException', true);
    } catch (\Throwable $e) {
        $result('ServicioService::eliminar con equipo', false, get_class($e) . ': ' . $e->getMessage());
    }

    // --- 4. FacturaService::actualizar monto con pago validado (D2, deuda 07/10) ---
    try {
        (new FacturaService($pdo))->actualizar($facturaId, ['monto' => 999.99]);
        $result('FacturaService::actualizar monto con pago validado → DomainException', false, 'no lanzó excepción');
    } catch (DomainException $e) {
        $result('FacturaService::actualizar monto con pago validado → DomainException', true);
    } catch (\Throwable $e) {
        $result('FacturaService::actualizar monto con pago validado', false, get_class($e) . ': ' . $e->getMessage());
    }

    // --- 4b. FacturaService::actualizar sin cambiar monto debe PASAR ---
    try {
        (new FacturaService($pdo))->actualizar($facturaId, ['monto' => 100.00, 'fecha_vencimiento' => '2099-02-28']);
        $result('FacturaService::actualizar mismo monto → pasa', true);
    } catch (\Throwable $e) {
        $result('FacturaService::actualizar mismo monto → pasa', false, $e->getMessage());
    }

    // --- 5. PagoService::crear con factura de otro servicio (deuda 08) ---
    // Creamos un segundo servicio dummy para el mismo cliente.
    // Trigger trg_servicios_validar_insert: INSERT solo permite 'pendiente'.
    $pdo->exec("INSERT INTO servicios (cliente_id, estado_comercial, plan_id) VALUES ($clienteId, 'pendiente', 1)");
    $servicioOtroId = (int) $pdo->lastInsertId();
    $pdo->exec("UPDATE servicios SET estado_comercial='activo' WHERE id = $servicioOtroId");
    $cleanup[] = fn() => $pdo->exec("DELETE FROM servicios WHERE id = $servicioOtroId");
    try {
        (new PagoService($pdo))->crear([
            'cliente_id' => $clienteId,
            'servicio_id' => $servicioOtroId,
            'factura_id' => $facturaId,
            'monto' => 50.00,
            'fecha_pago' => '2099-01-20 10:00:00',
        ]);
        $result('PagoService::crear factura de otro servicio → ValidationException', false, 'no lanzó excepción');
    } catch (ValidationException $e) {
        $result('PagoService::crear factura de otro servicio → ValidationException', true);
    } catch (\Throwable $e) {
        $result('PagoService::crear factura de otro servicio', false, get_class($e) . ': ' . $e->getMessage());
    }

    // --- 6. PagoService::calcularSaldo en factura anulada (deuda 13) ---
    $pdo->exec("UPDATE facturas SET estado='anulada' WHERE id = $facturaId");
    try {
        $saldo = (new PagoService($pdo))->calcularSaldo($facturaId);
        $result('PagoService::calcularSaldo anulada → 0.0', $saldo === 0.0, "saldo=$saldo");
    } catch (\Throwable $e) {
        $result('PagoService::calcularSaldo anulada → 0.0', false, $e->getMessage());
    }
    $pdo->exec("UPDATE facturas SET estado='pendiente' WHERE id = $facturaId");

    // --- 7. PedidoService::asignarTecnico en pedido completado (deuda 09) ---
    try {
        (new PedidoService($pdo))->asignarTecnico($pedidoId, $usuarioId);
        $result('PedidoService::asignarTecnico pedido completado → DomainException', false, 'no lanzó excepción');
    } catch (DomainException $e) {
        $result('PedidoService::asignarTecnico pedido completado → DomainException', true);
    } catch (\Throwable $e) {
        $result('PedidoService::asignarTecnico pedido completado', false, get_class($e) . ': ' . $e->getMessage());
    }

    // --- 8. EmpresaService::actualizar dia_facturacion string '15' (deuda 14) ---
    $diaOriginal = $pdo->query("SELECT dia_facturacion FROM configuracion_empresa WHERE id = 1")->fetchColumn();
    $cleanup[] = fn() => $pdo->exec("UPDATE configuracion_empresa SET dia_facturacion = " . (int) $diaOriginal . " WHERE id = 1");
    try {
        (new EmpresaService($pdo))->actualizar(['dia_facturacion' => '15']);
        $diaDb = (int) $pdo->query("SELECT dia_facturacion FROM configuracion_empresa WHERE id = 1")->fetchColumn();
        $result('EmpresaService::actualizar dia="15" string → acepta', $diaDb === 15, "dia en BD=$diaDb");
    } catch (\Throwable $e) {
        $result('EmpresaService::actualizar dia="15" string → acepta', false, $e->getMessage());
    }

    // --- 9. TicketService::actualizar Cerrado→Abierto (D4, deuda 11) ---
    try {
        (new TicketService($pdo))->actualizar($ticketId, ['estado' => 'Abierto']);
        $result('TicketService::actualizar Cerrado→Abierto → PDOException 1644', false, 'no lanzó excepción');
    } catch (\PDOException $e) {
        $sqlCode = (int) ($e->errorInfo[1] ?? 0);
        $result('TicketService::actualizar Cerrado→Abierto → PDOException 1644', $sqlCode === 1644, "sqlCode=$sqlCode");
    } catch (\Throwable $e) {
        $result('TicketService::actualizar Cerrado→Abierto', false, get_class($e) . ': ' . $e->getMessage());
    }

    // --- 9b. TicketService::actualizar Cerrado→En proceso debe PASAR ---
    try {
        (new TicketService($pdo))->actualizar($ticketId, ['estado' => 'En proceso']);
        $estado = $pdo->query("SELECT estado FROM tickets WHERE id = $ticketId")->fetchColumn();
        $result('TicketService::actualizar Cerrado→En proceso → pasa', $estado === 'En proceso', "estado=$estado");
    } catch (\Throwable $e) {
        $result('TicketService::actualizar Cerrado→En proceso → pasa', false, $e->getMessage());
    }

} catch (\Throwable $e) {
    echo "\n  ✗ SETUP falló: " . $e->getMessage() . "\n";
    $failed++;
} finally {
    echo "\n▶ Teardown\n";
    foreach (array_reverse($cleanup) as $fn) {
        try { $fn(); } catch (\Throwable $e) { echo "  ⚠ cleanup: " . $e->getMessage() . "\n"; }
    }
    echo "  · datos dummy eliminados\n";
}

echo "\n────────────────────────\n";
echo "Resultado: $passed pasaron, $failed fallaron\n";
exit($failed === 0 ? 0 : 1);