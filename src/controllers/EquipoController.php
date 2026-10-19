<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\EquipoService;
use App\Validators\EquipoValidator;
use App\Exceptions\NotFoundException;
use App\Exceptions\DomainException;
use App\Exceptions\ConflictException;
use App\Exceptions\ValidationException;

final class EquipoController extends BaseController
{
    private EquipoService $equipoService;

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->equipoService = new EquipoService($pdo);
    }

    public function index(): void
    {
        $estado = trim($_GET['estado'] ?? '');
        $napId = isset($_GET['nap_id']) && $_GET['nap_id'] !== '' ? (int) $_GET['nap_id'] : null;
        $servicioId = isset($_GET['servicio_id']) && $_GET['servicio_id'] !== '' ? (int) $_GET['servicio_id'] : null;

        if ($napId !== null) {
            $equipos = $this->equipoService->listarPorNap($napId);
        } elseif ($servicioId !== null) {
            $equipos = $this->equipoService->listarPorServicio($servicioId);
        } elseif ($estado !== '') {
            $equipos = $this->equipoService->listarPorEstado($estado);
        } else {
            $equipos = $this->equipoService->listarPorEstado('stock');
        }

        $this->render(['status' => 'success', 'equipos' => $equipos], 200);
    }

    public function store(): void
    {
        $input = $this->input();
        $validated = EquipoValidator::validateCreate($input);
        $this->equipoService->crear($validated);
        $this->render(['status' => 'success', 'message' => 'Equipo creado correctamente'], 200);
    }

    public function update(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_equipo'] ?? 0);
        unset($input['id_equipo']);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'Equipo inválido'], 400);
            return;
        }

        $validated = EquipoValidator::validateUpdate($input);
        $this->equipoService->actualizar($id, $validated);
        $this->render(['status' => 'success', 'message' => 'Equipo actualizado'], 200);
    }

    public function destroy(): void
    {
        $input = $this->input();
        $id = (int) ($_GET['id'] ?? $input['id_equipo'] ?? 0);
        $this->equipoService->eliminar($id);
        $this->render(['status' => 'success', 'message' => 'Equipo eliminado'], 200);
    }
}