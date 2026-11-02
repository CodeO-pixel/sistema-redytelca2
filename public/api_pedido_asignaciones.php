<?php
// api_pedido_asignaciones.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\PedidoAsignacionController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new PedidoAsignacionController($pdo);

    if ($method === 'GET') {
        Auth::requirePageAccess($pdo, 'pedidos');
        Auth::requireOperation($pdo, 'pedidos', 'leer');
        $controller->index();
    } elseif ($method === 'POST') {
        $staffSession = Auth::requirePageAccess($pdo, 'pedidos');
        Auth::requireOperation($pdo, 'pedidos', 'editar');
        $controller->store($staffSession);
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}