<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core/Bootstrap.php';

use App\Controllers\TareaController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new TareaController($pdo);

    if ($method === 'GET') {
        Auth::requirePageAccess($pdo, 'tareas');
        Auth::requireOperation($pdo, 'tareas', 'leer');
        $controller->index();
    } elseif ($method === 'POST') {
        Auth::requirePageAccess($pdo, 'tareas');
        Auth::requireOperation($pdo, 'tareas', 'crear');
        $controller->store();
    } elseif ($method === 'PUT') {
        Auth::requirePageAccess($pdo, 'tareas');
        Auth::requireOperation($pdo, 'tareas', 'editar');
        $controller->update();
    } elseif ($method === 'DELETE') {
        Auth::requirePageAccess($pdo, 'tareas');
        Auth::requireOperation($pdo, 'tareas', 'eliminar');
        $controller->destroy();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}