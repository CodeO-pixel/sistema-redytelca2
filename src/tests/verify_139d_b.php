<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/Bootstrap.php';

$pdo = \App\Core\Bootstrap::init();
$passed = 0;
$failed = 0;

function check(string $name, bool $ok, string $detail = ''): void {
    global $passed, $failed;
    if ($ok) { $passed++; echo "  OK   $name\n"; }
    else     { $failed++; echo "  FAIL $name" . ($detail !== '' ? " — $detail" : '') . "\n"; }
}

$dummyUserId = null;

try {
    echo "\n[Setup]\n";
    $hash = password_hash('TandaB!Initial', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO usuarios (username, password, email, estado, must_change_password) VALUES ('verify_139d_b_user', ?, 'v139db@local.test', 'Activo', 1)")
        ->execute([$hash]);
    $dummyUserId = (int) $pdo->lastInsertId();
    $pdo->exec("INSERT INTO usuario_rol (usuario_id, rol_id) VALUES ($dummyUserId, 1)");
    check("dummy user creado id=$dummyUserId must_change=1", $dummyUserId > 0);

    $auth = new \App\Services\AuthService($pdo);
    $r = $auth->login('verify_139d_b_user', 'TandaB!Initial', '127.0.0.1', 'cli');
    $token = $r['token'];
    check('login responde must_change_password=true', $r['must_change_password'] === true);

    echo "\n[1] requireStaffAuth sin bypass (default=false)\n";
    $helper = __DIR__ . '/_auth_helper.php';
    file_put_contents($helper, '<?php
require_once __DIR__ . "/../core/Bootstrap.php";
$_SERVER["HTTP_X_SESSION_TOKEN"] = $argv[1];
$bypass = ($argv[2] ?? "0") === "1";
$pdo = \App\Core\Bootstrap::init();
try {
    \App\Core\Auth::requireStaffAuth($pdo, null, $bypass);
    echo "ALIVE";
} catch (\Throwable $e) {
    echo "EXC:" . $e->getMessage();
}
');
    $out1 = trim((string) shell_exec('php ' . escapeshellarg($helper) . ' ' . escapeshellarg($token) . ' 0 2>&1'));
check('sin bypass rechaza must_change=1 (403)', strpos($out1, 'Debes cambiar tu contrase') !== false && strpos($out1, 'ALIVE') === false, 'out=' . substr($out1, 0, 120));
    echo "\n[2] requireStaffAuth con bypass=true\n";
    $out2 = trim((string) shell_exec('php ' . escapeshellarg($helper) . ' ' . escapeshellarg($token) . ' 1 2>&1'));
    check('con bypass acepta must_change=1', $out2 === 'ALIVE', 'out=' . substr($out2, 0, 80));

    echo "\n[3] cambiar contraseña limpia must_change_password\n";
    $usr = new \App\Services\UsuarioService($pdo);
    $usr->cambiarPassword($dummyUserId, 'TandaB!Initial', 'TandaB!New123');
    $flag = (int) $pdo->query("SELECT must_change_password FROM usuarios WHERE id = $dummyUserId")->fetchColumn();
    check('must_change_password=0 en BD', $flag === 0);

    echo "\n[4] requireStaffAuth sin bypass tras cambiar contraseña\n";
    $out3 = trim((string) shell_exec('php ' . escapeshellarg($helper) . ' ' . escapeshellarg($token) . ' 0 2>&1'));
    check('sin bypass acepta tras flag=0', $out3 === 'ALIVE', 'out=' . substr($out3, 0, 80));

    @unlink($helper);

} catch (\Throwable $e) {
    echo "\nEXCEPCION: " . get_class($e) . ': ' . $e->getMessage() . "\n";
    $failed++;
} finally {
    echo "\n[Cleanup]\n";
    if ($dummyUserId !== null) {
        try {
            $pdo->exec("DELETE FROM sesiones WHERE usuario_id = $dummyUserId");
            $pdo->exec("DELETE FROM usuario_rol WHERE usuario_id = $dummyUserId");
            $pdo->exec("DELETE FROM usuarios WHERE id = $dummyUserId");
            echo "  OK   dummy user eliminado\n";
        } catch (\Throwable $e) {
            echo "  WARN cleanup: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n────────────────────────\n";
echo "Resultado: $passed pasaron, $failed fallaron\n";
exit($failed === 0 ? 0 : 1);