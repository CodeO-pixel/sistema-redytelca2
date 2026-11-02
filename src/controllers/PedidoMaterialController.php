<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\PedidoMaterialService;
use App\Validators\PedidoMaterialValidator;

/**
 * Controlador para la gestión de materiales de pedidos.
 */
final class PedidoMaterialController extends BaseController
{
    /** @var PedidoMaterialService */
    private PedidoMaterialService $materialService;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->materialService = new PedidoMaterialService($pdo);
    }

    /**
     * Lista los materiales de un pedido específico.
     *
     * @return void
     */
    public function index(): void
    {
        $idPedido = isset($_GET['id_pedido']) ? (int) $_GET['id_pedido'] : 0;

        if ($idPedido <= 0) {
            $this->render(['status' => 'error', 'message' => 'El parámetro id_pedido es obligatorio.'], 400);
            return;
        }

        $materiales = $this->materialService->listarPorPedido($idPedido);
        $this->render(['status' => 'success', 'materiales' => $materiales], 200);
    }

    /**
     * Crea un nuevo material para un pedido.
     *
     * @return void
     */
    public function store(): void
    {
        $input = $this->input();
        $validated = PedidoMaterialValidator::validateCreate($input);

        $datos = [
            'pedido_id' => (int) $validated['pedido_id'],
            'descripcion' => $validated['descripcion'],
            'cantidad' => isset($validated['cantidad']) ? (float) $validated['cantidad'] : 1.00,
            'unidad' => $validated['unidad'] ?? 'unidad',
            'equipo_id' => isset($validated['equipo_id']) && $validated['equipo_id'] !== '' ? (int) $validated['equipo_id'] : null,
            'notas' => $validated['notas'] ?? null
        ];

        $this->materialService->crear($datos);
        $this->render(['status' => 'success', 'message' => 'Material añadido correctamente'], 200);
    }

    /**
     * Actualiza un material de pedido existente.
     *
     * @return void
     */
    public function update(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_material'] ?? 0);
        unset($input['id_material']);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'Material inválido'], 400);
            return;
        }

        $validated = PedidoMaterialValidator::validateUpdate($input);

        $datos = [];
        if (array_key_exists('pedido_id', $validated)) {
            $datos['pedido_id'] = (int) $validated['pedido_id'];
        }
        if (array_key_exists('descripcion', $validated)) {
            $datos['descripcion'] = $validated['descripcion'];
        }
        if (array_key_exists('cantidad', $validated)) {
            $datos['cantidad'] = (float) $validated['cantidad'];
        }
        if (array_key_exists('unidad', $validated)) {
            $datos['unidad'] = $validated['unidad'];
        }
        if (array_key_exists('equipo_id', $validated)) {
            $datos['equipo_id'] = ($validated['equipo_id'] !== null && $validated['equipo_id'] !== '') ? (int) $validated['equipo_id'] : null;
        }
        if (array_key_exists('notas', $validated)) {
            $datos['notas'] = $validated['notas'];
        }

        $this->materialService->actualizar($id, $datos);
        $this->render(['status' => 'success', 'message' => 'Material actualizado'], 200);
    }

    /**
     * Elimina un material de pedido.
     *
     * @return void
     */
    public function destroy(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $this->materialService->eliminar($id);
        $this->render(['status' => 'success', 'message' => 'Material eliminado'], 200);
    }
}