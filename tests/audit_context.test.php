<?php
// tests/audit_context.test.php
// Spec 019 — Verifica Auth::setAuditContext().
//
// Migrado de conexion.php a core/Auth.php en 139d Tanda E.
// Usa Bootstrap::init() directamente; no depende de conexion.php.

require_once __DIR__ . '/../core/Bootstrap.php';

use App\Core\Auth;
use App\Core\Bootstrap;

$pdo = Bootstrap::init();

return [
    'setAuditContext("staff", 42, null) → @app_usuario_id=42, @app_credencial_id=NULL, @app_tipo_actor="staff"' => function () use ($pdo) {
        Auth::setAuditContext($pdo, 'staff', 42, null);
        $row = $pdo->query("SELECT @app_usuario_id AS u, @app_credencial_id AS c, @app_tipo_actor AS t")
                    ->fetch(PDO::FETCH_ASSOC);
        assertSame(42, (int) $row['u'], '@app_usuario_id');
        assertNull($row['c'], '@app_credencial_id debe ser NULL para staff');
        assertSame('staff', $row['t'], '@app_tipo_actor');
    },

    'setAuditContext("cliente", null, 99) → @app_usuario_id=NULL, @app_credencial_id=99, @app_tipo_actor="cliente"' => function () use ($pdo) {
        Auth::setAuditContext($pdo, 'cliente', null, 99);
        $row = $pdo->query("SELECT @app_usuario_id AS u, @app_credencial_id AS c, @app_tipo_actor AS t")
                    ->fetch(PDO::FETCH_ASSOC);
        assertNull($row['u'], '@app_usuario_id debe ser NULL para cliente');
        assertSame(99, (int) $row['c'], '@app_credencial_id');
        assertSame('cliente', $row['t'], '@app_tipo_actor');
    },

    'setAuditContext con tipoActor inválido lanza InvalidArgumentException' => function () use ($pdo) {
        assertThrows(function () use ($pdo) {
            Auth::setAuditContext($pdo, 'admin', 1, null);
        }, InvalidArgumentException::class);
    },

    'setAuditContext("staff", 7, null) sobrescribe un contexto anterior de cliente' => function () use ($pdo) {
        Auth::setAuditContext($pdo, 'cliente', null, 55);
        Auth::setAuditContext($pdo, 'staff', 7, null);
        $row = $pdo->query("SELECT @app_usuario_id AS u, @app_credencial_id AS c, @app_tipo_actor AS t")
                    ->fetch(PDO::FETCH_ASSOC);
        assertSame(7, (int) $row['u']);
        assertNull($row['c'], '@app_credencial_id debe quedar NULL al cambiar a staff');
        assertSame('staff', $row['t']);
    },

    'Fallback: con variables reseteadas a NULL, COALESCE(@app_tipo_actor, "sistema") = "sistema"' => function () use ($pdo) {
        $pdo->exec("SET @app_usuario_id = NULL, @app_credencial_id = NULL, @app_tipo_actor = NULL");
        $valor = $pdo->query("SELECT COALESCE(@app_tipo_actor, 'sistema')")->fetchColumn();
        assertSame('sistema', $valor, 'Sin contexto seteado, los triggers deben caer al fallback "sistema"');
    },
];