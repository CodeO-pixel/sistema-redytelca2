<?php
// roles_listar.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core/Bootstrap.php';

use App\Controllers\RolController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new RolController($pdo);

    if ($method === 'GET') {
        Auth::requirePageAccess($pdo, 'roles');
        Auth::requireOperation($pdo, 'roles', 'leer');
        $controller->index();
    } elseif ($method === 'POST') {
        Auth::requirePageAccess($pdo, 'roles');
        Auth::requireOperation($pdo, 'roles', 'crear');
        $controller->store();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}