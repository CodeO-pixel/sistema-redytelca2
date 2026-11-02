<?php
// api_cliente_credenciales.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\ClienteCredencialController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new ClienteCredencialController($pdo);

    if ($method === 'GET') {
        Auth::requirePageAccess($pdo, 'clientes');
        Auth::requireOperation($pdo, 'clientes', 'editar');
        $controller->index();
    } elseif ($method === 'POST') {
        Auth::requirePageAccess($pdo, 'clientes');
        Auth::requireOperation($pdo, 'clientes', 'editar');
        $controller->store();
    } elseif ($method === 'PUT') {
        Auth::requirePageAccess($pdo, 'clientes');
        Auth::requireOperation($pdo, 'clientes', 'editar');
        $controller->update();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}