<?php
// usuarios_eliminar.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core/Bootstrap.php';

use App\Controllers\UsuarioController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    Auth::requirePageAccess($pdo, 'usuarios');
    Auth::requireOperation($pdo, 'usuarios', 'eliminar');
    (new UsuarioController($pdo))->destroy();
} catch (\Throwable $e) {
    Response::fromException($e);
}