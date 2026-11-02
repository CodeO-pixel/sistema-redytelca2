<?php
// usuarios_roles.php
// Ruta dedicada para actualizar SOLO los roles de un usuario.
// Existe para evitar el bug del PUT parcial que sobrescribiría
// username/email con '' si el frontend no los envía.
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\UsuarioController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new UsuarioController($pdo);

    if ($method === 'PUT') {
        Auth::requirePageAccess($pdo, 'usuarios');
        Auth::requireOperation($pdo, 'usuarios', 'editar');
        $controller->saveRoles();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}