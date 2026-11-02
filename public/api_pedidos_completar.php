<?php
// api_pedidos_completar.php
// F2.35 — cierre del pedido: asigna el equipo reservado al servicio
// y activa el servicio si estaba pendiente. Todo en transacción.
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

    if ($method === 'PUT') {
        Auth::requirePageAccess($pdo, 'pedidos');
        Auth::requireOperation($pdo, 'pedidos', 'editar');
        $controller->completar();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}