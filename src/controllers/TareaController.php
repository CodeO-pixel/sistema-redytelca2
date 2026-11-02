<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\TareaService;
use App\Validators\TareaValidator;
use App\Exceptions\NotFoundException;
use App\Exceptions\DomainException;
use App\Exceptions\ConflictException;
use App\Exceptions\ValidationException;

final class TareaController extends BaseController
{
    private TareaService $tareaService;

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->tareaService = new TareaService($pdo);
    }

    public function index(): void
    {
        $tareas = $this->tareaService->listar();
        $this->render(['status' => 'success', 'tareas' => $tareas], 200);
    }

    public function store(array $staffSession): void
    {
        $input = $this->input();
        unset($input['usuario_creador_id']);

        $validated = TareaValidator::validateCreate($input);
        $usuarioId = (int) ($staffSession['id_usuario'] ?? 0);
        $this->tareaService->crear($validated, $usuarioId);
        $this->render(['status' => 'success', 'message' => 'Tarea creada correctamente'], 200);
    }

    public function update(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_tarea'] ?? 0);
        unset($input['id_tarea']);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'Tarea inválida'], 400);
            return;
        }

        $validated = TareaValidator::validateUpdate($input);
        $this->tareaService->actualizar($id, $validated);
        $this->render(['status' => 'success', 'message' => 'Tarea actualizada'], 200);
    }

    public function destroy(): void
    {
        $input = $this->input();
        $id = (int) ($_GET['id'] ?? $input['id_tarea'] ?? 0);
        $this->tareaService->eliminar($id);
        $this->render(['status' => 'success', 'message' => 'Tarea eliminada'], 200);
    }
}