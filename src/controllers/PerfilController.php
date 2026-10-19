<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\UsuarioService;
use App\Repositories\SesionRepository;
use App\Exceptions\NotFoundException;

/**
 * Controlador para la gestión del perfil del usuario autenticado.
 */
final class PerfilController extends BaseController
{
    /** @var UsuarioService */
    private UsuarioService $usuarioService;

    /** @var SesionRepository */
    private SesionRepository $sesionRepo;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->usuarioService = new UsuarioService($pdo);
        $this->sesionRepo = new SesionRepository($pdo);
    }

    /**
     * Obtiene los datos del perfil y las sesiones del usuario.
     *
     * @param array $staffSession
     * @return void
     * @throws NotFoundException
     */
    public function index(array $staffSession): void
    {
        $id = (int) $staffSession['id_usuario'];
        $u = $this->usuarioService->buscarPorId($id);

        if (!$u) {
            throw new NotFoundException('Usuario no encontrado.');
        }

        $roles = $this->usuarioService->listarRoles($id);
        $sesiones = $this->sesionRepo->findByUsuarioId($id);

        $this->render([
            'status' => 'success',
            'usuario' => [
                'id_usuario' => $u['id'],
                'username' => $u['username'],
                'email' => $u['email'],
                'nombre_rol' => implode(', ', array_column($roles, 'nombre_rol')) ?: 'Sin rol'
            ],
            'sesiones' => array_map(fn($s) => [
                'id_sesion' => $s['id'],
                'creado_en' => $s['creado_en'],
                'expira_en' => $s['expira_en']
            ], $sesiones)
        ], 200);
    }
}