<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core/Bootstrap.php';

use App\Controllers\FacturaController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new FacturaController($pdo);

    if ($method === 'POST') {
        Auth::requirePageAccess($pdo, 'facturas');
        $controller->materializarVencimientos();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}