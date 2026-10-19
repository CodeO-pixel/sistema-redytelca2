<?php
// cambiar_password.php
//
// Este thin es el ÚNICO punto de entrada que llama a
// Auth::requireStaffAuth con bypass de must_change_password
// (tercer parámetro true). Razón: un usuario con el flag a 1 debe
// poder cambiar su contraseña. El resto del ecosistema
// (requirePageAccess, requireOperation, todos los api_*.php) usa el
// bypass=false por defecto y sigue rechazando al usuario con flag a 1
// con 403. Ver BUG-6.2 y Tanda B de 139d.
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core/Bootstrap.php';

use App\Controllers\UsuarioController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $staffSession = Auth::requireStaffAuth($pdo, null, true);
    (new UsuarioController($pdo))->cambiarPassword($staffSession);
} catch (\Throwable $e) {
    Response::fromException($e);
}