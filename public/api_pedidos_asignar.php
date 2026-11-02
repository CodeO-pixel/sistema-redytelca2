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

    if ($method === 'POST') {
        Auth::requirePageAccess($pdo, 'pedidos');
        Auth::requireOperation($pdo, 'pedidos', 'editar');
        $controller->asignarTecnico();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}