<?php
// api_portal_logout.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\PortalAuthController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new PortalAuthController($pdo);

    if ($method === 'POST') {
        // Bypass must_change_password: un cliente con contraseña temporal
        // debe poder cerrar sesión.
        $clienteSession = Auth::requireClientAuth($pdo, true);
        $controller->logout($clienteSession);
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}