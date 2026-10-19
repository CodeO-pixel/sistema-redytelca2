<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\PedidoService;
use App\Validators\PedidoValidator;
use App\Exceptions\NotFoundException;
use App\Exceptions\DomainException;
use App\Exceptions\ConflictException;
use App\Exceptions\ValidationException;

final class PedidoController extends BaseController
{
    private PedidoService $pedidoService;

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->pedidoService = new PedidoService($pdo);
    }

    public function index(): void
    {
        $pedidos = $this->pedidoService->listar();
        $this->render(['status' => 'success', 'pedidos' => $pedidos], 200);
    }

    public function store(): void
    {
        $input = $this->input();
        $validated = PedidoValidator::validateCreate($input);
        $this->pedidoService->crear($validated);
        $this->render(['status' => 'success', 'message' => 'Pedido creado correctamente'], 200);
    }

    public function update(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_pedido'] ?? 0);
        unset($input['id_pedido']);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'Pedido inválido'], 400);
            return;
        }

        $validated = PedidoValidator::validateUpdate($input);
        $this->pedidoService->actualizar($id, $validated);
        $this->render(['status' => 'success', 'message' => 'Pedido actualizado'], 200);
    }

    public function destroy(): void
    {
        $input = $this->input();
        $id = (int) ($_GET['id'] ?? $input['id_pedido'] ?? 0);
        $this->pedidoService->eliminar($id);
        $this->render(['status' => 'success', 'message' => 'Pedido eliminado'], 200);
    }
}