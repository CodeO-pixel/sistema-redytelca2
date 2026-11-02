<?php
// api_portal_comprobante.php
// Thin file del portal de cliente: descarga del comprobante de un pago.
//
// Gate: Auth::requireClientAuth. El cliente ve SOLO sus propios
// comprobantes. El `cliente_id` se toma de la sesión, NUNCA del body
// ni de la query string (BOLA/IDOR).
//
// GET api_portal_comprobante.php?id=N → sirve el archivo con
// Content-Type real y Content-Disposition: attachment.
//
// El archivo se guarda en public/uploads/pagos/{cliente_id}/, que
// tiene .htaccess con Require all denied. Por eso NO se puede acceder
// por URL directa; solo por este thin, que valida ownership.
//
// Sin bypass de must_change_password.
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\PortalPagoController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new PortalPagoController($pdo);

    if ($method === 'GET') {
        $clienteSession = Auth::requireClientAuth($pdo);
        $controller->comprobante($clienteSession);
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}