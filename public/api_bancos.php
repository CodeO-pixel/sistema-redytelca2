<?php
// api_bancos.php
// Catálogo administrativo de bancos emisores usados en pagos del portal.
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\BancoController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new BancoController($pdo);

    if ($method === 'GET') {
        Auth::requirePageAccess($pdo, 'pagos');
        Auth::requireOperation($pdo, 'pagos', 'leer');
        $controller->index();
    } elseif ($method === 'POST') {
        Auth::requirePageAccess($pdo, 'pagos');
        Auth::requireOperation($pdo, 'pagos', 'crear');
        $controller->store();
    } elseif ($method === 'PUT') {
        Auth::requirePageAccess($pdo, 'pagos');
        Auth::requireOperation($pdo, 'pagos', 'editar');
        $controller->update();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}
