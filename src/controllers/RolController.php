<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\RolService;
use App\Validators\RolValidator;

/**
 * Controlador para la gestión de roles.
 */
final class RolController extends BaseController
{
    /** @var RolService */
    private RolService $rolService;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->rolService = new RolService($pdo);
    }

    /**
     * Lista los roles. Devuelve el contrato viejo (`id_rol`) para
     * compatibilidad con el frontend hasta 140.
     *
     * @return void
     */
    public function index(): void
    {
        $roles = $this->rolService->listar();

        $payload = array_map(fn($r) => [
            'id_rol' => (int) $r['id'],
            'nombre_rol' => $r['nombre_rol'],
            'descripcion' => $r['descripcion'],
        ], $roles);

        $this->render(['status' => 'success', 'roles' => $payload], 200);
    }

    /**
     * Crea un nuevo rol.
     *
     * @return void
     */
    public function store(): void
    {
        $input = $this->input();
        $validated = RolValidator::validateCreate($input);
        $id = $this->rolService->crear($validated);
        $this->render([
            'status' => 'success',
            'message' => 'Rol creado correctamente',
            'id_rol' => $id
        ], 200);
    }

    /**
     * Actualiza un rol existente.
     *
     * @return void
     */
    public function update(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_rol'] ?? 0);
        unset($input['id_rol']);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'Rol inválido'], 400);
            return;
        }

        $validated = RolValidator::validateUpdate($input);
        $this->rolService->actualizar($id, $validated);
        $this->render(['status' => 'success', 'message' => 'Rol actualizado correctamente'], 200);
    }

    /**
     * Elimina un rol.
     *
     * @return void
     */
    public function destroy(): void
    {
        $input = $this->input();
        $id = (int) ($_GET['id'] ?? $input['id_rol'] ?? 0);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'No se recibió el identificador del rol'], 400);
            return;
        }

        $this->rolService->eliminar($id);
        $this->render(['status' => 'success', 'message' => 'Rol eliminado correctamente'], 200);
    }
}