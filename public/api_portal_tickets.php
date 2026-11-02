<?php
// api_portal_tickets.php
// Thin file del portal de cliente: lista, detalle y creación de tickets.
//
// Gate: Auth::requireClientAuth (NO requirePageAccess). El cliente
// autenticado ve SOLO sus propios tickets. El `cliente_id` se toma de
// la sesión, NUNCA del body ni de la query string (BOLA/IDOR, OWASP
// API Security Top 10 2023 API1:2023).
//
// GET  api_portal_tickets.php?page=1&per_page=20&estado=Abierto&prioridad=Alta
//   → 200 + {status, tickets:[...], total, page, pages, per_page}
// GET  api_portal_tickets.php?id=N
//   → 200 + {status, ticket:{...}, respuestas:[...]} | 404
// POST api_portal_tickets.php (JSON)
//   Fields: asunto (req), descripcion, prioridad, categoria_id, servicio_id
//   → 200 + {status, message, ticket_id} | 422
//
// Sin bypass de must_change_password: si el cliente tiene contraseña
// temporal, recibe 403 hasta que la cambie (OWASP ASVS V2.4).
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\PortalTicketController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new PortalTicketController($pdo);

    if ($method === 'GET') {
        $clienteSession = Auth::requireClientAuth($pdo);
        $controller->index($clienteSession);
    } elseif ($method === 'POST') {
        $clienteSession = Auth::requireClientAuth($pdo);
        $controller->store($clienteSession);
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}