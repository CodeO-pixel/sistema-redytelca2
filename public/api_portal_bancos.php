<?php
// api_portal_bancos.php
// Catálogo de bancos activos para registrar pagos en el portal.
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\BancoController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        Response::error('Método no soportado', 405);
        return;
    }

    Auth::requireClientAuth($pdo);
    (new BancoController($pdo))->indexActivos();
} catch (\Throwable $e) {
    Response::fromException($e);
}
