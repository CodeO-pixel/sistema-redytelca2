<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\PedidoController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new PedidoController($pdo);

    if ($method === 'GET') {
        $staffSession = Auth::requirePageAccess($pdo, 'pedidos');
        Auth::requireOperation($pdo, 'pedidos', 'leer');
        $controller->index($staffSession);
    } elseif ($method === 'POST') {
        $staffSession = Auth::requirePageAccess($pdo, 'pedidos');
        Auth::requireOperation($pdo, 'pedidos', 'crear');
        $controller->store($staffSession);
    } elseif ($method === 'PUT') {
        Auth::requirePageAccess($pdo, 'pedidos');
        Auth::requireOperation($pdo, 'pedidos', 'editar');
        $controller->update();
    } elseif ($method === 'DELETE') {
        Auth::requirePageAccess($pdo, 'pedidos');
        Auth::requireOperation($pdo, 'pedidos', 'eliminar');
        $controller->destroy();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}