<?php
// api_portal_pagos.php
// Thin file del portal de cliente: lista, detalle y creación de pagos.
//
// Gate: Auth::requireClientAuth (NO requirePageAccess). El cliente
// autenticado ve SOLO sus propios pagos. El `cliente_id` se toma de
// la sesión, NUNCA del body ni de la query string (BOLA/IDOR,
// OWASP API Security Top 10 2023 API1:2023).
//
// GET  api_portal_pagos.php?page=1&per_page=20&estado=pendiente
//      → lista paginada.
// GET  api_portal_pagos.php?id=N
//      → detalle.
// POST api_portal_pagos.php (multipart/form-data)
//      → registra un pago con comprobante.
//      Fields: factura_id (opcional), servicio_id, monto, moneda_pago,
//      tasa_cambio (si VES), fecha_pago, metodo_id, referencia_bancaria.
//      File: comprobante (obligatorio, JPG/PNG/WEBP/PDF, max 2 MB).
//
// Sin bypass de must_change_password: si el cliente tiene contraseña
// temporal, recibe 403 hasta que la cambie (OWASP ASVS V2.4).
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