<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\PermisoService;

/**
 * Controlador para la matriz de permisos por rol.
 */
final class PermisoController extends BaseController
{
    /** @var PermisoService */
    private PermisoService $permisoService;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->permisoService = new PermisoService($pdo);
    }

    /**
     * Devuelve la matriz de permisos de un rol.
     * Query string: id_rol (opcional, 0 = solo catálogo).
     *
     * @return void
     */
    public function index(): void
    {
        $idRol = isset($_GET['id_rol']) ? (int) $_GET['id_rol'] : 0;
        $matriz = $this->permisoService->listarMatriz($idRol);

        $this->render([
            'status' => 'success',
            'modulos' => $matriz['modulos'],
            'permisos_catalogo' => $matriz['permisos_catalogo']
        ], 200);
    }

    /**
     * Guarda la matriz de permisos de un rol.
     * Body: {id_rol, paginas: [{id_pagina, visible, permisos[]}]}
     *
     * @return void
     */
    public function guardarMatriz(): void
    {
        $input = $this->input();
        $idRol = (int) ($input['id_rol'] ?? 0);
        $paginas = isset($input['paginas']) && is_array($input['paginas']) ? $input['paginas'] : [];

        $this->permisoService->guardarMatriz($idRol, $paginas);
        $this->render(['status' => 'success', 'message' => 'Permisos actualizados correctamente'], 200);
    }
}