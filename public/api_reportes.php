<?php
// api_reportes.php
// Endpoint thin del reporte operativo con filtro de periodo opcional (B3.15).
//
// GET ?desde=YYYY-MM-DD&hasta=YYYY-MM-DD
//   - Ambos parámetros son OPCIONALES. Si faltan (o vienen vacíos), el
//     service devuelve TODOS los registros (sin filtro).
//   - Si solo viene uno, el service completa el otro con el mes de la
//     fecha provista (primer/último día del mes).
//   - Rango inválido (formato, fecha real, o desde > hasta) → 422
//     con `errors[]` por campo, vía Response::fromException.
//
// Gate: requirePageAccess('reportes') + requireOperation('reportes', 'leer').
// Solo lectura. Cualquier verbo que no sea GET → 405.
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Controllers\ReporteController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new ReporteController($pdo);

    if ($method === 'GET') {
        Auth::requirePageAccess($pdo, 'reportes');
        Auth::requireOperation($pdo, 'reportes', 'leer');
        $controller->index();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}