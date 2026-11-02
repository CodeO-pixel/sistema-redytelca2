<?php
// api_transiciones_estado.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\TransicionEstadoController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new TransicionEstadoController($pdo);

    if ($method === 'GET') {
        Auth::requirePageAccess($pdo, 'empresa');
        Auth::requireOperation($pdo, 'empresa', 'leer');
        $controller->index();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}