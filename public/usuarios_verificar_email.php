<?php
// usuarios_verificar_email.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\UsuarioController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    Auth::requirePageAccess($pdo, 'usuarios');
    Auth::requireOperation($pdo, 'usuarios', 'editar');
    (new UsuarioController($pdo))->verificarEmail();
} catch (\Throwable $e) {
    Response::fromException($e);
}