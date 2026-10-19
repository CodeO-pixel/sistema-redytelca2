<?php
declare(strict_types=1);

/**
 * tests/verify_138.php
 * Verificación de spec 138 (16 validadores).
 *
 * Parte A — estática: existencia, sintaxis, contrato de clase,
 *   ausencia de SQL/repos/PDO.
 * Parte B — funcional: por cada validador, payload válido (no lanza)
 *   + payload inválido por campo (lanza ValidationException con la
 *   clave correcta).
 *
 * No toca BD. Solo valida forma.
 *
 * Uso: php tests/verify_138.php
 */

require_once __DIR__ . '/../core/Bootstrap.php';

\App\Core\Bootstrap::registerAutoloader();

use App\Exceptions\ValidationException;
use App\Validators\ClienteValidator;
use App\Validators\ServicioValidator;
use App\Validators\ContratoValidator;
use App\Validators\PlanValidator;
use App\Validators\FacturaValidator;
use App\Validators\PagoValidator;
use App\Validators\NodoValidator;
use App\Validators\OltValidator;
use App\Validators\NapValidator;
use App\Validators\EquipoValidator;
use App\Validators\TicketValidator;
use App\Validators\TareaValidator;
use App\Validators\PedidoValidator;
use App\Validators\UsuarioValidator;
use App\Validators\RolValidator;
use App\Validators\PermisoValidator;

$passed = 0;
$failed = 0;

$ok = function (string $name) use (&$passed) {
    $passed++;
    echo "  ✓ $name\n";
};
$fail = function (string $name, string $detail = '') use (&$failed) {
    $failed++;
    echo "  ✗ $name" . ($detail ? " — $detail" : '') . "\n";
};

// ─────────────────────────────────────────────────────────────────
// PARTE A — Verificación estática
// ─────────────────────────────────────────────────────────────────
echo "\n▶ Parte A — Verificación estática\n";

$validators = [
    'ClienteValidator', 'ServicioValidator', 'ContratoValidator',
    'PlanValidator', 'FacturaValidator', 'PagoValidator',
    'NodoValidator', 'OltValidator', 'NapValidator', 'EquipoValidator',
    'TicketValidator', 'TareaValidator', 'PedidoValidator',
    'UsuarioValidator', 'RolValidator', 'PermisoValidator',
];

$dir = __DIR__ . '/../validators';

foreach ($validators as $class) {
    $file = "$dir/$class.php";

    if (!file_exists($file)) {
        $fail("$class — archivo existe", "no encontrado");
        continue;
    }

    $src = file_get_contents($file);

    // php -l
    $out = [];
    $ret = 0;
    exec('php -l ' . escapeshellarg($file) . ' 2>&1', $out, $ret);
    if ($ret !== 0) {
        $fail("$class — sintaxis", implode(' ', $out));
        continue;
    }

    if (strpos($src, 'declare(strict_types=1);') === false) {
        $fail("$class — declare(strict_types=1)"); continue;
    }
    if (strpos($src, 'namespace App\\Validators;') === false) {
        $fail("$class — namespace App\\Validators"); continue;
    }
    if (strpos($src, "final class $class") === false) {
        $fail("$class — final class"); continue;
    }
    if (strpos($src, 'function validateCreate(') === false) {
        $fail("$class — validateCreate"); continue;
    }
    if (strpos($src, 'function validateUpdate(') === false) {
        $fail("$class — validateUpdate"); continue;
    }
    if (substr(rtrim($src), -2) === '?>') {
        $fail("$class — termina con ?>"); continue;
    }

    // Sin SQL ni repositorios
    $forbidden = ['->prepare(', '->query(', 'new \\PDO', 'use App\\Repositories\\', '->pdo->'];
    foreach ($forbidden as $token) {
        if (strpos($src, $token) !== false) {
            $fail("$class — contiene '$token' (debe ser puro)"); continue 2;
        }
    }

    $ok("$class — contrato estático OK");
}

