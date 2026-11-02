<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\PlanService;
use App\Validators\PlanValidator;

/**
 * Controlador para la gestión de planes.
 */
final class PlanController extends BaseController
{
    /** @var PlanService */
    private PlanService $planService;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->planService = new PlanService($pdo);
    }

    /**
     * Lista los planes.
     *
     * @return void
     */
    public function index(): void
    {
        $planes = $this->planService->listar();

        foreach ($planes as &$plan) {
            $plan['total_servicios'] = $this->planService->contarServicios((int) $plan['id']);
        }
        unset($plan);

        $this->render(['status' => 'success', 'planes' => $planes], 200);
    }

    /**
     * Crea un nuevo plan.
     *
     * @return void
     */
    public function store(): void
    {
        $input = $this->input();
        $validated = PlanValidator::validateCreate($input);

        $this->planService->crear([
            'nombre' => $validated['nombre'],
            'velocidad_bajada_mbps' => $validated['velocidad_bajada_mbps'] ?? null,
            'velocidad_subida_mbps' => $validated['velocidad_subida_mbps'] ?? null,
            'tecnologia' => $validated['tecnologia'] ?? 'FTTH',
            'precio_mensual' => $validated['precio_mensual'],
            'moneda' => $validated['moneda'] ?? 'USD',
            'estado' => $validated['estado'] ?? 'activo'
        ]);

        $this->render(['status' => 'success', 'message' => 'Plan creado correctamente'], 200);
    }

    /**
     * Actualiza un plan existente.
     *
     * Solo modifica los campos presentes en el body. Los ausentes conservan
     * su valor actual (partial update real).
     *
     * @return void
     */
    public function update(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_plan'] ?? 0);
        unset($input['id_plan']);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'Plan inválido'], 400);
            return;
        }

        $validated = PlanValidator::validateUpdate($input);

        $datos = [];
        if (array_key_exists('nombre', $validated)) {
            $datos['nombre'] = $validated['nombre'];
        }
        if (array_key_exists('precio_mensual', $validated)) {
            $datos['precio_mensual'] = $validated['precio_mensual'];
        }
        if (array_key_exists('velocidad_bajada_mbps', $validated)) {
            $datos['velocidad_bajada_mbps'] = $validated['velocidad_bajada_mbps'];
        }
        if (array_key_exists('velocidad_subida_mbps', $validated)) {
            $datos['velocidad_subida_mbps'] = $validated['velocidad_subida_mbps'];
        }
        if (array_key_exists('tecnologia', $validated)) {
            $datos['tecnologia'] = $validated['tecnologia'];
        }
        if (array_key_exists('moneda', $validated)) {
            $datos['moneda'] = $validated['moneda'];
        }
        if (array_key_exists('estado', $validated)) {
            $datos['estado'] = $validated['estado'];
        }

        $this->planService->actualizar($id, $datos);
        $this->render(['status' => 'success', 'message' => 'Plan actualizado'], 200);
    }

    /**
     * Elimina un plan.
     *
     * @return void
     */
    public function destroy(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $this->planService->eliminar($id);
        $this->render(['status' => 'success', 'message' => 'Plan eliminado'], 200);
    }
}