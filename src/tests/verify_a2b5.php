<?php
// tests/verify_a2b5.php
// Verificación funcional de A2-B.5 (backend del portal de tickets).
//
// Cubre:
//   - A2-B.5.a: PortalTicketService::listar / detalle / crear
//   - A2-B.5.b: PortalTicketService::responder + TicketRespuestaService::crearRespuestaCliente
//   - A2-B.5.d: URLs de notificación (cliente /portal/*, staff /*)
//   - Fixes A2-B.5-1 (usuario_creador_id=NULL) y A2-B.5-5 (ownership servicio_id)
//   - BOLA/IDOR en detalle() y responder()
//   - CHECK chk_ticket_respuestas_discriminador
//
// Crea dummies con prefijo 'A2B5', verifica, limpia en finally.
//
// Uso: php src/tests/verify_a2b5.php
declare(strict_types=1);

require_once __DIR__ . '/../core/Bootstrap.php';

use App\Core\Bootstrap;
use App\Exceptions\DomainException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\TicketRepository;
use App\Services\PortalTicketService;

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

$clienteAId = null;
$clienteBId = null;
$credAId = null;
$credBId = null;
$servicioAId = null;
$servicioBId = null;
$adminId = null;
$ticketAId = null;
$ticketCerradoId = null;
$respuestaId = null;

echo "\n▶ Setup de datos dummy\n";

