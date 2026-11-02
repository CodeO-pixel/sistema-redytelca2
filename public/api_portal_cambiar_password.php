<?php
// api_portal_cambiar_password.php
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
        // Bypass must_change_password: el cliente debe poder cambiar su
        // contraseña temporal.
        $clienteSession = Auth::requireClientAuth($pdo, true);
        $controller->cambiarPassword($clienteSession);
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}