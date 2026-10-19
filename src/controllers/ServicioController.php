<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\ServicioService;
use App\Repositories\ServicioRepository;
use App\Repositories\DireccionServicioRepository;

/**
 * Controlador para la gestión de servicios.
 */
final class ServicioController extends BaseController
{
    /** @var ServicioService */
    private ServicioService $servicioService;

    /** @var DireccionServicioRepository */
    private DireccionServicioRepository $direccionServicioRepo;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->servicioService = new ServicioService($pdo);
        $this->direccionServicioRepo = new DireccionServicioRepository($pdo);
    }

    /**
     * Lista los servicios.
     *
     * @return void
     */
    public function index(): void
    {
        $idCliente = isset($_GET['id_cliente']) ? (int) $_GET['id_cliente'] : 0;

        if ($idCliente > 0) {
            $servicios = $this->servicioService->listarPorCliente($idCliente);
        } else {
            $servicios = (new ServicioRepository($this->pdo))->findAll();
        }

        $this->render(['status' => 'success', 'servicios' => $servicios], 200);
    }

    /**
     * Crea un nuevo servicio.
     *
     * @return void
     */
    public function store(): void
    {
        $input = $this->input();
        $idCliente = (int) ($input['id_cliente'] ?? 0);
        $direccionTexto = trim($input['direccion_texto'] ?? '');

        if ($idCliente <= 0 || $direccionTexto === '') {
            $this->render(['status' => 'error', 'message' => 'Cliente y dirección del servicio son obligatorios'], 400);
            return;
        }

        $idPlan = isset($input['id_plan']) && $input['id_plan'] !== '' ? (int) $input['id_plan'] : 1;
        $idNap = isset($input['id_naps']) && $input['id_naps'] !== '' ? (int) $input['id_naps'] : null;

        $latRaw = isset($input['latitud']) && $input['latitud'] !== '' ? $input['latitud'] : null;
        $lonRaw = isset($input['longitud']) && $input['longitud'] !== '' ? $input['longitud'] : null;

        $lat = $latRaw !== null ? (float) str_replace(',', '.', (string) $latRaw) : null;
        $lon = $lonRaw !== null ? (float) str_replace(',', '.', (string) $lonRaw) : null;

        if (($latRaw !== null && !is_numeric($lat)) || ($lonRaw !== null && !is_numeric($lon))) {
            $this->render(['status' => 'error', 'message' => 'La latitud/longitud debe ser un número válido (usa punto como separador decimal, ej. 10.4806).'], 400);
            return;
        }

        $dirId = $this->direccionServicioRepo->create([
            'calle' => $direccionTexto ?: null,
            'latitud' => $lat,
            'longitud' => $lon
        ]);

        $this->servicioService->crear([
            'cliente_id' => $idCliente,
            'plan_id' => $idPlan,
            'nap_id' => $idNap,
            'alias' => trim($input['alias'] ?? '') ?: null,
            'direccion_id' => $dirId,
            'estado_comercial' => 'pendiente'
        ]);

        $this->render(['status' => 'success', 'message' => 'Servicio añadido correctamente'], 200);
    }

    /**
     * Actualiza un servicio existente.
     *
     * @return void
     */
    public function update(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_servicio'] ?? 0);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'Servicio inválido'], 400);
            return;
        }

        $direccionTexto = trim($input['direccion_texto'] ?? '');
        if ($direccionTexto === '') {
            $this->render(['status' => 'error', 'message' => 'La dirección del servicio es obligatoria'], 400);
            return;
        }

        // Deuda menor: direccion_texto no se actualiza en 139b; se hará cuando ServicioService tenga método actualizarDireccion.
        $this->servicioService->actualizar($id, [
            'alias' => trim($input['alias'] ?? '') ?: null,
            'estado_comercial' => trim($input['estado_comercial'] ?? 'pendiente'),
            'plan_id' => isset($input['id_plan']) && $input['id_plan'] !== '' ? (int) $input['id_plan'] : 1,
            'nap_id' => isset($input['id_naps']) && $input['id_naps'] !== '' ? (int) $input['id_naps'] : null
        ]);

        $this->render(['status' => 'success', 'message' => 'Servicio actualizado'], 200);
    }

    /**
     * Elimina un servicio.
     *
     * @return void
     */
    public function destroy(): void
    {
        $input = $this->input();
        $id = (int) ($_GET['id'] ?? $input['id_servicio'] ?? 0);

        $this->servicioService->eliminar($id);

        $this->render(['status' => 'success', 'message' => 'Servicio eliminado'], 200);
    }
}