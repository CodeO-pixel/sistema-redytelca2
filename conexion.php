<?php
/**
 * Cabeceras CORS centralizadas. Se manejan aquí (y no repetidas en cada
 * endpoint) porque conexion.php es el único archivo que TODOS los .php
 * requieren sin excepción. Antes, auth_state.php y logout.php no tenían
 * ningún header CORS ni respondían al preflight OPTIONS: si el navegador
 * decidía enviar una petición preflight por el header custom
 * X-Session-Token, esas dos rutas la bloqueaban silenciosamente y el
 * fetch terminaba en catch(error), lo que producía un cierre de sesión
 * fantasma en cada F5.
 */
/**
 * CORS whitelist configurable por variable de entorno (Prompt 7).
 * REDYTELCA_ALLOWED_ORIGINS acepta una lista separada por comas de
 * orígenes permitidos (ej. "http://localhost,https://redytelca.com").
 * Si la variable no está definida (caso actual: desarrollo local sin
 * Docker), se usa un fallback seguro de orígenes de desarrollo típicos
 * en vez de '*', para no bloquear el trabajo actual mientras se prepara
 * el despliegue.
 */
$allowedOriginsEnv = getenv('REDYTELCA_ALLOWED_ORIGINS');
$allowedOrigins = $allowedOriginsEnv !== false && trim($allowedOriginsEnv) !== ''
    ? array_map('trim', explode(',', $allowedOriginsEnv))
    : ['http://localhost', 'http://localhost:80', 'http://127.0.0.1', 'http://127.0.0.1:80'];

$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($requestOrigin !== '' && in_array($requestOrigin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $requestOrigin);
} elseif (empty($allowedOriginsEnv)) {
    // Sin variable de entorno configurada y sin header Origin reconocible
    // (ej. petición directa de curl/Postman sin header Origin): se
    // mantiene compatibilidad con el flujo de pruebas actual del proyecto.
    header('Access-Control-Allow-Origin: ' . $allowedOrigins[0]);
}

header('Access-Control-Allow-Headers: Content-Type, X-Session-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Vary: Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$host = "localhost";
$dbname = "bd_redytelca";
$user = "root";
$pass = ""; // Por defecto en XAMPP está vacío

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Auto-reparación de esquema eliminada (Prompt 9): las columnas email,
    // must_change_password y email_verified ya son nativas en usuarios desde
    // bd_redytelca.sql (Fase 0); este blo
} catch (PDOException $e) {
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Error de conexión: " . $e->getMessage()]);
        exit;
    }
    throw $e;
}

/**
 * FASE 1 (pendiente resuelto) — MIGRACIÓN DE CONTRASEÑAS A password_hash():
 *
 * Hallazgo de la auditoría: `usuarios.password` se comparaba en texto
 * plano (`login.php`: `$userRow['password'] !== $password`), riesgo de
 * seguridad real ante cualquier acceso de lectura a la BD (backup, dump,
 * SQL injection residual, etc.).
 *
 * CORRECCIÓN: migración perezosa (lazy migration), patrón estándar para
 * este escenario. `verificarYMigrarPassword()` centraliza la lógica para
 * que login.php y cambiar_password.php no dupliquen el mismo criterio:
 *
 * 1. Si el valor almacenado ya tiene formato de hash reconocible por PHP
 *    (password_get_info devuelve un algo distinto de null), se verifica
 *    con password_verify() — comportamiento moderno normal.
 * 2. Si NO tiene formato de hash (contraseñas legadas en texto plano,
 *    como la semilla 'admin123'), se compara con hash_equals() (comparación
 *    de tiempo constante, evita timing attacks incluso en el fallback) y,
 *    si coincide, se re-escribe inmediatamente como hash bcrypt en la
 *    misma llamada. El usuario migra a hash de forma transparente en su
 *    siguiente login exitoso, sin necesidad de un script separado que
 *    alguien podría olvidar ejecutar.
 *
 * hashearPasswordNueva() se usa en cambiar_password.php/usuarios_crear.php
 * para que toda contraseña nueva o restablecida se guarde siempre hasheada
 * desde el origen, sin excepción.
 */
