<?php
// clientes_listar.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core/Bootstrap.php';

use App\Controllers\ClienteController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    Auth::requirePageAccess($pdo, 'clientes');
    Auth::requireOperation($pdo, 'clientes', 'leer');
    (new ClienteController($pdo))->index();
} catch (\Throwable $e) {
    Response::fromException($e);
}