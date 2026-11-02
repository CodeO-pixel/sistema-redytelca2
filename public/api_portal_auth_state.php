<?php
// api_portal_auth_state.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\PortalAuthController;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new PortalAuthController($pdo);

    if ($method === 'GET') {
        // NO usa requireClientAuth: el cliente con must_change_password=1
        // debe poder consultar su estado. El service valida el token y
        // devuelve {authenticated:false} si no es válido.
        $controller->state();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}