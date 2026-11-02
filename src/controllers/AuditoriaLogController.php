<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Repositories\AuditoriaLogRepository;

/**
 * Controlador para la consulta de logs de auditoría.
 */
final class AuditoriaLogController extends BaseController
{
    /** @var AuditoriaLogRepository */
    private AuditoriaLogRepository $auditoriaRepo;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->auditoriaRepo = new AuditoriaLogRepository($pdo);
    }

    /**
     * Lista los logs de auditoría de forma paginada y con filtros opcionales.
     *
     * @return void
     */
    public function index(): void
    {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }

        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 50;
        if ($perPage < 1) {
            $perPage = 1;
        } elseif ($perPage > 100) {
            $perPage = 100;
        }

        $filtros = [];

        $tabla = trim($_GET['tabla'] ?? '');
        if ($tabla !== '') {
            $filtros['tabla'] = $tabla;
        }

        $accion = trim($_GET['accion'] ?? '');
        if ($accion !== '') {
            $accionesValidas = ['INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'LOGIN_FALLIDO'];
            if (in_array(strtoupper($accion), $accionesValidas, true)) {
                $filtros['accion'] = strtoupper($accion);
            }
        }

        $desde = trim($_GET['desde'] ?? '');
        if ($desde !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
            $filtros['desde'] = $desde;
        }

        $hasta = trim($_GET['hasta'] ?? '');
        if ($hasta !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $filtros['hasta'] = $hasta;
        }

        $resultado = $this->auditoriaRepo->paginarConFiltros($filtros, $page, $perPage);
        $total = $resultado['total'];
        $pages = max(1, (int) ceil($total / $perPage));

        $this->render([
            'status' => 'success',
            'logs' => $resultado['items'],
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage
        ], 200);
    }
}