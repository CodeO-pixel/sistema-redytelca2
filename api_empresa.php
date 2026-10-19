<?php
// api_empresa.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core/Bootstrap.php';

use App\Controllers\EmpresaController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new EmpresaController($pdo);

    if ($method === 'GET') {
        Auth::requirePageAccess($pdo, 'empresa');
        Auth::requireOperation($pdo, 'empresa', 'leer');
        $controller->index();
    } elseif ($method === 'POST') {
        Auth::requirePageAccess($pdo, 'empresa');
        Auth::requireOperation($pdo, 'empresa', 'editar');
        $controller->store();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}