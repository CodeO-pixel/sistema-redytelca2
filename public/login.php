<?php
// login.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\AuthController;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    (new AuthController($pdo))->login();
} catch (\Throwable $e) {
    Response::fromException($e);
}