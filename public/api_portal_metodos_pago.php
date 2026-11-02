<?php
// api_portal_metodos_pago.php
// Thin file del portal de cliente: catálogo de métodos de pago activos.
//
// Gate: Auth::requireClientAuth. El cliente necesita el catálogo para
// construir el formulario de pago (Fase 5). No es información
// administrativa: solo nombre + código + si requiere referencia.
//
// GET api_portal_metodos_pago.php → {status, metodos:[{id, codigo, nombre, requiere_referencia}]}
//
// Reusa MetodoPagoController::index() (A2). No hay lógica nueva.
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\MetodoPagoController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new MetodoPagoController($pdo);

    if ($method === 'GET') {
        Auth::requireClientAuth($pdo);
        $controller->index();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}