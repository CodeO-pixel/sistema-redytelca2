<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\NodoService;
use App\Validators\NodoValidator;
use App\Exceptions\NotFoundException;
use App\Exceptions\DomainException;
use App\Exceptions\ConflictException;
use App\Exceptions\ValidationException;

final class NodoController extends BaseController
{
    private NodoService $nodoService;

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->nodoService = new NodoService($pdo);
    }

    public function index(): void
    {
        $nodos = $this->nodoService->listar();
        $this->render(['status' => 'success', 'nodos' => $nodos], 200);
    }

    public function store(): void
    {
        $input = $this->input();
        $validated = NodoValidator::validateCreate($input);
        $this->nodoService->crear($validated);
        $this->render(['status' => 'success', 'message' => 'Nodo creado correctamente'], 200);
    }

    public function update(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_nodo'] ?? 0);
        unset($input['id_nodo']);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'Nodo inválido'], 400);
            return;
        }

        $validated = NodoValidator::validateUpdate($input);
        $this->nodoService->actualizar($id, $validated);
        $this->render(['status' => 'success', 'message' => 'Nodo actualizado'], 200);
    }

    public function destroy(): void
    {
        $input = $this->input();
        $id = (int) ($_GET['id'] ?? $input['id_nodo'] ?? 0);
        $this->nodoService->eliminar($id);
        $this->render(['status' => 'success', 'message' => 'Nodo eliminado'], 200);
    }
}