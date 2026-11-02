<?php
// api_factura_lineas.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\FacturaLineaController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new FacturaLineaController($pdo);

    if ($method === 'GET') {
        Auth::requirePageAccess($pdo, 'facturas');
        Auth::requireOperation($pdo, 'facturas', 'leer');
        $controller->index();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}