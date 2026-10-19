<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\ContratoService;
use App\Repositories\ContratoRepository;

/**
 * Controlador para la gestión de contratos.
 */
final class ContratoController extends BaseController
{
    /** @var ContratoService */
    private ContratoService $contratoService;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->contratoService = new ContratoService($pdo);
    }

    /**
     * Lista los contratos.
     *
     * @return void
     */
    public function index(): void
    {
        $contratos = (new ContratoRepository($this->pdo))->findAll();
        $this->render(['status' => 'success', 'contratos' => $contratos], 200);
    }

    /**
     * Crea un nuevo contrato.
     *
     * @return void
     */
    public function store(): void
    {
        $input = $this->input();
        $idServicio = (int) ($input['id_servicio'] ?? 0);
        $fechaInicio = trim($input['fecha_inicio'] ?? '');

        if ($idServicio <= 0 || $fechaInicio === '') {
            $this->render(['status' => 'error', 'message' => 'Servicio y fecha de inicio son obligatorios'], 400);
            return;
        }

        $tipoContrato = trim($input['tipo_contrato'] ?? 'indefinido');
        if (!in_array($tipoContrato, ['indefinido', 'plazo_fijo', 'promocional'], true)) {
            $tipoContrato = 'indefinido';
        }

        $fechaFin = trim($input['fecha_fin'] ?? '');
        if ($tipoContrato !== 'indefinido' && $fechaFin === '') {
            $this->render(['status' => 'error', 'message' => 'Un contrato a plazo fijo o promocional requiere fecha de fin'], 400);
            return;
        }

        $this->contratoService->crear([
            'servicio_id' => $idServicio,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin ?: null,
            'tipo_contrato' => $tipoContrato,
            'observaciones' => trim($input['observaciones'] ?? '') ?: null
        ]);

        $this->render(['status' => 'success', 'message' => 'Contrato creado correctamente'], 200);
    }

    /**
     * Actualiza un contrato existente.
     *
     * @return void
     */
    public function update(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_contrato'] ?? 0);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'Contrato inválido'], 400);
            return;
        }

        $idServicio = (int) ($input['id_servicio'] ?? 0);
        $fechaInicio = trim($input['fecha_inicio'] ?? '');

        if ($idServicio <= 0 || $fechaInicio === '') {
            $this->render(['status' => 'error', 'message' => 'Servicio y fecha de inicio son obligatorios'], 400);
            return;
        }

        $tipoContrato = trim($input['tipo_contrato'] ?? 'indefinido');
        $fechaFin = trim($input['fecha_fin'] ?? '');

        if ($tipoContrato !== 'indefinido' && $fechaFin === '') {
            $this->render(['status' => 'error', 'message' => 'Un contrato a plazo fijo o promocional requiere fecha de fin'], 400);
            return;
        }

        $estado = trim($input['estado'] ?? 'vigente');
        if (!in_array($estado, ['vigente', 'vencido', 'rescindido'], true)) {
            $estado = 'vigente';
        }

        $this->contratoService->actualizar($id, [
            'servicio_id' => $idServicio,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin ?: null,
            'tipo_contrato' => $tipoContrato,
            'estado' => $estado,
            'observaciones' => trim($input['observaciones'] ?? '') ?: null
        ]);

        $this->render(['status' => 'success', 'message' => 'Contrato actualizado'], 200);
    }

    /**
     * Elimina un contrato.
     *
     * @return void
     */
    public function destroy(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $this->contratoService->eliminar($id);
        $this->render(['status' => 'success', 'message' => 'Contrato eliminado'], 200);
    }

    /**
     * Materializa los vencimientos de los contratos.
     *
     * @return void
     */
    public function materializarVencimientos(): void
    {
        $count = $this->contratoService->materializarVencimientos();
        $this->render(['status' => 'success', 'actualizados' => $count], 200);
    }
}