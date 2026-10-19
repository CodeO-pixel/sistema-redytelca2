<?php
// roles_eliminar.php
//
// Acepta POST (compatibilidad con el frontend viejo) y DELETE.
// Deuda 139d-1: en 140, el frontend nuevo puede usar DELETE y se
// puede retirar el POST.
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

    if ($method === 'POST' || $method === 'DELETE') {
        Auth::requirePageAccess($pdo, 'roles');
        Auth::requireOperation($pdo, 'roles', 'eliminar');
        $controller->destroy();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}