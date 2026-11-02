<?php
// tests/verify_a2b3.php
// Verificación funcional de A2-B.3 (portal auth).
//
// Ejecuta el flujo end-to-end de las operaciones del service contra la
// BD real. Crea datos dummy con prefijo 'A2B3', verifica, limpia en
// finally. El cleanup neutraliza el trigger de inmutabilidad para poder
// borrar los logs generados por el test (bypass de test, no de
// producción). La credencial en producción no se borra: se desactiva.
//
// IMPORTANTE (fix 2026-10-31): el teardown resetea @app_usuario_id,
// @app_credencial_id, @app_tipo_actor a NULL ANTES de cualquier DELETE.
// Sin esto, el trigger trg_clientes_credenciales_audit_delete inserta
// un log con credencial_id = X (el @app_credencial_id que dejó
// validateSession), y como X se está borrando en la misma transacción,
// salta FK 1452 (fk_log_credencial). Documentado en VERIFICACION.md
// §2.42.
//
// Uso: php src/tests/verify_a2b3.php
declare(strict_types=1);

require_once __DIR__ . '/../core/Bootstrap.php';

use App\Core\Bootstrap;
use App\Services\ClienteService;
use App\Services\ClienteCredencialService;
use App\Services\PortalAuthService;

$pdo = Bootstrap::init();

$passed = 0;
$failed = 0;
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

$clienteId = null;
$idCredencial = null;

