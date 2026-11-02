<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\BancoService;
use App\Validators\BancoValidator;

final class BancoController extends BaseController
{
    private BancoService $bancoService;

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->bancoService = new BancoService($pdo);
    }

    public function index(): void
    {
        $this->render(['status' => 'success', 'bancos' => $this->bancoService->listar()], 200);
    }

    public function indexActivos(): void
    {
        $this->render(['status' => 'success', 'bancos' => $this->bancoService->listarActivos()], 200);
    }

    public function store(): void
    {
        $validated = BancoValidator::validateCreate($this->input());
        $id = $this->bancoService->crear(trim((string) $validated['nombre']));
        $this->render(['status' => 'success', 'message' => 'Banco agregado al catálogo.', 'id' => $id], 201);
    }

    public function update(): void
    {
        $validated = BancoValidator::validateUpdate($this->input());
        $this->bancoService->actualizarEstado((int) $validated['id'], (int) $validated['activo']);
        $this->render(['status' => 'success', 'message' => 'Estado del banco actualizado.'], 200);
    }
}
