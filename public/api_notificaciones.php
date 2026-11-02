<?php
// api_notificaciones.php
// Thin file de la bandeja de notificaciones del staff.
//
// Gate: requireStaffAuth (NO requirePageAccess). La campana vive en el
// header de cualquier página, no en una página del sidebar. El usuario
// autenticado ve SOLO sus propias notificaciones.
//
// GET  ?page=1&per_page=20         → lista paginada + conteo de no leídas.
// GET  ?count_only=1               → solo { no_leidas: N } para el badge.
// PUT  body { id_notificacion: N } → marca una como leída.
// PUT  body { mark_all: true }     → marca todas las del usuario.
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\NotificacionController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new NotificacionController($pdo);

    if ($method === 'GET') {
        $staffSession = Auth::requireStaffAuth($pdo);
        $controller->index($staffSession);
    } elseif ($method === 'PUT') {
        $staffSession = Auth::requireStaffAuth($pdo);
        $controller->update($staffSession);
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}