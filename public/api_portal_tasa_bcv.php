<?php
// api_portal_tasa_bcv.php
// Devuelve la tasa oficial BCV disponible para el portal autenticado.
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\PortalTasaBcvController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method !== 'GET') {
        Response::error('Método no soportado', 405);
    }

    Auth::requireClientAuth($pdo);
    (new PortalTasaBcvController($pdo))->index();
} catch (\Throwable $e) {
    Response::fromException($e);
}
