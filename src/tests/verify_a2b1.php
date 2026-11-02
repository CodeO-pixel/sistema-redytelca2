<?php
// tests/verify_a2b1.php
// Verificación funcional de A2-B.1 (notificaciones backend).
//
// Ejecuta el flujo end-to-end de las 7 integraciones contra la BD real.
// Crea datos dummy con prefijo 'A2B1', verifica, limpia en finally.
//
// Uso: php src/tests/verify_a2b1.php
declare(strict_types=1);

require_once __DIR__ . '/../core/Bootstrap.php';

use App\Core\Bootstrap;
use App\Repositories\NotificacionRepository;
use App\Repositories\NotificacionStaffRepository;
use App\Services\NotificacionService;

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
        echo "  ✗ $name" . ($detail ? " — $detail" : '') . "\n";
    }
};

echo "\n▶ Setup de datos dummy\n";

try {
    // Cliente dummy
    $pdo->exec("INSERT INTO clientes (nombre, apellido, cedula, correo, estado) VALUES ('A2B1','Test','V-88000001','a2b1-test@local.test','activo')");
    $clienteId = (int) $pdo->lastInsertId();
    $cleanup[] = fn() => $pdo->exec("DELETE FROM clientes WHERE id = $clienteId");

    // Admin dummy (para que exista al menos un rol 1 activo además del seed)
    $hash = password_hash('a2b1', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO usuarios (username, password, email, estado) VALUES ('a2b1_admin','$hash','a2b1-admin@local.test','Activo')");
    $adminId = (int) $pdo->lastInsertId();
    $pdo->exec("INSERT INTO usuario_rol (usuario_id, rol_id) VALUES ($adminId, 1)");
    $cleanup[] = fn() => $pdo->exec("DELETE FROM usuario_rol WHERE usuario_id = $adminId");
    $cleanup[] = fn() => $pdo->exec("DELETE FROM usuarios WHERE id = $adminId");

    // Servicio dummy (para facturas)
    $pdo->exec("INSERT INTO servicios (cliente_id, estado_comercial, plan_id) VALUES ($clienteId, 'pendiente', 1)");
    $servicioId = (int) $pdo->lastInsertId();
    $pdo->exec("UPDATE servicios SET estado_comercial='activo' WHERE id = $servicioId");
    $cleanup[] = fn() => $pdo->exec("DELETE FROM servicios WHERE id = $servicioId");

    // Ticket dummy
    $pdo->exec("INSERT INTO tickets (asunto, estado, cliente_id, origen) VALUES ('A2B1 ticket','Abierto',$clienteId,'backoffice')");
    $ticketId = (int) $pdo->lastInsertId();
    $cleanup[] = fn() => $pdo->exec("DELETE FROM tickets WHERE id = $ticketId");

    // Factura dummy
    $pdo->exec("INSERT INTO facturas (servicio_id, periodo, monto, moneda, fecha_emision, fecha_vencimiento, estado) VALUES ($servicioId, '2099-01', 50.00, 'USD', '2099-01-01', '2099-01-31', 'pendiente')");
    $facturaId = (int) $pdo->lastInsertId();
    $cleanup[] = fn() => $pdo->exec("DELETE FROM facturas WHERE id = $facturaId");

    // Pago dummy
    $pdo->exec("INSERT INTO pagos (cliente_id, servicio_id, factura_id, monto, moneda_pago, fecha_pago, estado, origen) VALUES ($clienteId, $servicioId, $facturaId, 10.00, 'USD', '2099-01-15 10:00:00', 'pendiente', 'backoffice')");
    $pagoId = (int) $pdo->lastInsertId();
    $cleanup[] = fn() => $pdo->exec("DELETE FROM pagos WHERE id = $pagoId");

    echo "  · cliente=$clienteId admin=$adminId servicio=$servicioId ticket=$ticketId factura=$facturaId pago=$pagoId\n";

    $notifSvc = new NotificacionService($pdo);
    $notifStaffRepo = new NotificacionStaffRepository($pdo);
    $notifClienteRepo = new NotificacionRepository($pdo);

    echo "\n▶ Tests\n";

    // ─────────────────────────────────────────────────────────────
    // 1. notificar() directo → staff
    // ─────────────────────────────────────────────────────────────
    try {
        $id = $notifSvc->notificar('test_evento', 'staff', $adminId, [
            'titulo' => 'Test staff', 'mensaje' => 'Mensaje staff', 'url_destino' => '/test',
        ]);
        $row = $notifStaffRepo->findById($id);
        $ok = $row && (int) $row['usuario_id'] === $adminId
            && $row['tipo_evento'] === 'test_evento'
            && $row['estado'] === 'no leido'
            && $row['leida_en'] === null;
        $result('notificar() staff directo', $ok, $row ? 'fila creada pero campos incorrectos' : 'fila no creada');
    } catch (\Throwable $e) {
        $result('notificar() staff directo', false, get_class($e) . ': ' . $e->getMessage());
    }

    // ─────────────────────────────────────────────────────────────
    // 2. notificar() directo → cliente
    // ─────────────────────────────────────────────────────────────
    try {
        $id = $notifSvc->notificar('test_evento', 'cliente', $clienteId, [
            'titulo' => 'Test cliente', 'mensaje' => 'Mensaje cliente',
        ]);
        $row = $notifClienteRepo->findById($id);
        $ok = $row && (int) $row['cliente_id'] === $clienteId
            && $row['estado'] === 'no leido'
            && $row['leida_en'] === null;
        $result('notificar() cliente directo', $ok);
    } catch (\Throwable $e) {
        $result('notificar() cliente directo', false, $e->getMessage());
    }

    // ─────────────────────────────────────────────────────────────
    // 3. eventoTicketCreado (portal)
    // ─────────────────────────────────────────────────────────────
    try {
        $notifSvc->eventoTicketCreado($ticketId, $clienteId, 'A2B1 ticket', $adminId);
        $cntStaff = $notifStaffRepo->countNoLeidasByUsuarioId($adminId);
        $cntCliente = $notifClienteRepo->countNoLeidasByClienteId($clienteId);
        // El admin generador NO debe recibir notificación de su propio ticket.
        // Como el admin dummy es el único admin activo (además del seed id=1),
        // y el generador es $adminId, el seed id=1 SÍ debe recibir una.
        $rowSeed = $pdo->query("SELECT COUNT(*) FROM notificaciones_staff WHERE usuario_id = 1 AND tipo_evento = 'ticket_creado'")->fetchColumn();
        $ok = $cntCliente >= 1 && (int) $rowSeed >= 1;
        $result('eventoTicketCreado (portal) → cliente + admin distinto del generador', $ok,
            "cliente_no_leidas=$cntCliente admin_no_leidas=$cntStaff seed_admins=$rowSeed");
    } catch (\Throwable $e) {
        $result('eventoTicketCreado', false, $e->getMessage());
    }

    // ─────────────────────────────────────────────────────────────
    // 4. eventoTicketCerrado
    // ─────────────────────────────────────────────────────────────
    try {
        $before = $notifClienteRepo->countNoLeidasByClienteId($clienteId);
        $notifSvc->eventoTicketCerrado($ticketId, $clienteId, 'A2B1 ticket');
        $after = $notifClienteRepo->countNoLeidasByClienteId($clienteId);
        $result('eventoTicketCerrado → +1 al cliente', $after === $before + 1, "before=$before after=$after");
    } catch (\Throwable $e) {
        $result('eventoTicketCerrado', false, $e->getMessage());
    }

    // ─────────────────────────────────────────────────────────────
    // 5. eventoTicketRespondido
    // ─────────────────────────────────────────────────────────────
    try {
        $before = $notifClienteRepo->countNoLeidasByClienteId($clienteId);
        $notifSvc->eventoTicketRespondido($ticketId, $clienteId, 'A2B1 ticket', 'Probando respuesta', 1);
        $after = $notifClienteRepo->countNoLeidasByClienteId($clienteId);
        $result('eventoTicketRespondido → +1 al cliente', $after === $before + 1, "before=$before after=$after");
    } catch (\Throwable $e) {
        $result('eventoTicketRespondido', false, $e->getMessage());
    }

    // ─────────────────────────────────────────────────────────────
    // 6. markAsRead idempotente
    // ─────────────────────────────────────────────────────────────
    try {
        $notif = $pdo->query("SELECT id FROM notificaciones WHERE cliente_id = $clienteId AND leida_en IS NULL LIMIT 1")->fetch();
        if (!$notif) {
            $result('markAsRead idempotente', false, 'no hay notificación no leída');
        } else {
            $nid = (int) $notif['id'];
            $r1 = $notifClienteRepo->markAsRead($nid, $clienteId);
            $row1 = $notifClienteRepo->findById($nid);
            $t1 = $row1['leida_en'];
            sleep(1);
            $r2 = $notifClienteRepo->markAsRead($nid, $clienteId);
            $row2 = $notifClienteRepo->findById($nid);
            $t2 = $row2['leida_en'];
            $ok = $r1 && $r2 && $t1 === $t2 && $row2['estado'] === 'leido';
            $result('markAsRead idempotente (leida_en no cambia)', $ok,
                "r1=$r1 r2=$r2 t1=$t1 t2=$t2 estado={$row2['estado']}");
        }
    } catch (\Throwable $e) {
        $result('markAsRead idempotente', false, $e->getMessage());
    }

    // ─────────────────────────────────────────────────────────────
    // 7. markAllAsReadByClienteId
    // ─────────────────────────────────────────────────────────────
    try {
        $before = $notifClienteRepo->countNoLeidasByClienteId($clienteId);
        $marked = $notifClienteRepo->markAllAsReadByClienteId($clienteId);
        $after = $notifClienteRepo->countNoLeidasByClienteId($clienteId);
        $ok = $after === 0 && $marked >= 1;
        $result('markAllAsReadByClienteId → 0 no leídas', $ok,
            "before=$before marked=$marked after=$after");
    } catch (\Throwable $e) {
        $result('markAllAsReadByClienteId', false, $e->getMessage());
    }

    // ─────────────────────────────────────────────────────────────
    // 8. markAsRead de otro cliente → false (no filtra existencia)
    // ─────────────────────────────────────────────────────────────
    try {
        $notif = $pdo->query("SELECT id FROM notificaciones WHERE cliente_id = $clienteId LIMIT 1")->fetch();
        $nid = (int) $notif['id'];
        $wrongCliente = 99999;
        $r = $notifClienteRepo->markAsRead($nid, $wrongCliente);
        $result('markAsRead de otro cliente → false', $r === false);
    } catch (\Throwable $e) {
        $result('markAsRead de otro cliente', false, $e->getMessage());
    }

    // ─────────────────────────────────────────────────────────────
    // 9. eventoPagoSubido (portal)
    // ─────────────────────────────────────────────────────────────
    try {
        $before = $notifClienteRepo->countNoLeidasByClienteId($clienteId);
        $notifSvc->eventoPagoSubido($pagoId, $clienteId, 10.00, 'USD', null);
        $after = $notifClienteRepo->countNoLeidasByClienteId($clienteId);
        $result('eventoPagoSubido → +1 al cliente', $after === $before + 1, "before=$before after=$after");
    } catch (\Throwable $e) {
        $result('eventoPagoSubido', false, $e->getMessage());
    }

    // ─────────────────────────────────────────────────────────────
    // 10. eventoPagoValidado
    // ─────────────────────────────────────────────────────────────
    try {
        $before = $notifClienteRepo->countNoLeidasByClienteId($clienteId);
        $notifSvc->eventoPagoValidado($pagoId, $clienteId, 10.00, 'USD', 1);
        $after = $notifClienteRepo->countNoLeidasByClienteId($clienteId);
        $result('eventoPagoValidado → +1 al cliente', $after === $before + 1, "before=$before after=$after");
    } catch (\Throwable $e) {
        $result('eventoPagoValidado', false, $e->getMessage());
    }

    // ─────────────────────────────────────────────────────────────
    // 11. eventoPagoRechazado
    // ─────────────────────────────────────────────────────────────
    try {
        $before = $notifClienteRepo->countNoLeidasByClienteId($clienteId);
        $notifSvc->eventoPagoRechazado($pagoId, $clienteId, 10.00, 'USD', 1, 'Motivo de prueba');
        $after = $notifClienteRepo->countNoLeidasByClienteId($clienteId);
        $result('eventoPagoRechazado → +1 al cliente', $after === $before + 1, "before=$before after=$after");
    } catch (\Throwable $e) {
        $result('eventoPagoRechazado', false, $e->getMessage());
    }

    // ─────────────────────────────────────────────────────────────
    // 12. eventoFacturaEmitida
    // ─────────────────────────────────────────────────────────────
    try {
        $before = $notifClienteRepo->countNoLeidasByClienteId($clienteId);
        $notifSvc->eventoFacturaEmitida($facturaId, $clienteId, '2099-01', 50.00, 'USD');
        $after = $notifClienteRepo->countNoLeidasByClienteId($clienteId);
        $result('eventoFacturaEmitida → +1 al cliente', $after === $before + 1, "before=$before after=$after");
    } catch (\Throwable $e) {
        $result('eventoFacturaEmitida', false, $e->getMessage());
    }

    // ─────────────────────────────────────────────────────────────
    // 13. eventoFacturaVencida
    // ─────────────────────────────────────────────────────────────
    try {
        $before = $notifClienteRepo->countNoLeidasByClienteId($clienteId);
        $notifSvc->eventoFacturaVencida($facturaId, $clienteId, '2099-01', 50.00, 'USD');
        $after = $notifClienteRepo->countNoLeidasByClienteId($clienteId);
        $result('eventoFacturaVencida → +1 al cliente', $after === $before + 1, "before=$before after=$after");
    } catch (\Throwable $e) {
        $result('eventoFacturaVencida', false, $e->getMessage());
    }

    // ─────────────────────────────────────────────────────────────
    // 14. auditoria_logs tiene registros de notificaciones_staff
    // ─────────────────────────────────────────────────────────────
    try {
        $cnt = (int) $pdo->query("SELECT COUNT(*) FROM auditoria_logs WHERE tabla_afectada = 'notificaciones_staff' AND id_registro_afectado IN (SELECT id FROM notificaciones_staff WHERE usuario_id = $adminId)")->fetchColumn();
        $result('auditoria_logs registra INSERT en notificaciones_staff', $cnt >= 1, "count=$cnt");
    } catch (\Throwable $e) {
        $result('auditoria_logs', false, $e->getMessage());
    }

} catch (\Throwable $e) {
    echo "\n  ✗ SETUP falló: " . $e->getMessage() . "\n";
    $failed++;
} finally {
    echo "\n▶ Teardown\n";
    // Limpieza de notificaciones del cliente y admin dummy
    if (isset($clienteId)) {
        $pdo->exec("DELETE FROM notificaciones WHERE cliente_id = $clienteId");
    }
    if (isset($adminId)) {
        $pdo->exec("DELETE FROM notificaciones_staff WHERE usuario_id = $adminId");
        // También las que el seed admin (id=1) recibió por los tests.
        $pdo->exec("DELETE FROM notificaciones_staff WHERE usuario_id = 1 AND tipo_evento IN ('test_evento','ticket_creado','pago_subido','factura_vencida')");
    }
    foreach (array_reverse($cleanup) as $fn) {
        try { $fn(); } catch (\Throwable $e) { echo "  ⚠ cleanup: " . $e->getMessage() . "\n"; }
    }
    echo "  · datos dummy eliminados\n";
}

echo "\n────────────────────────\n";
echo "Resultado: $passed pasaron, $failed fallaron\n";
exit($failed === 0 ? 0 : 1);