// ─────────────────────────────────────────────────────────────────
// PARTE B — Verificación funcional
// ─────────────────────────────────────────────────────────────────

/**
 * Prueba que un payload válido no lanza y retorna array.
 */
function assertValid(string $label, callable $fn, array $payload): void {
    global $ok, $fail;
    try {
        $result = $fn($payload);
        if (is_array($result)) {
            $ok("$label — payload válido pasa");
        } else {
            $fail("$label — payload válido", "no retornó array");
        }
    } catch (\Throwable $e) {
        $fail("$label — payload válido", get_class($e) . ': ' . $e->getMessage());
    }
}

/**
 * Prueba que un payload inválido lanza ValidationException con la
 * clave esperada en errors().
 */
function assertInvalid(string $label, callable $fn, array $payload, string $expectedField): void {
    global $ok, $fail;
    try {
        $fn($payload);
        $fail("$label — payload inválido", "no lanzó excepción");
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
        if (isset($errors[$expectedField])) {
            $ok("$label — inválido ($expectedField) rechazado");
        } else {
            $fail("$label — inválido", "lanzó pero sin clave '$expectedField'. Errores: " . implode(', ', array_keys($errors)));
        }
    } catch (\Throwable $e) {
        $fail("$label — inválido", get_class($e) . ': ' . $e->getMessage());
    }
}

echo "\n▶ Parte B — Verificación funcional\n";

// ── ClienteValidator ──
echo "\n  ClienteValidator\n";
$payloadCliente = ['nombre' => 'Juan', 'apellido' => 'Pérez', 'cedula' => 'V-12345678', 'correo' => 'juan@test.com'];
assertValid('Cliente::create', fn($p) => ClienteValidator::validateCreate($p), $payloadCliente);
assertInvalid('Cliente::create sin nombre', fn($p) => ClienteValidator::validateCreate($p), array_diff_key($payloadCliente, ['nombre' => 1]), 'nombre');
assertInvalid('Cliente::create cédula inválida', fn($p) => ClienteValidator::validateCreate($p), array_merge($payloadCliente, ['cedula' => 'ABC']), 'cedula');
assertInvalid('Cliente::create email inválido', fn($p) => ClienteValidator::validateCreate($p), array_merge($payloadCliente, ['correo' => 'no-es-email']), 'correo');
assertValid('Cliente::update vacío', fn($p) => ClienteValidator::validateUpdate($p), []);

// ── ServicioValidator ──
echo "\n  ServicioValidator\n";
$payloadServicio = ['cliente_id' => 1, 'plan_id' => 1];
assertValid('Servicio::create', fn($p) => ServicioValidator::validateCreate($p), $payloadServicio);
assertInvalid('Servicio::create sin cliente_id', fn($p) => ServicioValidator::validateCreate($p), ['plan_id' => 1], 'cliente_id');
assertInvalid('Servicio::create cliente_id=0', fn($p) => ServicioValidator::validateCreate($p), ['cliente_id' => 0, 'plan_id' => 1], 'cliente_id');
assertValid('Servicio::update vacío', fn($p) => ServicioValidator::validateUpdate($p), []);

// ── ContratoValidator ──
echo "\n  ContratoValidator\n";
$payloadContrato = ['servicio_id' => 1, 'fecha_inicio' => '2026-01-01'];
assertValid('Contrato::create', fn($p) => ContratoValidator::validateCreate($p), $payloadContrato);
assertInvalid('Contrato::create sin fecha_inicio', fn($p) => ContratoValidator::validateCreate($p), ['servicio_id' => 1], 'fecha_inicio');
assertInvalid('Contrato::create fecha inválida', fn($p) => ContratoValidator::validateCreate($p), ['servicio_id' => 1, 'fecha_inicio' => '31/01/2026'], 'fecha_inicio');
assertInvalid('Contrato::create ENUM inválido', fn($p) => ContratoValidator::validateCreate($p), array_merge($payloadContrato, ['tipo_contrato' => 'invalido']), 'tipo_contrato');
assertValid('Contrato::update vacío', fn($p) => ContratoValidator::validateUpdate($p), []);

