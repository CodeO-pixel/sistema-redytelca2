<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\SlaDefinicionService;
use App\Validators\SlaDefinicionValidator;

/**
 * Controlador para la gestión de definiciones SLA.
 */
final class SlaDefinicionController extends BaseController
{
    /** @var SlaDefinicionService */
    private SlaDefinicionService $slaService;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->slaService = new SlaDefinicionService($pdo);
    }

    /**
     * Lista las definiciones SLA.
     *
     * @return void
     */
    public function index(): void
    {
        $slas = $this->slaService->listar();
        $this->render(['status' => 'success', 'slas' => $slas], 200);
    }

    /**
     * Crea una nueva definición SLA.
     *
     * @return void
     */
    public function store(): void
    {
        $input = $this->input();
        $validated = SlaDefinicionValidator::validateCreate($input);
        $this->slaService->crear($validated);
        $this->render(['status' => 'success', 'message' => 'Definición SLA creada correctamente'], 200);
    }

    /**
     * Actualiza una definición SLA existente.
     *
     * @return void
     */
    public function update(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_sla'] ?? 0);
        unset($input['id_sla']);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'Definición SLA inválida'], 400);
            return;
        }

        $validated = SlaDefinicionValidator::validateUpdate($input);
        $this->slaService->actualizar($id, $validated);
        $this->render(['status' => 'success', 'message' => 'Definición SLA actualizada'], 200);
    }

    /**
     * Elimina una definición SLA.
     *
     * @return void
     */
    public function destroy(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $this->slaService->eliminar($id);
        $this->render(['status' => 'success', 'message' => 'Definición SLA eliminada'], 200);
    }
}