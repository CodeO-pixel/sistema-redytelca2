<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\PlanService;

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
        $nombre = trim($input['nombre'] ?? '');
        $precio = isset($input['precio_mensual']) && $input['precio_mensual'] !== '' ? (float) $input['precio_mensual'] : null;

        if ($nombre === '' || $precio === null || $precio <= 0) {
            $this->render(['status' => 'error', 'message' => 'Nombre, velocidad y precio mensual son obligatorios'], 400);
            return;
        }

        $velBajada = isset($input['velocidad_bajada_mbps']) && $input['velocidad_bajada_mbps'] !== '' ? (float) $input['velocidad_bajada_mbps'] : null;
        $velSubida = isset($input['velocidad_subida_mbps']) && $input['velocidad_subida_mbps'] !== '' ? (float) $input['velocidad_subida_mbps'] : null;

        $tecnologia = trim($input['tecnologia'] ?? 'FTTH');
        if (!in_array($tecnologia, ['FTTH', 'HFC', 'WISP', 'OTRO'], true)) {
            $tecnologia = 'FTTH';
        }

        $moneda = trim($input['moneda'] ?? 'USD') ?: 'USD';
        $estado = trim($input['estado'] ?? 'activo');
        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            $estado = 'activo';
        }

        $this->planService->crear([
            'nombre' => $nombre,
            'velocidad_bajada_mbps' => $velBajada,
            'velocidad_subida_mbps' => $velSubida,
            'tecnologia' => $tecnologia,
            'precio_mensual' => $precio,
            'moneda' => $moneda,
            'estado' => $estado
        ]);

        $this->render(['status' => 'success', 'message' => 'Plan creado correctamente'], 200);
    }

    /**
     * Actualiza un plan existente.
     *
     * @return void
     */
    public function update(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_plan'] ?? 0);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'Plan inválido'], 400);
            return;
        }

        $nombre = trim($input['nombre'] ?? '');
        $precio = isset($input['precio_mensual']) && $input['precio_mensual'] !== '' ? (float) $input['precio_mensual'] : null;

        if ($nombre === '' || $precio === null || $precio <= 0) {
            $this->render(['status' => 'error', 'message' => 'Nombre, velocidad y precio mensual son obligatorios'], 400);
            return;
        }

        $velBajada = isset($input['velocidad_bajada_mbps']) && $input['velocidad_bajada_mbps'] !== '' ? (float) $input['velocidad_bajada_mbps'] : null;
        $velSubida = isset($input['velocidad_subida_mbps']) && $input['velocidad_subida_mbps'] !== '' ? (float) $input['velocidad_subida_mbps'] : null;

        $tecnologia = trim($input['tecnologia'] ?? 'FTTH');
        if (!in_array($tecnologia, ['FTTH', 'HFC', 'WISP', 'OTRO'], true)) {
            $tecnologia = 'FTTH';
        }

        $moneda = trim($input['moneda'] ?? 'USD') ?: 'USD';
        $estado = trim($input['estado'] ?? 'activo');
        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            $estado = 'activo';
        }

        $this->planService->actualizar($id, [
            'nombre' => $nombre,
            'velocidad_bajada_mbps' => $velBajada,
            'velocidad_subida_mbps' => $velSubida,
            'tecnologia' => $tecnologia,
            'precio_mensual' => $precio,
            'moneda' => $moneda,
            'estado' => $estado
        ]);

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