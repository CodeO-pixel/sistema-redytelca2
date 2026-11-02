<?php
// api_portal_servicios.php
// Thin file del portal de cliente: lista y detalle de servicios.
//
// Gate: Auth::requireClientAuth (NO requirePageAccess). El cliente
// autenticado ve SOLO sus propios servicios. El `cliente_id` se toma
// de la sesión, NUNCA del body ni de la query string (BOLA/IDOR,
// OWASP API Security Top 10 2023 API1:2023).
//
// GET api_portal_servicios.php?page=1&per_page=20  → lista paginada.
// GET api_portal_servicios.php?id=N                → detalle.
//
// Sin bypass de must_change_password: si el cliente tiene contraseña
// temporal, recibe 403 hasta que la cambie (OWASP ASVS V2.4).
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\PortalServicioController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new PortalServicioController($pdo);

    if ($method === 'GET') {
        $clienteSession = Auth::requireClientAuth($pdo);
        $controller->index($clienteSession);
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}