// ── PlanValidator ──
echo "\n  PlanValidator\n";
$payloadPlan = ['nombre' => 'Plan Test', 'precio_mensual' => 10];
assertValid('Plan::create', fn($p) => PlanValidator::validateCreate($p), $payloadPlan);
assertInvalid('Plan::create sin precio', fn($p) => PlanValidator::validateCreate($p), ['nombre' => 'Plan Test'], 'precio_mensual');
assertInvalid('Plan::create precio negativo', fn($p) => PlanValidator::validateCreate($p), ['nombre' => 'Plan Test', 'precio_mensual' => -5], 'precio_mensual');
assertValid('Plan::update vacío', fn($p) => PlanValidator::validateUpdate($p), []);

// ── FacturaValidator ──
echo "\n  FacturaValidator\n";
$payloadFactura = [
    'servicio_id' => 1, 'periodo' => '2026-01', 'monto' => 10,
    'fecha_emision' => '2026-01-01', 'fecha_vencimiento' => '2026-01-31',
];
assertValid('Factura::create USD', fn($p) => FacturaValidator::validateCreate($p), $payloadFactura);
assertValid('Factura::create VES con tasa', fn($p) => FacturaValidator::validateCreate($p), array_merge($payloadFactura, ['moneda' => 'VES', 'tasa_cambio' => 36.5]));
assertInvalid('Factura::create VES sin tasa', fn($p) => FacturaValidator::validateCreate($p), array_merge($payloadFactura, ['moneda' => 'VES']), 'tasa_cambio');
assertInvalid('Factura::create periodo inválido', fn($p) => FacturaValidator::validateCreate($p), array_merge($payloadFactura, ['periodo' => '2026/01']), 'periodo');
assertInvalid('Factura::create monto 0', fn($p) => FacturaValidator::validateCreate($p), array_merge($payloadFactura, ['monto' => 0]), 'monto');
assertValid('Factura::update vacío', fn($p) => FacturaValidator::validateUpdate($p), []);
assertValid('Factura::update VES sin tasa (service resuelve)', fn($p) => FacturaValidator::validateUpdate($p), ['moneda' => 'VES']);

// ── PagoValidator ──
echo "\n  PagoValidator\n";
$payloadPago = ['cliente_id' => 1, 'servicio_id' => 1, 'monto' => 10, 'fecha_pago' => '2026-01-01 10:00:00'];
assertValid('Pago::create USD', fn($p) => PagoValidator::validateCreate($p), $payloadPago);
assertValid('Pago::create VES con tasa', fn($p) => PagoValidator::validateCreate($p), array_merge($payloadPago, ['moneda_pago' => 'VES', 'tasa_cambio' => 36.5]));
assertInvalid('Pago::create VES sin tasa', fn($p) => PagoValidator::validateCreate($p), array_merge($payloadPago, ['moneda_pago' => 'VES']), 'tasa_cambio');
assertInvalid('Pago::create sin monto', fn($p) => PagoValidator::validateCreate($p), ['cliente_id' => 1, 'servicio_id' => 1, 'fecha_pago' => '2026-01-01 10:00:00'], 'monto');
assertValid('Pago::update vacío', fn($p) => PagoValidator::validateUpdate($p), []);
assertValid('Pago::update VES sin tasa (service resuelve)', fn($p) => PagoValidator::validateUpdate($p), ['moneda_pago' => 'VES']);

