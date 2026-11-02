<?php
// api_tickets.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\TicketController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new TicketController($pdo);

    if ($method === 'GET') {
        Auth::requirePageAccess($pdo, 'tickets');
        Auth::requireOperation($pdo, 'tickets', 'leer');
        $controller->index();
    } elseif ($method === 'POST') {
        $staffSession = Auth::requirePageAccess($pdo, 'tickets');
        Auth::requireOperation($pdo, 'tickets', 'crear');
        $controller->store($staffSession);
    } elseif ($method === 'PUT') {
        Auth::requirePageAccess($pdo, 'tickets');
        Auth::requireOperation($pdo, 'tickets', 'editar');
        $controller->update();
    } elseif ($method === 'DELETE') {
        Auth::requirePageAccess($pdo, 'tickets');
        Auth::requireOperation($pdo, 'tickets', 'eliminar');
        $controller->destroy();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}