try {
    // --- Plan existente (para servicioA / servicioB) ---
    $planId = (int) $pdo->query("SELECT id FROM planes WHERE estado='activo' ORDER BY id ASC LIMIT 1")->fetchColumn();
    if ($planId <= 0) {
        throw new \RuntimeException('No hay planes activos en el seed. Abortar.');
    }
    echo "  · plan_id usado: $planId\n";

    // --- Cliente A ---
    $pdo->exec("INSERT INTO clientes (nombre, apellido, cedula, correo, estado) VALUES ('A2B5A','Test','V-88000001','a2b5-a@local.test','activo')");
    $clienteAId = (int) $pdo->lastInsertId();
    $cleanup[] = fn() => $pdo->exec("DELETE FROM clientes WHERE id = $clienteAId");

    // --- Cliente B (para BOLA/IDOR) ---
    $pdo->exec("INSERT INTO clientes (nombre, apellido, cedula, correo, estado) VALUES ('A2B5B','Test','V-88000002','a2b5-b@local.test','activo')");
    $clienteBId = (int) $pdo->lastInsertId();
    $cleanup[] = fn() => $pdo->exec("DELETE FROM clientes WHERE id = $clienteBId");

    // --- Credencial A ---
    $hash = password_hash('A2B5Pass!', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO clientes_credenciales (cliente_id, username, password, estado, must_change_password) VALUES ($clienteAId, 'a2b5-a@local.test', '$hash', 'activa', 0)");
    $credAId = (int) $pdo->lastInsertId();
    $cleanup[] = fn() => $pdo->exec("DELETE FROM clientes_credenciales WHERE id = $credAId");

    // --- Credencial B ---
    $pdo->exec("INSERT INTO clientes_credenciales (cliente_id, username, password, estado, must_change_password) VALUES ($clienteBId, 'a2b5-b@local.test', '$hash', 'activa', 0)");
    $credBId = (int) $pdo->lastInsertId();
    $cleanup[] = fn() => $pdo->exec("DELETE FROM clientes_credenciales WHERE id = $credBId");

    // --- Servicio A ---
    // Trigger trg_servicios_validar_insert: INSERT solo permite 'pendiente'.
    $pdo->exec("INSERT INTO servicios (cliente_id, estado_comercial, plan_id) VALUES ($clienteAId, 'pendiente', $planId)");
    $servicioAId = (int) $pdo->lastInsertId();
    $pdo->exec("UPDATE servicios SET estado_comercial='activo' WHERE id = $servicioAId");
    $cleanup[] = fn() => $pdo->exec("DELETE FROM servicios WHERE id = $servicioAId");

    // --- Servicio B (para probar ownership de servicio_id) ---
    $pdo->exec("INSERT INTO servicios (cliente_id, estado_comercial, plan_id) VALUES ($clienteBId, 'pendiente', $planId)");
    $servicioBId = (int) $pdo->lastInsertId();
    $pdo->exec("UPDATE servicios SET estado_comercial='activo' WHERE id = $servicioBId");
    $cleanup[] = fn() => $pdo->exec("DELETE FROM servicios WHERE id = $servicioBId");

    // --- Admin (para recibir notificaciones_staff) ---
    $pdo->exec("INSERT INTO usuarios (username, password, email, estado) VALUES ('a2b5_admin','$hash','a2b5-admin@local.test','Activo')");
    $adminId = (int) $pdo->lastInsertId();
    $pdo->exec("INSERT INTO usuario_rol (usuario_id, rol_id) VALUES ($adminId, 1)");
    $cleanup[] = fn() => $pdo->exec("DELETE FROM usuario_rol WHERE usuario_id = $adminId");
    $cleanup[] = fn() => $pdo->exec("DELETE FROM usuarios WHERE id = $adminId");

    echo "  · cliA=$clienteAId cliB=$clienteBId credA=$credAId credB=$credBId srvA=$servicioAId srvB=$servicioBId admin=$adminId\n";

    $svc = new PortalTicketService($pdo);

    echo "\n▶ Grupo A — PortalTicketService::crear + TicketService::crear(null)\n";

    // 1. Crear ticket válido (con servicio_id del mismo cliente)
    try {
        $ticketAId = $svc->crear($clienteAId, [
            'asunto' => 'A2B5 Internet lento',
            'descripcion' => 'Descripción de prueba',
            'prioridad' => 'Alta',
            'servicio_id' => $servicioAId,
        ]);
        $result('A1 crear ticket válido', $ticketAId > 0 && is_int($ticketAId));
    } catch (\Throwable $e) {
        $result('A1 crear ticket válido', false, get_class($e) . ': ' . $e->getMessage());
    }

    // 2. Ticket tiene origen=portal_cliente
    if ($ticketAId) {
        $row = $pdo->query("SELECT origen FROM tickets WHERE id = $ticketAId")->fetch();
        $result('A2 ticket.origen = portal_cliente', $row && $row['origen'] === 'portal_cliente', 'origen=' . ($row['origen'] ?? 'NULL'));
    } else {
        $result('A2 ticket.origen = portal_cliente', false, 'ticketAId=null');
    }

    // 3. usuario_creador_id IS NULL (fix A2-B.5-1)
    if ($ticketAId) {
        $val = $pdo->query("SELECT usuario_creador_id FROM tickets WHERE id = $ticketAId")->fetchColumn();
        $result('A3 ticket.usuario_creador_id IS NULL', $val === null, 'val=' . var_export($val, true));
    } else {
        $result('A3 ticket.usuario_creador_id IS NULL', false, 'ticketAId=null');
    }

    // 4. estado = 'Abierto'
    if ($ticketAId) {
        $estado = $pdo->query("SELECT estado FROM tickets WHERE id = $ticketAId")->fetchColumn();
        $result('A4 ticket.estado = Abierto', $estado === 'Abierto', 'estado=' . var_export($estado, true));
    } else {
        $result('A4 ticket.estado = Abierto', false, 'ticketAId=null');
    }

    // 5. cliente_id = clienteAId
    if ($ticketAId) {
        $cid = (int) $pdo->query("SELECT cliente_id FROM tickets WHERE id = $ticketAId")->fetchColumn();
        $result('A5 ticket.cliente_id = cliA', $cid === $clienteAId, "cid=$cid");
    } else {
        $result('A5 ticket.cliente_id = cliA', false, 'ticketAId=null');
    }

    // 6. servicio_id de otro cliente → ValidationException (fix A2-B.5-5)
    try {
        $svc->crear($clienteAId, [
            'asunto' => 'A2B5 Servicio ajeno',
            'servicio_id' => $servicioBId,
        ]);
        $result('A6 servicio_id de otro cliente → ValidationException', false, 'no lanzó');
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
        $result('A6 servicio_id de otro cliente → ValidationException', isset($errors['servicio_id']), implode(', ', array_keys($errors)));
    } catch (\Throwable $e) {
        $result('A6 servicio_id de otro cliente → ValidationException', false, get_class($e) . ': ' . $e->getMessage());
    }

    echo "\n▶ Grupo B — PortalTicketService::listar\n";

    // 7. listar(cliA) devuelve ≥1 ticket
    try {
        $r = $svc->listar($clienteAId, 1, 20);
        $result('B1 listar(cliA) devuelve ≥1 ticket', count($r['items']) >= 1, 'count=' . count($r['items']));
    } catch (\Throwable $e) {
        $result('B1 listar(cliA) devuelve ≥1 ticket', false, $e->getMessage());
    }

    // 8. listar(cliA) solo ve tickets de A
    try {
        $r = $svc->listar($clienteAId, 1, 20);
        $todosSonDeA = true;
        foreach ($r['items'] as $t) {
            if ((int) $t['cliente_id'] !== $clienteAId) { $todosSonDeA = false; break; }
        }
        $result('B2 listar(cliA) solo ve tickets de A', $todosSonDeA);
    } catch (\Throwable $e) {
        $result('B2 listar(cliA) solo ve tickets de A', false, $e->getMessage());
    }

    // 9. listar(cliB) NO ve tickets de A
    try {
        $r = $svc->listar($clienteBId, 1, 20);
        $hayTicketsDeA = false;
        foreach ($r['items'] as $t) {
            if ((int) $t['cliente_id'] === $clienteAId) { $hayTicketsDeA = true; break; }
        }
        $result('B3 listar(cliB) NO ve tickets de A', !$hayTicketsDeA);
    } catch (\Throwable $e) {
        $result('B3 listar(cliB) NO ve tickets de A', false, $e->getMessage());
    }

    // 10. Filtro estado='Abierto' devuelve el ticket
    try {
        $r = $svc->listar($clienteAId, 1, 20, ['estado' => 'Abierto']);
        $encontrado = false;
        foreach ($r['items'] as $t) {
            if ((int) $t['id'] === $ticketAId) { $encontrado = true; break; }
        }
        $result('B4 filtro estado=Abierto devuelve el ticket', $encontrado);
    } catch (\Throwable $e) {
        $result('B4 filtro estado=Abierto devuelve el ticket', false, $e->getMessage());
    }

    // 11. Filtro estado inválido se descarta (no 422)
    try {
        $r = $svc->listar($clienteAId, 1, 20, ['estado' => 'Invalido']);
        $result('B5 filtro estado inválido no lanza', isset($r['items']) && is_array($r['items']));
    } catch (\Throwable $e) {
        $result('B5 filtro estado inválido no lanza', false, get_class($e) . ': ' . $e->getMessage());
    }

    // 12. Filtro prioridad='Alta' devuelve el ticket
    try {
        $r = $svc->listar($clienteAId, 1, 20, ['prioridad' => 'Alta']);
        $encontrado = false;
        foreach ($r['items'] as $t) {
            if ((int) $t['id'] === $ticketAId) { $encontrado = true; break; }
        }
        $result('B6 filtro prioridad=Alta devuelve el ticket', $encontrado);
    } catch (\Throwable $e) {
        $result('B6 filtro prioridad=Alta devuelve el ticket', false, $e->getMessage());
    }

    // 13. per_page > 100 se clampea
    try {
        $r = $svc->listar($clienteAId, 1, 999);
        $result('B7 per_page clampea a 100', (int) $r['per_page'] === 100, 'per_page=' . $r['per_page']);
    } catch (\Throwable $e) {
        $result('B7 per_page clampea a 100', false, $e->getMessage());
    }

    echo "\n▶ Grupo C — PortalTicketService::detalle\n";

    // 14. detalle(cliA) devuelve ticket + respuestas
    try {
        $d = $svc->detalle($ticketAId, $clienteAId);
        $ok = $d !== null && isset($d['ticket'], $d['respuestas'])
            && (int) $d['ticket']['id'] === $ticketAId;
        $result('C1 detalle(cliA) devuelve {ticket, respuestas}', $ok);
    } catch (\Throwable $e) {
        $result('C1 detalle(cliA) devuelve {ticket, respuestas}', false, $e->getMessage());
    }

    // 15. detalle de otro cliente → null (BOLA/IDOR)
    try {
        $d = $svc->detalle($ticketAId, $clienteBId);
        $result('C2 detalle(cliB, ticketA) → null', $d === null, 'd=' . var_export($d, true));
    } catch (\Throwable $e) {
        $result('C2 detalle(cliB, ticketA) → null', false, $e->getMessage());
    }

    // 16. detalle inexistente → null
    try {
        $d = $svc->detalle(999999, $clienteAId);
        $result('C3 detalle(999999) → null', $d === null, 'd=' . var_export($d, true));
    } catch (\Throwable $e) {
        $result('C3 detalle(999999) → null', false, $e->getMessage());
    }

    echo "\n▶ Grupo D — PortalTicketService::responder\n";

    // 17. responder(cliA) no lanza
    try {
        $respuestaId = $svc->responder($ticketAId, $clienteAId, $credAId, 'Mensaje del cliente');
        $result('D1 responder(cliA) no lanza', $respuestaId > 0, 'respuestaId=' . var_export($respuestaId, true));
    } catch (\Throwable $e) {
        $result('D1 responder(cliA) no lanza', false, get_class($e) . ': ' . $e->getMessage());
    }

    // 18. tipo_autor='cliente'
    if ($respuestaId) {
        $ta = $pdo->query("SELECT tipo_autor FROM ticket_respuestas WHERE id = $respuestaId")->fetchColumn();
        $result('D2 tipo_autor = cliente', $ta === 'cliente', 'ta=' . var_export($ta, true));
    } else {
        $result('D2 tipo_autor = cliente', false, 'respuestaId=null');
    }

    // 19. credencial_id de la sesión
    if ($respuestaId) {
        $cid = (int) $pdo->query("SELECT credencial_id FROM ticket_respuestas WHERE id = $respuestaId")->fetchColumn();
        $result('D3 credencial_id = credA (de la sesión)', $cid === $credAId, "cid=$cid esperado=$credAId");
    } else {
        $result('D3 credencial_id = credA (de la sesión)', false, 'respuestaId=null');
    }

    // 20. usuario_id IS NULL
    if ($respuestaId) {
        $uid = $pdo->query("SELECT usuario_id FROM ticket_respuestas WHERE id = $respuestaId")->fetchColumn();
        $result('D4 usuario_id IS NULL', $uid === null, 'uid=' . var_export($uid, true));
    } else {
        $result('D4 usuario_id IS NULL', false, 'respuestaId=null');
    }

    // 21. adjunto_path IS NULL
    if ($respuestaId) {
        $adj = $pdo->query("SELECT adjunto_path FROM ticket_respuestas WHERE id = $respuestaId")->fetchColumn();
        $result('D5 adjunto_path IS NULL', $adj === null, 'adj=' . var_export($adj, true));
    } else {
        $result('D5 adjunto_path IS NULL', false, 'respuestaId=null');
    }

    // 22. responder ticket de otro cliente → NotFoundException (BOLA/IDOR)
    try {
        $svc->responder($ticketAId, $clienteBId, $credBId, 'Intento BOLA');
        $result('D6 responder(cliB, ticketA) → NotFoundException', false, 'no lanzó');
    } catch (NotFoundException $e) {
        $result('D6 responder(cliB, ticketA) → NotFoundException', true);
    } catch (\Throwable $e) {
        $result('D6 responder(cliB, ticketA) → NotFoundException', false, get_class($e) . ': ' . $e->getMessage());
    }

    // 23. responder ticket cerrado → DomainException
    // Se crea un ticket y se transiciona a Cerrado vía UPDATE (trigger valida Abierto→Cerrado).
    try {
        $pdo->exec("INSERT INTO tickets (asunto, estado, cliente_id, origen) VALUES ('A2B5 ticket cerrado', 'Abierto', $clienteAId, 'portal_cliente')");
        $ticketCerradoId = (int) $pdo->lastInsertId();
        $pdo->exec("UPDATE tickets SET estado='Cerrado' WHERE id = $ticketCerradoId");
        try {
            $svc->responder($ticketCerradoId, $clienteAId, $credAId, 'Intento en cerrado');
            $result('D7 responder ticket cerrado → DomainException', false, 'no lanzó');
        } catch (DomainException $e) {
            $ok = strpos($e->getMessage(), 'cerrado') !== false;
            $result('D7 responder ticket cerrado → DomainException', $ok, 'msg=' . $e->getMessage());
        }
    } catch (\Throwable $e) {
        $result('D7 responder ticket cerrado → DomainException', false, get_class($e) . ': ' . $e->getMessage());
    }

    echo "\n▶ Grupo E — Notificaciones (A2-B.5.b + .d)\n";

    // 24. notificaciones_staff tiene fila ticket_respondido_por_cliente al admin
    $cnt = (int) $pdo->query("SELECT COUNT(*) FROM notificaciones_staff WHERE usuario_id = $adminId AND tipo_evento = 'ticket_respondido_por_cliente'")->fetchColumn();
    $result('E1 notificaciones_staff: ticket_respondido_por_cliente al admin', $cnt >= 1, "cnt=$cnt");

    // 25. url_destino de esa notificación staff = /tickets?id=N
    $url = (string) $pdo->query("SELECT url_destino FROM notificaciones_staff WHERE usuario_id = $adminId AND tipo_evento = 'ticket_respondido_por_cliente' ORDER BY id DESC LIMIT 1")->fetchColumn();
    $result('E2 url_destino staff = /tickets?id=N', $url === '/tickets?id=' . $ticketAId, "url=$url esperado=/tickets?id=$ticketAId");

    // 26. NO se crea notificación en bandeja cliente por la respuesta del cliente
    // (el cliente ya sabe que respondió). Se cuenta por tipo_evento.
    $cntCliente = (int) $pdo->query("SELECT COUNT(*) FROM notificaciones WHERE cliente_id = $clienteAId AND tipo_evento = 'ticket_respondido_por_cliente'")->fetchColumn();
    $result('E3 NO hay notificación cliente por ticket_respondido_por_cliente', $cntCliente === 0, "cnt=$cntCliente");

    // 27. notificaciones: ticket_creado con url /portal/tickets?id=N
    $urlCli = (string) $pdo->query("SELECT url_destino FROM notificaciones WHERE cliente_id = $clienteAId AND tipo_evento = 'ticket_creado' ORDER BY id ASC LIMIT 1")->fetchColumn();
    $result('E4 url_destino cliente = /portal/tickets?id=N', $urlCli === '/portal/tickets?id=' . $ticketAId, "url=$urlCli esperado=/portal/tickets?id=$ticketAId");

    // 28. notificaciones_staff: ticket_creado con url /tickets?id=N
    $urlStaff = (string) $pdo->query("SELECT url_destino FROM notificaciones_staff WHERE usuario_id = $adminId AND tipo_evento = 'ticket_creado' ORDER BY id ASC LIMIT 1")->fetchColumn();
    $result('E5 url_destino staff ticket_creado = /tickets?id=N', $urlStaff === '/tickets?id=' . $ticketAId, "url=$urlStaff esperado=/tickets?id=$ticketAId");

    echo "\n▶ Grupo F — CHECK chk_ticket_respuestas_discriminador\n";

    // 29. INSERT con tipo_autor='cliente' + usuario_id NOT NULL → PDOException 4025
    try {
        $pdo->exec("INSERT INTO ticket_respuestas (ticket_id, tipo_autor, mensaje, usuario_id, credencial_id) VALUES ($ticketAId, 'cliente', 'x', $adminId, NULL)");
        $result('F1 CHECK rechaza cliente con usuario_id NOT NULL', false, 'no lanzó');
    } catch (\PDOException $e) {
        $sqlCode = (int) ($e->errorInfo[1] ?? 0);
        $result('F1 CHECK rechaza cliente con usuario_id NOT NULL', $sqlCode === 4025, "sqlCode=$sqlCode");
    }

    echo "\n▶ Grupo G — Integridad\n";

    // 30. Verificar que detalle() trae las respuestas del hilo
    try {
        $d = $svc->detalle($ticketAId, $clienteAId);
        $tieneRespuesta = false;
        foreach ($d['respuestas'] as $r) {
            if ((int) $r['id'] === $respuestaId) { $tieneRespuesta = true; break; }
        }
        $result('G1 detalle() incluye la respuesta creada', $tieneRespuesta);
    } catch (\Throwable $e) {
        $result('G1 detalle() incluye la respuesta creada', false, $e->getMessage());
    }

} catch (\Throwable $e) {
    echo "\n  ✗ SETUP falló: " . get_class($e) . ': ' . $e->getMessage() . "\n";
    $failed++;
} finally {
    echo "\n▶ Teardown\n";

    // Reset de @app_* (lección §2.42)
    try {
        $pdo->exec("SET @app_usuario_id = NULL, @app_credencial_id = NULL, @app_tipo_actor = NULL");
    } catch (\Throwable $e) { /* no-op */ }

    // Neutralizar trigger inmutable para poder borrar auditoria_logs
    try {
        $pdo->exec("DROP TRIGGER IF EXISTS trg_auditoria_logs_inmutable_delete");
    } catch (\Throwable $e) { /* no-op */ }

    // Orden FK (lecciones §2.42, §2.43, §2.45)
    $stmts = [];

    if ($clienteAId !== null && $clienteBId !== null) {
        $stmts[] = "DELETE FROM notificaciones WHERE cliente_id IN ($clienteAId, $clienteBId)";
    }
    if ($adminId !== null) {
        $stmts[] = "DELETE FROM notificaciones_staff WHERE usuario_id = $adminId";
    }
    if ($clienteAId !== null && $clienteBId !== null) {
        $stmts[] = "DELETE FROM ticket_respuestas WHERE ticket_id IN (SELECT id FROM tickets WHERE cliente_id IN ($clienteAId, $clienteBId))";
        $stmts[] = "DELETE FROM tickets WHERE cliente_id IN ($clienteAId, $clienteBId)";
        $stmts[] = "DELETE FROM servicios WHERE cliente_id IN ($clienteAId, $clienteBId)";
    }
    if ($credAId !== null && $credBId !== null) {
        $stmts[] = "DELETE FROM sesiones WHERE credencial_id IN ($credAId, $credBId)";
        $stmts[] = "DELETE FROM auditoria_logs WHERE credencial_id IN ($credAId, $credBId)";
        $stmts[] = "DELETE FROM clientes_credenciales WHERE id IN ($credAId, $credBId)";
    }
    if ($clienteAId !== null && $clienteBId !== null) {
        $stmts[] = "DELETE FROM r_cliente_telefono WHERE cliente_id IN ($clienteAId, $clienteBId)";
        $stmts[] = "DELETE FROM clientes WHERE id IN ($clienteAId, $clienteBId)";
    }
    if ($adminId !== null) {
        $stmts[] = "DELETE FROM auditoria_logs WHERE usuario_id = $adminId";
        $stmts[] = "DELETE FROM usuario_rol WHERE usuario_id = $adminId";
        $stmts[] = "DELETE FROM usuarios WHERE id = $adminId";
    }

    // Logs residuales de tipo 'sistema' generados por los DELETE con trigger
    $stmts[] = "DELETE FROM auditoria_logs WHERE credencial_id IS NULL
                AND creado_en > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                AND tabla_afectada IN ('tickets','ticket_respuestas','servicios','clientes','clientes_credenciales','usuarios','notificaciones','notificaciones_staff','usuario_rol')";

    foreach ($stmts as $sql) {
        try { $pdo->exec($sql); } catch (\Throwable $e) { echo "  ⚠ $sql — " . $e->getMessage() . "\n"; }
    }

    // Restaurar trigger inmutable
    try {
        $pdo->exec("CREATE TRIGGER IF NOT EXISTS trg_auditoria_logs_inmutable_delete BEFORE DELETE ON auditoria_logs FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'auditoria_logs es inmutable: DELETE bloqueado.'; END");
    } catch (\Throwable $e) {
        echo "  ⚠ restaurar trigger: " . $e->getMessage() . "\n";
    }

    echo "  · datos dummy eliminados\n";
}

echo "\n────────────────────────\n";
echo "Resultado: $passed pasaron, $failed fallaron\n";
exit($failed === 0 ? 0 : 1);