// ── NodoValidator ──
echo "\n  NodoValidator\n";
$payloadNodo = ['nombre' => 'Nodo Test', 'latitud' => 10.5, 'longitud' => -66.9];
assertValid('Nodo::create', fn($p) => NodoValidator::validateCreate($p), $payloadNodo);
assertInvalid('Nodo::create latitud fuera de rango', fn($p) => NodoValidator::validateCreate($p), array_merge($payloadNodo, ['latitud' => 100]), 'latitud');
assertInvalid('Nodo::create longitud fuera de rango', fn($p) => NodoValidator::validateCreate($p), array_merge($payloadNodo, ['longitud' => -200]), 'longitud');
assertInvalid('Nodo::create sin nombre', fn($p) => NodoValidator::validateCreate($p), ['latitud' => 10.5, 'longitud' => -66.9], 'nombre');
assertValid('Nodo::update vacío', fn($p) => NodoValidator::validateUpdate($p), []);

// ── OltValidator ──
echo "\n  OltValidator\n";
$payloadOlt = ['marca_modelo' => 'Huawei MA5616', 'puertos_pon' => 16, 'nodo_id' => 1];
assertValid('Olt::create', fn($p) => OltValidator::validateCreate($p), $payloadOlt);
assertInvalid('Olt::create sin marca', fn($p) => OltValidator::validateCreate($p), ['puertos_pon' => 16, 'nodo_id' => 1], 'marca_modelo');
assertInvalid('Olt::create puertos_pon=0', fn($p) => OltValidator::validateCreate($p), ['marca_modelo' => 'X', 'puertos_pon' => 0, 'nodo_id' => 1], 'puertos_pon');
assertValid('Olt::update vacío', fn($p) => OltValidator::validateUpdate($p), []);

// ── NapValidator ──
echo "\n  NapValidator\n";
$payloadNap = ['codigo' => 'NAP-TEST', 'latitud' => 10.5, 'longitud' => -66.9, 'olt_id' => 1];
assertValid('Nap::create', fn($p) => NapValidator::validateCreate($p), $payloadNap);
assertInvalid('Nap::create sin código', fn($p) => NapValidator::validateCreate($p), ['latitud' => 10.5, 'longitud' => -66.9, 'olt_id' => 1], 'codigo');
assertInvalid('Nap::create latitud inválida', fn($p) => NapValidator::validateCreate($p), array_merge($payloadNap, ['latitud' => 200]), 'latitud');
assertValid('Nap::update vacío', fn($p) => NapValidator::validateUpdate($p), []);

// ── EquipoValidator ──
echo "\n  EquipoValidator\n";
$payloadEquipo = ['tipo' => 'ONU', 'direccion_mac' => 'AA:BB:CC:DD:EE:FF', 'puerto_nap' => 1, 'nap_id' => 1];
assertValid('Equipo::create', fn($p) => EquipoValidator::validateCreate($p), $payloadEquipo);
assertInvalid('Equipo::create MAC inválida', fn($p) => EquipoValidator::validateCreate($p), array_merge($payloadEquipo, ['direccion_mac' => 'AABBCCDDEEFF']), 'direccion_mac');
assertInvalid('Equipo::create tipo inválido', fn($p) => EquipoValidator::validateCreate($p), array_merge($payloadEquipo, ['tipo' => 'ONT']), 'tipo');
assertInvalid('Equipo::create puerto_nap=0', fn($p) => EquipoValidator::validateCreate($p), array_merge($payloadEquipo, ['puerto_nap' => 0]), 'puerto_nap');
assertValid('Equipo::update vacío', fn($p) => EquipoValidator::validateUpdate($p), []);

// ── TicketValidator ──
echo "\n  TicketValidator\n";
$payloadTicket = ['asunto' => 'Ticket Test', 'cliente_id' => 1];
assertValid('Ticket::create', fn($p) => TicketValidator::validateCreate($p), $payloadTicket);
assertInvalid('Ticket::create sin asunto', fn($p) => TicketValidator::validateCreate($p), ['cliente_id' => 1], 'asunto');
assertInvalid('Ticket::create estado inválido', fn($p) => TicketValidator::validateCreate($p), array_merge($payloadTicket, ['estado' => 'Nuevo']), 'estado');
assertValid('Ticket::update vacío', fn($p) => TicketValidator::validateUpdate($p), []);

