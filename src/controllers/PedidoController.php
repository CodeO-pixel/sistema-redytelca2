<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
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

    /**
     * Lista los pedidos.
     *
     * Fix B3.13 — filtro por rol. Si el usuario tiene rol Técnico y
     * NO tiene Admin ni Operador, se devuelven solo sus pedidos
     * asignados. Admin y Operador ven todos.
     *
     * Detección por nombre de rol (no por ID hardcodeado) para que no
     * se rompa si el seed cambia.
     *
     * @param array $staffSession
     * @return void
     */
    public function index(array $staffSession = []): void
    {
        $idUsuario = (int) ($staffSession['id_usuario'] ?? 0);

        if ($idUsuario > 0) {
            $nombresRoles = Auth::loadUserRoleNames($this->pdo, $idUsuario);
            $esAdminUOperador = in_array('Administrador', $nombresRoles, true) || in_array('Operador', $nombresRoles, true);
            $esTecnico = in_array('Técnico', $nombresRoles, true);

            if ($esTecnico && !$esAdminUOperador) {
                $pedidos = $this->pedidoService->listarPorTecnico($idUsuario);
                $this->render(['status' => 'success', 'pedidos' => $pedidos], 200);
                return;
            }
        }

        $pedidos = $this->pedidoService->listar();
        $this->render(['status' => 'success', 'pedidos' => $pedidos], 200);
    }

    public function store(array $staffSession): void
    {
        $input = $this->input();
        unset($input['usuario_creador_id']);

        $validated = PedidoValidator::validateCreate($input);
        $usuarioId = (int) ($staffSession['id_usuario'] ?? 0);
        $this->pedidoService->crear($validated, $usuarioId);
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

    public function asignarTecnico(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_pedido'] ?? 0);
        $tecnicoId = (int) ($input['tecnico_asignado_id'] ?? 0);

        if ($id <= 0 || $tecnicoId <= 0) {
            $this->render(['status' => 'error', 'message' => 'Pedido y técnico son obligatorios'], 400);
            return;
        }

        $this->pedidoService->asignarTecnico($id, $tecnicoId);
        $this->render(['status' => 'success', 'message' => 'Técnico asignado correctamente'], 200);
    }

    /**
     * Completa un pedido y asigna el equipo reservado al servicio.
     *
     * F2.35 — cierre del flujo reserva → asignación. Accesible al
     * técnico (rol Técnico con permiso pedidos.editar) y al operador.
     * No permite cambiar a 'completado' por PUT api_pedidos.php.
     *
     * @return void
     */
    public function completar(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_pedido'] ?? 0);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'Pedido inválido'], 400);
            return;
        }

        $this->pedidoService->completar($id);
        $this->render([
            'status' => 'success',
            'message' => 'Pedido completado. Equipo asignado al servicio.'
        ], 200);
    }
}