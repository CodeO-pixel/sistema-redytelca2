<?php
// api_pago_comprobante.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\PagoController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        Response::error('Método no soportado', 405);
    }

    Auth::requirePageAccess($pdo, 'pagos');
    Auth::requireOperation($pdo, 'pagos', 'leer');
    (new PagoController($pdo))->comprobante();
} catch (\Throwable $e) {
    Response::fromException($e);
}
