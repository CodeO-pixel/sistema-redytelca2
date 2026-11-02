<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\TicketRespuestaService;

/**
 * Controlador para la gestión de respuestas de tickets.
 */
final class TicketRespuestaController extends BaseController
{
    /** @var TicketRespuestaService */
    private TicketRespuestaService $respuestaService;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->respuestaService = new TicketRespuestaService($pdo);
    }

    /**
     * Lista las respuestas de un ticket específico.
     *
     * @param array $staffSession
     * @return void
     */
    public function index(array $staffSession = []): void
    {
        $idTicket = isset($_GET['id_ticket']) ? (int) $_GET['id_ticket'] : 0;

        if ($idTicket <= 0) {
            $this->render(['status' => 'error', 'message' => 'El parámetro id_ticket es obligatorio.'], 400);
            return;
        }

        $respuestas = $this->respuestaService->listarPorTicket($idTicket);
        $this->render(['status' => 'success', 'respuestas' => $respuestas], 200);
    }

    /**
     * Crea una nueva respuesta en un ticket.
     *
     * @param array $staffSession
     * @return void
     */
    public function store(array $staffSession): void
    {
        $input = $this->input();
        $usuarioId = (int) ($staffSession['id_usuario'] ?? 0);

        $idRespuesta = $this->respuestaService->crear($input, $usuarioId);

        $this->render([
            'status' => 'success',
            'message' => 'Respuesta añadida correctamente',
            'id_respuesta' => $idRespuesta
        ], 200);
    }
}