<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\NapService;
use App\Validators\NapValidator;
use App\Exceptions\NotFoundException;
use App\Exceptions\DomainException;
use App\Exceptions\ConflictException;
use App\Exceptions\ValidationException;

final class NapController extends BaseController
{
    private NapService $napService;

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->napService = new NapService($pdo);
    }

    public function index(): void
    {
        $naps = $this->napService->listar();
        $this->render(['status' => 'success', 'naps' => $naps], 200);
    }

    public function store(): void
    {
        $input = $this->input();
        $validated = NapValidator::validateCreate($input);
        $this->napService->crear($validated);
        $this->render(['status' => 'success', 'message' => 'NAP creada correctamente'], 200);
    }

    public function update(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_nap'] ?? 0);
        unset($input['id_nap']);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'NAP inválida'], 400);
            return;
        }

        $validated = NapValidator::validateUpdate($input);
        $this->napService->actualizar($id, $validated);
        $this->render(['status' => 'success', 'message' => 'NAP actualizada'], 200);
    }

    public function destroy(): void
    {
        $input = $this->input();
        $id = (int) ($_GET['id'] ?? $input['id_nap'] ?? 0);
        $this->napService->eliminar($id);
        $this->render(['status' => 'success', 'message' => 'NAP eliminada'], 200);
    }
}