<?php
// tests/verify_139d_a.php
// Verificación Tanda A — refactor 136f-bis (9 archivos)
//
// Uso: php tests/verify_139d_a.php

declare(strict_types=1);

require_once __DIR__ . '/../core/Bootstrap.php';

$pdo = \App\Core\Bootstrap::init();

$passed = 0;
$failed = 0;

function check(string $name, bool $ok, string $detail = ''): void {
    global $passed, $failed;
    if ($ok) {
        $passed++;
        echo "  OK   $name\n";
    } else {
        $failed++;
        echo "  FAIL $name" . ($detail !== '' ? " — $detail" : '') . "\n";
    }
}

$origHash1   = null;
$origHash2   = null;
$dummyUserId = null;
$dummyRolId  = null;

try {
    echo "\n[Setup] Reset temporal de contraseñas\n";

    $origHash1 = $pdo->query("SELECT password FROM usuarios WHERE id = 1")->fetchColumn();
    $origHash2 = $pdo->query("SELECT password FROM usuarios WHERE id = 2")->fetchColumn();

    if (!$origHash1 || !$origHash2) {
        throw new \RuntimeException('No se encontraron usuarios id 1 y 2.');
    }

    $knownHash = password_hash('Verify139dA!', PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE usuarios SET password = ? WHERE id IN (1, 2)")->execute([$knownHash]);
    check('contraseñas temporales aplicadas', true);

    // =====================================================================
    // FASE 3 — AuthService::login + validateSession
    // =====================================================================
    echo "\n[Fase 3] AuthService::login + validateSession\n";

    $authSvc = new \App\Services\AuthService($pdo);
    $tokenAdmin = null;
    $tokenOper  = null;

    try {
        $r = $authSvc->login('admin', 'Verify139dA!', '127.0.0.1', 'cli');
        $tokenAdmin = $r['token'];
        check('3.1 login admin OK', strlen($tokenAdmin) === 64 && ctype_xdigit($tokenAdmin));
        check('3.1 login admin: 1 rol (Admin)', count($r['roles']) === 1 && $r['roles'][0] === 1);

        $ids = array_unique(array_map(fn($m) => (int) $m['id_modulo'], $r['modules']));
        sort($ids);
        check('3.1 login admin: 7 módulos distintos', count($ids) === 7, 'ids=' . implode(',', $ids));
        check('3.1 login admin: incluye módulo 4 (Configuración)', in_array(4, $ids, true));
    } catch (\Throwable $e) {
        check('3.1 login admin', false, get_class($e) . ': ' . $e->getMessage());
    }

    try {
        $r = $authSvc->login('Marcos', 'Verify139dA!', '127.0.0.1', 'cli');
        $tokenOper = $r['token'];
        check('3.2 login Marcos OK', strlen($tokenOper) === 64 && ctype_xdigit($tokenOper));
        check('3.2 login Marcos: 1 rol (Operador)', count($r['roles']) === 1 && $r['roles'][0] === 2);

        $ids = array_unique(array_map(fn($m) => (int) $m['id_modulo'], $r['modules']));
        sort($ids);
        check('3.2 login Marcos: 6 módulos distintos', count($ids) === 6, 'ids=' . implode(',', $ids));
        check('3.2 login Marcos: SIN módulo 4 (Configuración)', !in_array(4, $ids, true), 'ids=' . implode(',', $ids));
    } catch (\Throwable $e) {
        check('3.2 login Marcos', false, get_class($e) . ': ' . $e->getMessage());
    }

    try {
        $r = $authSvc->validateSession($tokenAdmin);
        $ids = $r ? array_unique(array_map(fn($m) => (int) $m['id_modulo'], $r['modules'])) : [];
        check('3.3 validateSession admin: 7 módulos', $r !== null && count($ids) === 7);
    } catch (\Throwable $e) {
        check('3.3 validateSession admin', false, get_class($e) . ': ' . $e->getMessage());
    }

    try {
        $r = $authSvc->validateSession($tokenOper);
        $ids = $r ? array_unique(array_map(fn($m) => (int) $m['id_modulo'], $r['modules'])) : [];
        check('3.4 validateSession Marcos: 6 módulos sin módulo 4', $r !== null && count($ids) === 6 && !in_array(4, $ids, true));
    } catch (\Throwable $e) {
        check('3.4 validateSession Marcos', false, get_class($e) . ': ' . $e->getMessage());
    }

    try {
        $authSvc->logout($tokenAdmin);
        $authSvc->logout($tokenOper);
        $sesA = $pdo->query("SELECT revocado_en FROM sesiones WHERE token = " . $pdo->quote($tokenAdmin))->fetch();
        $sesO = $pdo->query("SELECT revocado_en FROM sesiones WHERE token = " . $pdo->quote($tokenOper))->fetch();
        check('3.5 logout admin revoca sesión', $sesA && $sesA['revocado_en'] !== null);
        check('3.5 logout Marcos revoca sesión', $sesO && $sesO['revocado_en'] !== null);
    } catch (\Throwable $e) {
        check('3.5 logout', false, get_class($e) . ': ' . $e->getMessage());
    }

    // =====================================================================
    // FASE 4 — UsuarioService::desactivar
    // =====================================================================
    echo "\n[Fase 4] UsuarioService::desactivar\n";

    $dummyHash = password_hash('DummyPass!', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO usuarios (username, password, email, estado) VALUES ('verify_139d_a_user', ?, 'v139da@local.test', 'Activo')")
        ->execute([$dummyHash]);
    $dummyUserId = (int) $pdo->lastInsertId();
    $pdo->exec("INSERT INTO usuario_rol (usuario_id, rol_id) VALUES ($dummyUserId, 2)");

    $dummyToken = bin2hex(random_bytes(32));
    $expira = gmdate('Y-m-d H:i:s', strtotime('+30 days'));
    $pdo->prepare("INSERT INTO sesiones (token, tipo_usuario, usuario_id, expira_en) VALUES (?, 'staff', ?, ?)")
        ->execute([$dummyToken, $dummyUserId, $expira]);
    $dummySesionId = (int) $pdo->lastInsertId();

    check('4.0 dummy user creado (id=' . $dummyUserId . ')', $dummyUserId > 0 && $dummySesionId > 0);

    $usrSvc = new \App\Services\UsuarioService($pdo);
    try {
        $usrSvc->desactivar($dummyUserId);
        check('4.1 desactivar sin excepción', true);
    } catch (\Throwable $e) {
        check('4.1 desactivar sin excepción', false, get_class($e) . ': ' . $e->getMessage());
    }

    $estado = $pdo->query("SELECT estado FROM usuarios WHERE id = $dummyUserId")->fetchColumn();
    check('4.2 usuario estado=Inactivo', $estado === 'Inactivo', "estado=$estado");

    $sesRow = $pdo->query("SELECT revocado_en, motivo_cierre FROM sesiones WHERE id = $dummySesionId")->fetch();
    check('4.3 sesión revocada', $sesRow && $sesRow['revocado_en'] !== null);
    check('4.4 motivo_cierre=revocada_por_admin', $sesRow && $sesRow['motivo_cierre'] === 'revocada_por_admin', $sesRow['motivo_cierre'] ?? 'NULL');

    // =====================================================================
    // FASE 5 — PermisoService::guardarMatriz
    // =====================================================================
    echo "\n[Fase 5] PermisoService::guardarMatriz\n";

    $pdo->exec("INSERT INTO rol (nombre_rol) VALUES ('verify_139d_a_rol')");
    $dummyRolId = (int) $pdo->lastInsertId();
    check('5.0 rol dummy creado (id=' . $dummyRolId . ')', $dummyRolId > 0);

    $permSvc = new \App\Services\PermisoService($pdo);
    try {
        $permSvc->guardarMatriz($dummyRolId, [
            ['id_pagina' => 1, 'visible' => true, 'permisos' => [1, 2]],
        ]);
        check('5.1 guardarMatriz sin excepción', true);
    } catch (\Throwable $e) {
        check('5.1 guardarMatriz sin excepción', false, get_class($e) . ': ' . $e->getMessage());
    }

    $rmIds = $pdo->query("SELECT id FROM rol_modulo WHERE rol_id = $dummyRolId")->fetchAll(\PDO::FETCH_COLUMN);
    $rmId  = $rmIds ? (int) $rmIds[0] : 0;
    check('5.2 rol_modulo = 1 fila', count($rmIds) === 1, 'count=' . count($rmIds));

    if ($rmId > 0) {
        $nPag = (int) $pdo->query("SELECT COUNT(*) FROM rol_modulo_pagina WHERE rol_modulo_id = $rmId")->fetchColumn();
        $nPer = (int) $pdo->query("SELECT COUNT(*) FROM rol_modulo_permiso WHERE rol_modulo_id = $rmId")->fetchColumn();
        check('5.3 rol_modulo_pagina = 1 fila', $nPag === 1, "nPag=$nPag");
        check('5.4 rol_modulo_permiso = 2 filas', $nPer === 2, "nPer=$nPer");
    }
} catch (\Throwable $e) {
    echo "\nEXCEPCION NO CAPTURADA: " . get_class($e) . ': ' . $e->getMessage() . "\n";
    $failed++;
} finally {
    echo "\n[Cleanup]\n";

    if ($origHash1 !== null && $origHash2 !== null) {
        try {
            $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = 1")->execute([$origHash1]);
            $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = 2")->execute([$origHash2]);
            echo "  OK   contraseñas originales restauradas\n";
        } catch (\Throwable $e) {
            echo "  FAIL restore hash: " . $e->getMessage() . "\n";
        }
    }

    if ($dummyUserId !== null) {
        try {
            $pdo->exec("DELETE FROM sesiones WHERE usuario_id = $dummyUserId");
            $pdo->exec("DELETE FROM usuarios WHERE id = $dummyUserId");
            echo "  OK   dummy user eliminado\n";
        } catch (\Throwable $e) {
            echo "  WARN cleanup user: " . $e->getMessage() . "\n";
        }
    }

    if ($dummyRolId !== null) {
        try {
            $pdo->exec("DELETE FROM rol WHERE id = $dummyRolId");
            echo "  OK   dummy rol eliminado\n";
        } catch (\Throwable $e) {
            echo "  WARN cleanup rol: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n────────────────────────\n";
echo "Resultado: $passed pasaron, $failed fallaron\n";
exit($failed === 0 ? 0 : 1);