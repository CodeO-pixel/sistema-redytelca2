<?php
// api_portal_facturas.php
// Thin file del portal de cliente: lista y detalle de facturas.
//
// Gate: Auth::requireClientAuth (NO requirePageAccess). El cliente
// autenticado ve SOLO sus propias facturas. El `cliente_id` se toma
// de la sesión, NUNCA del body ni de la query string (BOLA/IDOR,
// OWASP API Security Top 10 2023 API1:2023).
//
// GET api_portal_facturas.php?page=1&per_page=20&estado=pendiente&periodo=2026-10
//   → lista paginada + total_pagado + saldo.
// GET api_portal_facturas.php?id=N
//   → detalle + lineas.
//
// Sin bypass de must_change_password: si el cliente tiene contraseña
// temporal, recibe 403 hasta que la cambie (OWASP ASVS V2.4).
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\PortalFacturaController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new PortalFacturaController($pdo);

    if ($method === 'GET') {
        $clienteSession = Auth::requireClientAuth($pdo);
        $controller->index($clienteSession);
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}