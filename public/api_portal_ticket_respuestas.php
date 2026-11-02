<?php
// api_portal_ticket_respuestas.php
// Thin file del portal de cliente: creación de respuestas en un ticket.
//
// Gate: Auth::requireClientAuth. El cliente autenticado responde SOLO
// tickets que le pertenecen. El `cliente_id` y `credencial_id` se
// toman de la sesión, NUNCA del body (BOLA/IDOR, OWASP API Security
// Top 10 2023 API1:2023).
//
// POST api_portal_ticket_respuestas.php (JSON)
//   Fields: id_ticket (req), mensaje (req)
//   → 200 + {status, message, id_respuesta} | 404 | 422
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

    if ($method === 'POST') {
        $clienteSession = Auth::requireClientAuth($pdo);
        $controller->responder($clienteSession);
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}