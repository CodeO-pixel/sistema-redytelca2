<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core/Bootstrap.php';

use App\Controllers\PlanController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new PlanController($pdo);

    if ($method === 'GET') {
        Auth::requirePageAccess($pdo, 'planes');
        Auth::requireOperation($pdo, 'planes', 'leer');
        $controller->index();
    } elseif ($method === 'POST') {
        Auth::requirePageAccess($pdo, 'planes');
        Auth::requireOperation($pdo, 'planes', 'crear');
        $controller->store();
    } elseif ($method === 'PUT') {
        Auth::requirePageAccess($pdo, 'planes');
        Auth::requireOperation($pdo, 'planes', 'editar');
        $controller->update();
    } elseif ($method === 'DELETE') {
        Auth::requirePageAccess($pdo, 'planes');
        Auth::requireOperation($pdo, 'planes', 'eliminar');
        $controller->destroy();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}