<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\PagoController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new PagoController($pdo);

    if ($method === 'GET') {
        Auth::requirePageAccess($pdo, 'pagos');
        Auth::requireOperation($pdo, 'pagos', 'leer');
        $controller->index();
    } elseif ($method === 'POST') {
        Auth::requirePageAccess($pdo, 'pagos');
        Auth::requireOperation($pdo, 'pagos', 'crear');
        $controller->store();
    } elseif ($method === 'PUT') {
        $staffSession = Auth::requirePageAccess($pdo, 'pagos');
        Auth::requireOperation($pdo, 'pagos', 'editar');
        $controller->update($staffSession);
    } elseif ($method === 'DELETE') {
        Auth::requirePageAccess($pdo, 'pagos');
        Auth::requireOperation($pdo, 'pagos', 'eliminar');
        $controller->destroy();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}