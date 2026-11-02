<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\ReporteService;

/**
 * Controlador para el reporte operativo con filtro de periodo opcional.
 *
 * Endpoint thin: public/api_reportes.php.
 * Gate: requirePageAccess('reportes') + requireOperation('reportes', 'leer').
 *
 * Sin catch explícito de excepciones de dominio. Solo el thin hace
 * try/catch → Response::fromException. Las ValidationException del service
 * (rango inválido) se convierten en 422 con `errors[]` por campo.
 *
 * Sin filtro por defecto. Si el operador no manda desde/hasta, el service
 * devuelve TODOS los registros (facturas no anuladas, todos los pagos,
 * todos los tickets, todas las tareas). El filtro es opt-in.
 */
final class ReporteController extends BaseController
{
    /** @var ReporteService */
    private ReporteService $reporteService;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->reporteService = new ReporteService($pdo);
    }

    /**
     * Devuelve el reporte completo.
     *
     * Query string opcional:
     *   - ?desde=YYYY-MM-DD  (sin default: sin filtro si falta)
     *   - ?hasta=YYYY-MM-DD  (sin default: sin filtro si falta)
     *
     * Si solo viene uno de los dos, el service completa el otro con el
     * mes de la fecha provista (primer/último día del mes). Es el atajo
     * "puse una fecha, dame el resto del mes" de Splynx.
     *
     * Si el rango es inválido (formato, fecha real, o desde > hasta),
     * ReporteService::generar() lanza ValidationException y
     * Response::fromException responde 422 con `errors[]` por campo.
     *
     * @return void
     */
    public function index(): void
    {
        $desde = isset($_GET['desde']) ? trim((string) $_GET['desde']) : null;
        $hasta = isset($_GET['hasta']) ? trim((string) $_GET['hasta']) : null;

        // Normalizar '' a null. Si el frontend manda ?desde=&hasta=
        // (inputs vacíos), el service debe interpretarlo como "sin
        // filtro", no como "rango inválido".
        if ($desde === '') {
            $desde = null;
        }
        if ($hasta === '') {
            $hasta = null;
        }

        $reporte = $this->reporteService->generar($desde, $hasta);

        $this->render([
            'status' => 'success',
            'rango' => $reporte['rango'],
            'kpis' => $reporte['kpis'],
            'facturas' => $reporte['facturas'],
            'pagos' => $reporte['pagos'],
            'tickets' => $reporte['tickets'],
            'tareas' => $reporte['tareas'],
        ], 200);
    }
}