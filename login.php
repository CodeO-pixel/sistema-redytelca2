<?php
header('Content-Type: application/json; charset=utf-8');
require 'conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function loadAllowedViews(PDO $pdo, int $roleId): array {
    $stmt = $pdo->prepare("SELECT m.nombre_modulo AS modulo, p.nombre_pagina AS vista, p.url_pagina AS url
        FROM rol_modulo_pagina rmp
        INNER JOIN modulos m ON rmp.id_modulo = m.id_modulo
        INNER JOIN paginas p ON rmp.id_pagina = p.id_pagina
        WHERE rmp.id_rol = ?
        ORDER BY m.id_modulo, p.id_pagina");
    $stmt->execute([$roleId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return array_map(fn($row) => [
        'modulo' => $row['modulo'],
        'vista' => $row['vista'],
        'url' => $row['url']
    ], $rows);
}

$input = json_decode(file_get_contents('php://input'), true);
$username = isset($input['username']) ? trim($input['username']) : '';
$password = isset($input['password']) ? trim($input['password']) : '';

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Credenciales incompletas.']);
    exit;
}

try {
    // PASO 1: Extraer todas las columnas, incluyendo las de fuerza bruta
    // Añadimos la evaluación booleana en MySQL para evitar mismatch de timezones
    $stmtUser = $pdo->prepare("SELECT id_usuario, username, password, id_rol, must_change_password, intentos_fallidos, bloqueado_hasta,
        (bloqueado_hasta IS NOT NULL AND bloqueado_hasta > NOW()) AS esta_bloqueado
        FROM usuarios WHERE username = ?");
    $stmtUser->execute([$username]);
    $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$userRow) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Usuario o contraseña incorrectos.']);
        exit;
    }

    // PASO 2: Gatekeeper - Verificar si la cuenta está bloqueada temporalmente
    // Ahora delegamos la comparación temporal a MySQL usando la columna calculada `esta_bloqueado`
    if (!empty($userRow['esta_bloqueado'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Cuenta bloqueada por múltiples intentos fallidos. Intente en 15 minutos.']);
        exit;
    }

    // PASO 3: Verificar contraseña
    $credencialesValidas = verificarYMigrarPassword($pdo, $password, $userRow['password'], (int) $userRow['id_usuario']);

    if (!$credencialesValidas) {
        // Fallo: Incrementar intentos
        $intentos = (int)$userRow['intentos_fallidos'] + 1;
        if ($intentos >= 5) {
            $stmtLock = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = ?, bloqueado_hasta = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id_usuario = ?");
            $stmtLock->execute([$intentos, $userRow['id_usuario']]);
        } else {
            $stmtFail = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = ? WHERE id_usuario = ?");
            $stmtFail->execute([$intentos, $userRow['id_usuario']]);
        }
        
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Usuario o contraseña incorrectos.']);
        exit;
    }

    // PASO 4: Éxito - Resetear intentos a 0
    $stmtReset = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id_usuario = ?");
    $stmtReset->execute([$userRow['id_usuario']]);

    $stmtRole = $pdo->prepare("SELECT nombre_rol FROM rol WHERE id_rol = ?");
    $stmtRole->execute([$userRow['id_rol']]);
    $roleRow = $stmtRole->fetch();

    $modules = loadAllowedViews($pdo, (int) $userRow['id_rol']);

    $stmtDelSesiones = $pdo->prepare(
        "DELETE FROM sesiones WHERE id_usuario = ? AND expira_en < DATE_SUB(NOW(), INTERVAL 60 DAY)"
    );
    $stmtDelSesiones->execute([$userRow['id_usuario']]);

    $token = bin2hex(random_bytes(32));
    $expiraEn = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    // PASO 5: Auditoría - Capturar IP y User Agent
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmtSesion = $pdo->prepare("INSERT INTO sesiones (token, id_usuario, expira_en, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmtSesion->execute([$token, $userRow['id_usuario'], $expiraEn, $ipAddress, $userAgent]);

    $_SESSION['auth_token'] = $token;
    $_SESSION['id_usuario'] = (int) $userRow['id_usuario'];
    $_SESSION['id_rol'] = (int) $userRow['id_rol'];
    $_SESSION['usuario'] = $userRow['username'];
    $_SESSION['rol_nombre'] = $roleRow['nombre_rol'] ?? 'Rol';
    $_SESSION['modulos_permitidos'] = $modules;

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'usuario' => $userRow['username'],
        'id_rol' => (int) $userRow['id_rol'],
        'rol_nombre' => $_SESSION['rol_nombre'],
        'token' => $token,
        'modulos_permitidos' => $modules,
        'must_change_password' => isset($userRow['must_change_password']) && $userRow['must_change_password'] == 1
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error interno del servidor al procesar el control de accesos.']);
}
/*
    Corrección: Se reemplazaron caracteres de reemplazo Unicode
    por sus equivalentes UTF-8 correctos en literales y comentarios:
    - "está" en el paso 2
    - "contraseña" en mensajes de error
    - "Auditoría" en el paso 5
    Motivo: el archivo fue guardado previamente con una codificación incorrecta
    (Latin-1 / Windows-1252) que corrompió caracteres acentuados. No se
    modificó ninguna lógica de negocio ni consultas SQL; cambios estrictos
    y limitados a strings literales para corregir la presentación al usuario.
*/
?>
