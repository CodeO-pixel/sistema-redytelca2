<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\CategoriaTicketService;
use App\Validators\CategoriaTicketValidator;

/**
 * Controlador para la gestión de categorías de tickets.
 */
final class CategoriaTicketController extends BaseController
{
    /** @var CategoriaTicketService */
    private CategoriaTicketService $categoriaService;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->categoriaService = new CategoriaTicketService($pdo);
    }

    /**
     * Lista todas las categorías (admin).
     *
     * @return void
     */
    public function index(): void
    {
        $categorias = $this->categoriaService->listar();
        $this->render(['status' => 'success', 'categorias' => $categorias], 200);
    }

    /**
     * Lista solo las categorías activas (portal de cliente).
     *
     * Endpoint: GET public/api_portal_categorias_ticket.php
     * Gate: Auth::requireClientAuth($pdo) en el thin file.
     *
     * El cliente no puede crear, editar ni eliminar categorías.
     * Solo las necesita para el select del formulario de "Nuevo ticket".
     *
     * @return void
     */
    public function indexPortal(): void
    {
        $categorias = $this->categoriaService->listarActivas();
        $this->render(['status' => 'success', 'categorias' => $categorias], 200);
    }

    /**
     * Crea una nueva categoría de ticket (admin).
     *
     * @return void
     */
    public function store(): void
    {
        $input = $this->input();
        $validated = CategoriaTicketValidator::validateCreate($input);
        $this->categoriaService->crear($validated);
        $this->render(['status' => 'success', 'message' => 'Categoría creada correctamente'], 200);
    }

    /**
     * Actualiza una categoría de ticket existente (admin).
     *
     * @return void
     */
    public function update(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_categoria'] ?? 0);
        unset($input['id_categoria']);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'Categoría inválida'], 400);
            return;
        }

        $validated = CategoriaTicketValidator::validateUpdate($input);
        $this->categoriaService->actualizar($id, $validated);
        $this->render(['status' => 'success', 'message' => 'Categoría actualizada'], 200);
    }

    /**
     * Elimina una categoría de ticket (admin).
     *
     * @return void
     */
    public function destroy(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $this->categoriaService->eliminar($id);
        $this->render(['status' => 'success', 'message' => 'Categoría eliminada'], 200);
    }
}