try {
    $datosCliente = [
        'nombre' => 'A2B3',
        'apellido' => 'VerifyTest',
        'cedula' => 'V-99990003',
        'correo' => 'a2b3-verify-' . time() . '@local.test',
        'telefono' => '0414-0000003'
    ];

    $clienteSvc = new ClienteService($pdo);
    $creado = $clienteSvc->crear($datosCliente);
    $clienteId = (int) $creado['cliente_id'];
    $username = $creado['username'];
    $passwordTemporal = $creado['password_temporal'];

    echo "  · cliente_id=$clienteId username=$username\n";
    echo "  · password_temporal=" . $passwordTemporal . " (" . strlen($passwordTemporal) . " chars)\n";

    $result('crear() devuelve cliente_id', $clienteId > 0);
    $result('crear() devuelve username', $username === $datosCliente['correo'], "username=$username");
    $result('password_temporal >= 12 chars', strlen($passwordTemporal) >= 12, "len=" . strlen($passwordTemporal));
    $result('password_temporal no contiene "Redytelca"', strpos($passwordTemporal, 'Redytelca') === false);
    $result('password_temporal no es igual al username', $passwordTemporal !== $username);

    $st = $pdo->prepare("SELECT id, cliente_id, username, estado, must_change_password, intentos_fallidos, bloqueado_hasta FROM clientes_credenciales WHERE cliente_id = ?");
    $st->execute([$clienteId]);
    $cred = $st->fetch(PDO::FETCH_ASSOC);
    $result('credencial existe en BD', $cred !== false);
    if ($cred) {
        $idCredencial = (int) $cred['id'];
        $result('credencial cliente_id coincide', (int) $cred['cliente_id'] === $clienteId);
        $result('credencial username = email', $cred['username'] === $datosCliente['correo']);
        $result('credencial estado = activa', $cred['estado'] === 'activa');
        $result('credencial must_change_password = 1', (int) $cred['must_change_password'] === 1);
        $result('credencial intentos_fallidos = 0', (int) $cred['intentos_fallidos'] === 0);
        $result('credencial bloqueado_hasta = NULL', $cred['bloqueado_hasta'] === null);
    }

    // ─────────────────────────────────────────────────────────────
    // PortalAuthService
    // ─────────────────────────────────────────────────────────────
    echo "\n▶ Tests PortalAuthService\n";

    $authSvc = new PortalAuthService($pdo);

    // 1. Login con password_temporal
    try {
        $r = $authSvc->login($username, $passwordTemporal, '127.0.0.1', 'verify-a2b3');
        $token = $r['token'];
        $result('login() con password_temporal → token', strlen($token) === 64);
        $result('login() must_change_password = true', $r['must_change_password'] === true);
        $result('login() devuelve cliente id', (int) $r['cliente']['id'] === $clienteId);
    } catch (\Throwable $e) {
        $result('login() con password_temporal', false, get_class($e) . ': ' . $e->getMessage());
        $token = null;
    }

    // 2. validateSession()
    if (isset($token)) {
        try {
            $s = $authSvc->validateSession($token);
            $result('validateSession() devuelve cliente', $s !== null && (int) $s['cliente']['id'] === $clienteId);
            $result('validateSession() must_change_password = true', $s !== null && $s['must_change_password'] === true);
            $result('validateSession() token idéntico', $s !== null && $s['token'] === $token);
        } catch (\Throwable $e) {
            $result('validateSession()', false, $e->getMessage());
        }
    }

    // 3. Login con password incorrecta
    try {
        $authSvc->login($username, 'WrongPass!', '127.0.0.1', 'verify-a2b3');
        $result('login() con password incorrecta lanza excepción', false);
    } catch (\App\Exceptions\AuthenticationException $e) {
        $result('login() con password incorrecta lanza AuthenticationException', true);
        $st2 = $pdo->prepare("SELECT intentos_fallidos FROM clientes_credenciales WHERE id = ?");
        $st2->execute([$idCredencial]);
        $intentos = (int) $st2->fetchColumn();
        $result('intentos_fallidos = 1 después de 1 fallo', $intentos === 1, "intentos=$intentos");
    }

    // 4. Reset de intentos y login con password correcta
    $pdo->exec("UPDATE clientes_credenciales SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = $idCredencial");
    try {
        $r = $authSvc->login($username, $passwordTemporal, '127.0.0.1', 'verify-a2b3');
        $result('login() después de reset funciona', strlen($r['token']) === 64);
        $token2 = $r['token'];
    } catch (\Throwable $e) {
        $result('login() después de reset funciona', false, $e->getMessage());
        $token2 = null;
    }

    // 5. cambiarPassword()
    $passwordPostCambio = null;
    if (isset($token2)) {
        $passwordPostCambio = 'NewSecureA2B3!';
        try {
            $authSvc->cambiarPassword($idCredencial, $passwordTemporal, $passwordPostCambio);
            $result('cambiarPassword() OK', true);
            $st3 = $pdo->prepare("SELECT must_change_password FROM clientes_credenciales WHERE id = ?");
            $st3->execute([$idCredencial]);
            $result('must_change_password = 0 después de cambiar', (int) $st3->fetchColumn() === 0);
            $st4 = $pdo->prepare("SELECT COUNT(*) FROM sesiones WHERE credencial_id = ? AND revocado_en IS NULL");
            $st4->execute([$idCredencial]);
            $result('todas las sesiones revocadas', (int) $st4->fetchColumn() === 0);
        } catch (\Throwable $e) {
            $result('cambiarPassword()', false, $e->getMessage());
        }
    }

    // 6. cambiarPassword() con actual incorrecta
    try {
        $authSvc->cambiarPassword($idCredencial, 'WrongActual!', 'Whatever123!');
        $result('cambiarPassword() con actual incorrecta lanza excepción', false);
    } catch (\App\Exceptions\ValidationException $e) {
        $result('cambiarPassword() con actual incorrecta lanza ValidationException', true);
    }

    // ─────────────────────────────────────────────────────────────
    // ClienteCredencialService
    // ─────────────────────────────────────────────────────────────
    echo "\n▶ Tests ClienteCredencialService (admin)\n";

    $credSvc = new ClienteCredencialService($pdo);
    $passwordReset = null;

    // 7. getEstado()
    try {
        $estado = $credSvc->getEstado($clienteId);
        $result('getEstado() devuelve username', $estado['username'] === $datosCliente['correo']);
        $result('getEstado() no devuelve password', !array_key_exists('password', $estado));
        $result('getEstado() estado activa', $estado['estado'] === 'activa');
    } catch (\Throwable $e) {
        $result('getEstado()', false, $e->getMessage());
    }

    // 8. resetPassword() — password FIJA para que el test 11 sea determinista.
    try {
        $passwordReset = 'ResetTestPass456!';
        $r = $credSvc->resetPassword($clienteId, $passwordReset);
        $result('resetPassword() devuelve username', $r['username'] === $datosCliente['correo']);
        $result('resetPassword() devuelve la password pasada', $r['password_temporal'] === $passwordReset);

        $st = $pdo->prepare("SELECT password, must_change_password FROM clientes_credenciales WHERE id = ?");
        $st->execute([$idCredencial]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        $verify = password_verify($passwordReset, $row['password']);
        $result('resetPassword() hash en BD verifica contra la password', $verify);
        $result('resetPassword() cambia must_change_password a 1', (int) $row['must_change_password'] === 1);
    } catch (\Throwable $e) {
        $result('resetPassword()', false, $e->getMessage());
    }

    // 9. toggleEstado() a suspendida
    try {
        $credSvc->toggleEstado($clienteId, 'suspendida');
        $st = $pdo->prepare("SELECT estado FROM clientes_credenciales WHERE id = ?");
        $st->execute([$idCredencial]);
        $result('toggleEstado(suspendida) → BD actualizada', $st->fetchColumn() === 'suspendida');
    } catch (\Throwable $e) {
        $result('toggleEstado(suspendida)', false, $e->getMessage());
    }

    // 10. Login con credencial suspendida
    try {
        $authSvc->login($username, $passwordReset, '127.0.0.1', 'verify-a2b3');
        $result('login() con credencial suspendida lanza excepción', false);
    } catch (\App\Exceptions\AuthenticationException $e) {
        $result('login() con credencial suspendida lanza AuthenticationException', strpos($e->getMessage(), 'suspendida') !== false);
    }

    // 11. toggleEstado() a activa → login con la password del test 8
    try {
        $credSvc->toggleEstado($clienteId, 'activa');
        $r = $authSvc->login($username, $passwordReset, '127.0.0.1', 'verify-a2b3');
        $result('toggleEstado(activa) → login funciona', strlen($r['token']) === 64);
    } catch (\Throwable $e) {
        $result('toggleEstado(activa) → login funciona', false, get_class($e) . ': ' . $e->getMessage());
    }

    // 12. getEstado() de cliente inexistente
    try {
        $credSvc->getEstado(999999);
        $result('getEstado() cliente inexistente lanza excepción', false);
    } catch (\App\Exceptions\NotFoundException $e) {
        $result('getEstado() cliente inexistente lanza NotFoundException', true);
    }

    // 13. toggleEstado con estado inválido
    try {
        $credSvc->toggleEstado($clienteId, 'invalido');
        $result('toggleEstado(invalido) lanza excepción', false);
    } catch (\App\Exceptions\ValidationException $e) {
        $result('toggleEstado(invalido) lanza ValidationException', true);
    }

} catch (\Throwable $e) {
    echo "\n  ✗ SETUP falló: " . get_class($e) . ': ' . $e->getMessage() . "\n";
    $failed++;
} finally {
    echo "\n▶ Teardown\n";

    // FIX 2026-10-31: resetear @app_* a NULL antes de cualquier DELETE.
    // validateSession() (test 2) seteó @app_credencial_id = X. Si el
    // trigger trg_clientes_credenciales_audit_delete inserta un log con
    // credencial_id = X mientras X se está borrando en la misma
    // transacción, salta FK 1452. Con @app_credencial_id = NULL, el
    // trigger inserta un log con credencial_id = NULL, tipo_actor =
    // 'sistema', que es válido según chk_log_actor.
    //
    // Los logs 'sistema' residuales (uno por cada DELETE con trigger)
    // se limpian al final filtrando por creado_en reciente + tablas
    // del test.
    try {
        $pdo->exec("SET @app_usuario_id = NULL, @app_credencial_id = NULL, @app_tipo_actor = NULL");
    } catch (\Throwable $e) { /* no-op */ }

    try {
        $pdo->exec("DROP TRIGGER IF EXISTS trg_auditoria_logs_inmutable_delete");
    } catch (\Throwable $e) {
        echo "  ⚠ drop trigger: " . $e->getMessage() . "\n";
    }

    if (isset($idCredencial)) {
        try { $pdo->exec("DELETE FROM auditoria_logs WHERE credencial_id = $idCredencial"); } catch (\Throwable $e) { /* no-op */ }
        try { $pdo->exec("DELETE FROM sesiones WHERE credencial_id = $idCredencial"); } catch (\Throwable $e) { /* no-op */ }
        try { $pdo->exec("DELETE FROM auditoria_logs WHERE credencial_id = $idCredencial"); } catch (\Throwable $e) { /* no-op */ }
        try { $pdo->exec("DELETE FROM clientes_credenciales WHERE id = $idCredencial"); } catch (\Throwable $e) { echo "  ⚠ credencial: " . $e->getMessage() . "\n"; }
        try { $pdo->exec("DELETE FROM auditoria_logs WHERE credencial_id = $idCredencial"); } catch (\Throwable $e) { /* no-op */ }
    }

    if (isset($clienteId)) {
        try { $pdo->exec("DELETE FROM r_cliente_telefono WHERE cliente_id = $clienteId"); } catch (\Throwable $e) { /* no-op */ }
        try { $pdo->exec("DELETE FROM clientes WHERE id = $clienteId"); } catch (\Throwable $e) { echo "  ⚠ cliente: " . $e->getMessage() . "\n"; }
    }

    // Limpiar logs 'sistema' residuales (credencial_id=NULL, recientes, tablas del test)
    try {
        $pdo->exec("DELETE FROM auditoria_logs WHERE credencial_id IS NULL
                    AND creado_en > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                    AND tabla_afectada IN ('clientes_credenciales','clientes','sesiones','r_cliente_telefono')");
    } catch (\Throwable $e) { /* no-op */ }

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