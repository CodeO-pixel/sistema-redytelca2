<?php
// logout.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core/Bootstrap.php';

use App\Controllers\AuthController;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    (new AuthController($pdo))->logout();
} catch (\Throwable $e) {
    Response::fromException($e);
}