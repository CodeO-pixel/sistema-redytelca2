<?php
// permisos_listar.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core/Bootstrap.php';

use App\Controllers\PermisoController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new PermisoController($pdo);

    if ($method === 'GET') {
        Auth::requirePageAccess($pdo, 'permisos');
        Auth::requireOperation($pdo, 'permisos', 'leer');
        $controller->index();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}