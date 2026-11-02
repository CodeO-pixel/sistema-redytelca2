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

$createdRolId = null;
$tempRolPermId = null;
$originalPassword1 = null;
$originalPassword2 = null;

try {
    echo "\n[Setup]\n";

    $originalPassword1 = $pdo->query("SELECT password FROM usuarios WHERE id = 1")->fetchColumn();
    $originalPassword2 = $pdo->query("SELECT password FROM usuarios WHERE id = 2")->fetchColumn();
    if (!$originalPassword1 || !$originalPassword2) {
        throw new \RuntimeException('No se encontraron usuarios id 1 y 2.');
    }

    $knownHash = password_hash('Verify139dC!', PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE usuarios SET password = ? WHERE id IN (1, 2)")->execute([$knownHash]);
    check('hashes temporales aplicados', true);

    $auth = new \App\Services\AuthService($pdo);
    $rAdmin = $auth->login('admin', 'Verify139dC!', '127.0.0.1', 'cli');
    $tokenAdmin = $rAdmin['token'];
    $rOper = $auth->login('Marcos', 'Verify139dC!', '127.0.0.1', 'cli');
    $tokenOper = $rOper['token'];
    check('tokens admin y operador obtenidos', strlen($tokenAdmin) === 64 && strlen($tokenOper) === 64);

    // Helper: body viaja en base64 para evitar corrupción de escapeshellarg en Windows.
    $helper = __DIR__ . '/_api_helper_c.php';
    file_put_contents($helper, '<?php
require_once __DIR__ . "/../core/Bootstrap.php";
$method = $argv[1];
$path = $argv[2];
$token = $argv[3] ?? "";
$bodyB64 = $argv[4] ?? "";
$body = $bodyB64 !== "" ? base64_decode($bodyB64, true) : "";
if ($body === false) { $body = ""; }
$_SERVER["REQUEST_METHOD"] = $method;
$_SERVER["HTTP_X_SESSION_TOKEN"] = $token;
$parts = parse_url($path);
if (isset($parts["query"])) {
    parse_str($parts["query"], $_GET);
}
if ($body !== "") {
    $decoded = json_decode($body, true);
    if (is_array($decoded)) {
        $_POST = $decoded;
    }
}
$thinFile = __DIR__ . "/../../public/" . ltrim($parts["path"], "/");
if (!file_exists($thinFile)) {
    echo "404 THIN NOT FOUND";
    exit;
}
try {
    require $thinFile;
} catch (\Throwable $e) {
    echo "EXC:" . $e->getMessage();
}
');
    $runApi = function(string $method, string $path, string $token = '', array $body = []) use ($helper): string {
        $bodyArg = $body ? base64_encode(json_encode($body)) : '';
        $cmd = 'php ' . escapeshellarg($helper) . ' '
            . escapeshellarg($method) . ' '
            . escapeshellarg($path) . ' '
            . escapeshellarg($token) . ' '
            . escapeshellarg($bodyArg) . ' 2>&1';
        return trim((string) shell_exec($cmd));
    };

    echo "\n[C1] GET roles_listar como Admin\n";
    $out = $runApi('GET', 'roles_listar.php', $tokenAdmin);
    check('C1 GET roles 200', strpos($out, '"status":"success"') !== false, 'out=' . substr($out, 0, 120));
    check('C1 devuelve 2 roles', substr_count($out, '"id_rol"') >= 2, 'count=' . substr_count($out, '"id_rol"'));

    echo "\n[C2] GET roles_listar como Operador → 403 (SoD)\n";
    $out = $runApi('GET', 'roles_listar.php', $tokenOper);
    check('C2 Operador 403', strpos($out, 'Separación de Funciones') !== false || strpos($out, 'Acceso denegado') !== false, 'out=' . substr($out, 0, 120));

    echo "\n[C3] GET roles_listar sin token → 401\n";
    $out = $runApi('GET', 'roles_listar.php', '');
    check('C3 sin token 401', strpos($out, 'No autenticado') !== false, 'out=' . substr($out, 0, 120));

    echo "\n[C4] POST roles_listar Admin {nombre_rol:'test_139d_rol'}\n";
    $out = $runApi('POST', 'roles_listar.php', $tokenAdmin, ['nombre_rol' => 'test_139d_rol']);
    check('C4 POST rol 200', strpos($out, '"status":"success"') !== false, 'out=' . substr($out, 0, 120));
    preg_match('/"id_rol":(\d+)/', $out, $m);
    $createdRolId = isset($m[1]) ? (int) $m[1] : null;
    check('C4 id_rol devuelto', $createdRolId !== null && $createdRolId > 2);

    echo "\n[C5] POST roles_listar Admin duplicado\n";
    $out = $runApi('POST', 'roles_listar.php', $tokenAdmin, ['nombre_rol' => 'Administrador']);
    check('C5 duplicado 409', strpos($out, 'Ya existe un rol') !== false, 'out=' . substr($out, 0, 120));

    echo "\n[C6] POST roles_listar Admin sin nombre\n";
    $out = $runApi('POST', 'roles_listar.php', $tokenAdmin, []);
    check('C6 sin nombre 422', strpos($out, 'Datos inválidos') !== false || strpos($out, 'nombre del rol') !== false, 'out=' . substr($out, 0, 120));

    echo "\n[C7] PUT roles_editar Admin\n";
    if ($createdRolId !== null) {
        $out = $runApi('PUT', 'roles_editar.php', $tokenAdmin, ['id_rol' => $createdRolId, 'nombre_rol' => 'test_139d_rol_mod']);
        check('C7 PUT rol 200', strpos($out, '"status":"success"') !== false, 'out=' . substr($out, 0, 120));
        $nombreDb = $pdo->query("SELECT nombre_rol FROM rol WHERE id = $createdRolId")->fetchColumn();
        check('C7 nombre en BD actualizado', $nombreDb === 'test_139d_rol_mod', 'nombre=' . ($nombreDb ?: 'NULL'));
    } else {
        check('C7 PUT rol 200', false, 'createdRolId null (C4 falló)');
        check('C7 nombre en BD actualizado', false, 'createdRolId null');
    }

    echo "\n[C8] POST roles_eliminar Admin\n";
    if ($createdRolId !== null) {
        $out = $runApi('POST', 'roles_eliminar.php', $tokenAdmin, ['id_rol' => $createdRolId]);
        check('C8 DELETE rol 200', strpos($out, '"status":"success"') !== false, 'out=' . substr($out, 0, 120));
        $existe = (int) $pdo->query("SELECT COUNT(*) FROM rol WHERE id = $createdRolId")->fetchColumn();
        check('C8 rol borrado en BD', $existe === 0);
        $createdRolId = null;
    } else {
        check('C8 DELETE rol 200', false, 'createdRolId null');
        check('C8 rol borrado en BD', false, 'createdRolId null');
    }

    echo "\n[C9] POST roles_eliminar Admin id=1 (Admin protegido)\n";
    $out = $runApi('POST', 'roles_eliminar.php', $tokenAdmin, ['id_rol' => 1]);
    check('C9 rol 1 rechazado 422', strpos($out, 'Administrador no se puede eliminar') !== false, 'out=' . substr($out, 0, 120));

    echo "\n[C10] GET permisos_listar Admin?id_rol=1\n";
    $out = $runApi('GET', 'permisos_listar.php?id_rol=1', $tokenAdmin);
    check('C10 GET permisos 200', strpos($out, '"status":"success"') !== false, 'out=' . substr($out, 0, 120));
    check('C10 tiene modulos', strpos($out, '"modulos"') !== false);
    check('C10 tiene permisos_catalogo', strpos($out, '"permisos_catalogo"') !== false);

    echo "\n[C11] POST permisos_guardar Admin con rol dummy (no toca seed)\n";
    $pdo->exec("INSERT INTO rol (nombre_rol) VALUES ('test_139d_rol_perm')");
    $tempRolPermId = (int) $pdo->lastInsertId();
    $out = $runApi('POST', 'permisos_guardar.php', $tokenAdmin, [
        'id_rol' => $tempRolPermId,
        'paginas' => [
            ['id_pagina' => 1, 'visible' => true, 'permisos' => [1]]
        ]
    ]);
    check('C11 POST permisos 200', strpos($out, '"status":"success"') !== false, 'out=' . substr($out, 0, 120));
    $nPag = (int) $pdo->query("SELECT COUNT(*) FROM rol_modulo_pagina WHERE rol_modulo_id IN (SELECT id FROM rol_modulo WHERE rol_id = $tempRolPermId)")->fetchColumn();
    $nPer = (int) $pdo->query("SELECT COUNT(*) FROM rol_modulo_permiso WHERE rol_modulo_id IN (SELECT id FROM rol_modulo WHERE rol_id = $tempRolPermId)")->fetchColumn();
    check('C11 rol_modulo_pagina escrito', $nPag === 1, 'nPag=' . $nPag);
    check('C11 rol_modulo_permiso escrito', $nPer === 1, 'nPer=' . $nPer);

    echo "\n[C12] POST permisos_guardar Admin id_rol=99999\n";
    $out = $runApi('POST', 'permisos_guardar.php', $tokenAdmin, [
        'id_rol' => 99999,
        'paginas' => []
    ]);
    check('C12 rol inexistente 404', strpos($out, 'Rol no encontrado') !== false, 'out=' . substr($out, 0, 120));

    @unlink($helper);

} catch (\Throwable $e) {
    echo "\nEXCEPCION: " . get_class($e) . ': ' . $e->getMessage() . "\n";
    $failed++;
} finally {
    echo "\n[Cleanup]\n";
    if ($tempRolPermId !== null) {
        try { $pdo->exec("DELETE FROM rol WHERE id = $tempRolPermId"); echo "  OK   rol perm dummy eliminado (cascada)\n"; } catch (\Throwable $e) { echo "  WARN: " . $e->getMessage() . "\n"; }
    }
    if ($createdRolId !== null) {
        try { $pdo->exec("DELETE FROM rol WHERE id = $createdRolId"); echo "  OK   rol dummy eliminado\n"; } catch (\Throwable $e) { echo "  WARN: " . $e->getMessage() . "\n"; }
    }
    if ($originalPassword1 !== null && $originalPassword2 !== null) {
        try {
            $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = 1")->execute([$originalPassword1]);
            $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = 2")->execute([$originalPassword2]);
            echo "  OK   hashes restaurados\n";
        } catch (\Throwable $e) { echo "  WARN restore: " . $e->getMessage() . "\n"; }
    }
    @unlink(__DIR__ . '/_api_helper_c.php');
}

echo "\n────────────────────────\n";
echo "Resultado: $passed pasaron, $failed fallaron\n";
exit($failed === 0 ? 0 : 1);