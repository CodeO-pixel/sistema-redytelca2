<?php
// api_portal_notificaciones.php
// Thin file de la bandeja de notificaciones del cliente (portal).
//
// Gate: requireClientAuth. El cliente_id se toma de la sesión, nunca
// del body (BOLA/IDOR). Fase 5 construye la UI; este endpoint existe
// desde A2-B.1 porque es backend puro.
//
// GET  ?page=1&per_page=20         → lista paginada + conteo de no leídas.
// GET  ?count_only=1               → solo { no_leidas: N } para el badge.
// PUT  body { id_notificacion: N } → marca una como leída.
// PUT  body { mark_all: true }     → marca todas las del cliente.
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\NotificacionPortalController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new NotificacionPortalController($pdo);

    if ($method === 'GET') {
        $clienteSession = Auth::requireClientAuth($pdo);
        $controller->index($clienteSession);
    } elseif ($method === 'PUT') {
        $clienteSession = Auth::requireClientAuth($pdo);
        $controller->update($clienteSession);
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}