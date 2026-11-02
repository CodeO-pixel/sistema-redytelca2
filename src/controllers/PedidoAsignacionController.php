<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Repositories\PedidoAsignacionRepository;
use App\Repositories\PedidoRepository;
use App\Services\PedidoService;

/**
 * Controlador para la gestión de asignaciones de pedidos.
 */
final class PedidoAsignacionController extends BaseController
{
    /** @var PedidoAsignacionRepository */
    private PedidoAsignacionRepository $asignacionRepo;

    /** @var PedidoRepository */
    private PedidoRepository $pedidoRepo;

    /** @var PedidoService */
    private PedidoService $pedidoService;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->asignacionRepo = new PedidoAsignacionRepository($pdo);
        $this->pedidoRepo = new PedidoRepository($pdo);
        $this->pedidoService = new PedidoService($pdo);
    }

    /**
     * Lista las asignaciones de un pedido específico.
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

        if (!$this->pedidoRepo->exists($idPedido)) {
            $this->render(['status' => 'error', 'message' => 'El pedido especificado no existe.'], 404);
            return;
        }

        $asignaciones = $this->asignacionRepo->findByPedidoId($idPedido);
        $this->render(['status' => 'success', 'asignaciones' => $asignaciones], 200);
    }

    /**
     * Asigna un técnico a un pedido.
     *
     * @param array $staffSession
     * @return void
     */
    public function store(array $staffSession): void
    {
        $input = $this->input();
        $idPedido = (int) ($input['id_pedido'] ?? 0);
        $tecnicoId = (int) ($input['tecnico_id'] ?? 0);

        if ($idPedido <= 0 || $tecnicoId <= 0) {
            $this->render(['status' => 'error', 'message' => 'id_pedido y tecnico_id son obligatorios.'], 400);
            return;
        }

        $usuarioAsignadorId = (int) ($staffSession['id_usuario'] ?? 0);

        $this->pedidoService->asignarTecnico($idPedido, $tecnicoId, $usuarioAsignadorId);

        $this->render(['status' => 'success', 'message' => 'Técnico asignado correctamente'], 200);
    }
}