<?php
// api_perfil.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\PerfilController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $staffSession = Auth::requireStaffAuth($pdo);
    (new PerfilController($pdo))->index($staffSession);
} catch (\Throwable $e) {
    Response::fromException($e);
}