<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\OltService;
use App\Validators\OltValidator;
use App\Exceptions\NotFoundException;
use App\Exceptions\DomainException;
use App\Exceptions\ConflictException;
use App\Exceptions\ValidationException;

final class OltController extends BaseController
{
    private OltService $oltService;

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->oltService = new OltService($pdo);
    }

    public function index(): void
    {
        $olts = $this->oltService->listar();
        $this->render(['status' => 'success', 'olts' => $olts], 200);
    }

    public function store(): void
    {
        $input = $this->input();
        $validated = OltValidator::validateCreate($input);
        $this->oltService->crear($validated);
        $this->render(['status' => 'success', 'message' => 'OLT creada correctamente'], 200);
    }

    public function update(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_olt'] ?? 0);
        unset($input['id_olt']);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'OLT inválida'], 400);
            return;
        }

        $validated = OltValidator::validateUpdate($input);
        $this->oltService->actualizar($id, $validated);
        $this->render(['status' => 'success', 'message' => 'OLT actualizada'], 200);
    }

    public function destroy(): void
    {
        $input = $this->input();
        $id = (int) ($_GET['id'] ?? $input['id_olt'] ?? 0);
        $this->oltService->eliminar($id);
        $this->render(['status' => 'success', 'message' => 'OLT eliminada'], 200);
    }
}