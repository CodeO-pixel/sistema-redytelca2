<?php
// api_portal_categorias_ticket.php
// Thin file del portal de cliente: catálogo de categorías de ticket activas.
//
// Gate: Auth::requireClientAuth($pdo). El cliente no tiene rol ni módulo,
// así que NO se usa requirePageAccess/requireOperation. La autorización
// es implícita: el cliente solo puede listar categorías, no crearlas ni
// modificarlas (esos verbos no están implementados).
//
// Sin bypass de must_change_password: el catálogo de categorías es dato
// del módulo Tickets. Si el cliente tiene contraseña temporal, recibe 403
// hasta que la cambie (OWASP ASVS V2.4).
//
// GET api_portal_categorias_ticket.php
//   → {status:'success', categorias:[{id, nombre, descripcion, color, activa}]}
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\CategoriaTicketController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        Auth::requireClientAuth($pdo);
        $controller = new CategoriaTicketController($pdo);
        $controller->indexPortal();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}