// ── TareaValidator ──
echo "\n  TareaValidator\n";
$payloadTarea = ['titulo' => 'Tarea Test'];
assertValid('Tarea::create', fn($p) => TareaValidator::validateCreate($p), $payloadTarea);
assertInvalid('Tarea::create sin título', fn($p) => TareaValidator::validateCreate($p), [], 'titulo');
assertInvalid('Tarea::create prioridad inválida', fn($p) => TareaValidator::validateCreate($p), array_merge($payloadTarea, ['prioridad' => 'Urgente']), 'prioridad');
assertValid('Tarea::update vacío', fn($p) => TareaValidator::validateUpdate($p), []);

// ── PedidoValidator ──
echo "\n  PedidoValidator\n";
$payloadPedido = ['cliente_id' => 1, 'tipo' => 'instalacion'];
assertValid('Pedido::create', fn($p) => PedidoValidator::validateCreate($p), $payloadPedido);
assertInvalid('Pedido::create sin tipo', fn($p) => PedidoValidator::validateCreate($p), ['cliente_id' => 1], 'tipo');
assertInvalid('Pedido::create tipo inválido', fn($p) => PedidoValidator::validateCreate($p), array_merge($payloadPedido, ['tipo' => 'otro']), 'tipo');
assertValid('Pedido::update vacío', fn($p) => PedidoValidator::validateUpdate($p), []);

// ── UsuarioValidator ──
echo "\n  UsuarioValidator\n";
$payloadUsuario = ['username' => 'testuser', 'password' => 'password123', 'email' => 'test@test.com'];
assertValid('Usuario::create', fn($p) => UsuarioValidator::validateCreate($p), $payloadUsuario);
assertInvalid('Usuario::create sin password', fn($p) => UsuarioValidator::validateCreate($p), ['username' => 'testuser', 'email' => 'test@test.com'], 'password');
assertInvalid('Usuario::create password corta', fn($p) => UsuarioValidator::validateCreate($p), ['username' => 'testuser', 'password' => 'abc', 'email' => 'test@test.com'], 'password');
assertInvalid('Usuario::create email inválido', fn($p) => UsuarioValidator::validateCreate($p), ['username' => 'testuser', 'password' => 'password123', 'email' => 'noemail'], 'email');
assertInvalid('Usuario::create estado inválido', fn($p) => UsuarioValidator::validateCreate($p), array_merge($payloadUsuario, ['estado' => 'Suspendido']), 'estado');
assertValid('Usuario::update sin password', fn($p) => UsuarioValidator::validateUpdate($p), ['username' => 'testuser', 'email' => 'test@test.com']);

// ── RolValidator ──
echo "\n  RolValidator\n";
assertValid('Rol::create', fn($p) => RolValidator::validateCreate($p), ['nombre_rol' => 'Test']);
assertInvalid('Rol::create sin nombre_rol', fn($p) => RolValidator::validateCreate($p), [], 'nombre_rol');
assertValid('Rol::update vacío', fn($p) => RolValidator::validateUpdate($p), []);

// ── PermisoValidator ──
echo "\n  PermisoValidator\n";
assertValid('Permiso::create', fn($p) => PermisoValidator::validateCreate($p), ['nombre' => 'test']);
assertInvalid('Permiso::create sin nombre', fn($p) => PermisoValidator::validateCreate($p), [], 'nombre');
assertValid('Permiso::update vacío', fn($p) => PermisoValidator::validateUpdate($p), []);

// ─────────────────────────────────────────────────────────────────
echo "\n────────────────────────\n";
echo "Resultado: $passed pasaron, $failed fallaron\n";
exit($failed === 0 ? 0 : 1);