function verificarYMigrarPassword(PDO $pdo, string $passwordIngresada, string $passwordAlmacenada, int $idUsuario): bool {
    $infoHash = password_get_info($passwordAlmacenada);

    if ($infoHash['algo'] !== null && $infoHash['algo'] !== 0) {
        // Ya es un hash reconocido (bcrypt/argon2) -> verificación moderna.
        return password_verify($passwordIngresada, $passwordAlmacenada);
    }

    // Contraseña legada en texto plano: comparación de tiempo constante.
    if (hash_equals($passwordAlmacenada, $passwordIngresada)) {
        $nuevoHash = password_hash($passwordIngresada, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE usuarios SET password = ? WHERE id_usuario = ?');
        $stmt->execute([$nuevoHash, $idUsuario]);
        return true;
    }

    return false;
}

function hashearPasswordNueva(string $passwordPlana): string {
    return password_hash($passwordPlana, PASSWORD_DEFAULT);
}

/**
 * FASE 4 (middleware centralizado): auditoría de seguridad — el RBAC de
 * interfaz en app.js solo controla la UX y puede ser forzado por Postman;
 * por eso se centraliza aquí la validación real de sesión para staff y para
 * clientes. La intención es evitar duplicar la misma lógica en cada endpoint
 * y permitir que cualquier archivo nuevo la invoque desde una sola función
 * reutilizable, en vez de repetir el mismo patrón de lectura de token,
 * JOIN a sesiones y verificación de expiración en 3 o 4 rutas distintas.
 */
function requireStaffAuth(PDO $pdo, ?int $minRoleId = null): array {
    $token = null;

    if (isset($_SERVER['HTTP_X_SESSION_TOKEN']) && trim((string) $_SERVER['HTTP_X_SESSION_TOKEN']) !== '') {
        $token = trim((string) $_SERVER['HTTP_X_SESSION_TOKEN']);
    }

    if ($token === null || $token === '') {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'No autenticado. Inicia sesión nuevamente.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT s.id_sesion, s.id_usuario, u.username, u.id_rol
        FROM sesiones s
        INNER JOIN usuarios u ON u.id_usuario = s.id_usuario
        WHERE s.token = ?
          AND (s.tipo_usuario = 'staff' OR s.tipo_usuario IS NULL)
          AND s.expira_en > NOW()");
    $stmt->execute([$token]);
    $sesion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sesion) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Sesión inválida o expirada.']);
        exit;
    }

    $roleId = (int) $sesion['id_rol'];
    if ($minRoleId !== null && $roleId > $minRoleId) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'No tienes permisos suficientes para esta acción.']);
        exit;
    }

    return [
        'id_usuario' => (int) $sesion['id_usuario'],
        'id_rol' => $roleId,
        'username' => $sesion['username'],
    ];
}

/**
 * PROMPT 14 — Autorización DINÁMICA basada en rol_modulo_pagina, no en un
 * id_rol hardcodeado. Corrige la regresión de Prompt 13: aquel prompt cerró
 * un hueco de seguridad real (endpoints sin ninguna restricción de rol)
 * usando requireStaffAuth($pdo, 1), lo cual congeló la autorización en el
 * código PHP e hizo que la configuración de Configuración > Permisos dejara
 * de tener efecto sobre esos endpoints — contradiciendo el principio de que
 * TODO lo relacionado a permisos debe ser configurable por el Admin desde
 * la UI, no desde una constante en el código.
 *
 * Esta función consulta la MISMA tabla que ya usa auth_state.php::
 * loadAllowedViews() para decidir qué ve cada rol en el sidebar. Así, el
 * backend y el frontend comparten una única fuente de verdad: si el Admin
 * marca una página para un rol en Configuración > Permisos, ese rol pasa a
 * poder usar el endpoint correspondiente sin ningún cambio de código;  si
 * el Admin la desmarca, el endpoint empieza a rechazar en la siguiente
 * petición, sin necesidad de forzar logout (la tabla se consulta en vivo,
 * igual que auth_state.php ya hace en cada polling de 15s).
 */
function requirePageAccess(PDO $pdo, string $urlPagina): array {
    $staffSession = requireStaffAuth($pdo);

    $stmt = $pdo->prepare("SELECT rmp.id_rmp
        FROM rol_modulo_pagina rmp
        INNER JOIN paginas p ON p.id_pagina = rmp.id_pagina
        WHERE rmp.id_rol = ? AND p.url_pagina = ?");
    $stmt->execute([$staffSession['id_rol'], $urlPagina]);

    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Tu rol no tiene habilitado este módulo. Pide a un Administrador que active esta vista desde Configuración > Permisos.'
        ]);
        exit;
    }

    return $staffSession;
}

function requireClientAuth(PDO $pdo): array {
    $token = null;

    if (isset($_SERVER['HTTP_X_SESSION_TOKEN']) && trim((string) $_SERVER['HTTP_X_SESSION_TOKEN']) !== '') {
        $token = trim((string) $_SERVER['HTTP_X_SESSION_TOKEN']);
    }

    if ($token === null || $token === '') {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'No autenticado. Inicia sesión nuevamente.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT s.id_sesion, s.id_credencial, cc.id_cliente
        FROM sesiones s
        INNER JOIN clientes_credenciales cc ON cc.id_credencial = s.id_credencial
        WHERE s.token = ?
          AND s.tipo_usuario = 'cliente'
          AND s.expira_en > NOW()");
    $stmt->execute([$token]);
    $sesion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sesion) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Sesión inválida o expirada.']);
        exit;
    }

    return [
        'id_credencial' => (int) $sesion['id_credencial'],
        'id_cliente' => (int) $sesion['id_cliente'],
    ];
}
?>