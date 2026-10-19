<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\ClienteService;
use App\Services\ServicioService;
use App\Repositories\DireccionServicioRepository;
use App\Validators\ClienteValidator;
use App\Exceptions\ConflictException;
use App\Exceptions\ValidationException;

/**
 * Controlador para la gestión de clientes.
 */
final class ClienteController extends BaseController
{
    /** @var ClienteService */
    private ClienteService $clienteService;

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
        $this->clienteService = new ClienteService($pdo);
        $this->servicioService = new ServicioService($pdo);
        $this->direccionServicioRepo = new DireccionServicioRepository($pdo);
    }

    /**
     * Lista los clientes de forma paginada.
     *
     * @return void
     */
    public function index(): void
    {
        $filter = trim($_GET['filter'] ?? '');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 10)));

        $r = $this->clienteService->listarPaginado($filter, $page, $perPage);

        $this->render([
            'status' => 'success',
            'clientes' => $r['items'],
            'page' => $r['page'],
            'pages' => $r['pages'],
            'total' => $r['total'],
            'per_page' => $r['per_page']
        ], 200);
    }

    /**
     * Crea un nuevo cliente.
     *
     * @return void
     */
    public function store(): void
    {
        $input = $this->input();

        if (isset($input['nombres']) && !isset($input['nombre'])) {
            $input['nombre'] = $input['nombres'];
        }
        if (isset($input['apellidos']) && !isset($input['apellido'])) {
            $input['apellido'] = $input['apellidos'];
        }

        $datos = [
            'nombre' => trim($input['nombre'] ?? ''),
            'apellido' => trim($input['apellido'] ?? ''),
            'cedula' => trim($input['cedula'] ?? ''),
            'correo' => trim($input['correo'] ?? ''),
            'telefono' => trim($input['telefono'] ?? '')
        ];

        try {
            ClienteValidator::validateCreate($datos);
            $clienteId = $this->clienteService->crear($datos);

            $direccionTexto = trim($input['direccion'] ?? '');
            $idNap = isset($input['id_nap']) && $input['id_nap'] !== '' ? (int) $input['id_nap'] : null;
            $lat = isset($input['latitud']) && $input['latitud'] !== '' ? (float) str_replace(',', '.', (string) $input['latitud']) : null;
            $lon = isset($input['longitud']) && $input['longitud'] !== '' ? (float) str_replace(',', '.', (string) $input['longitud']) : null;

            if ($direccionTexto !== '' || $idNap !== null || $lat !== null || $lon !== null) {
                $dirSrvId = $this->direccionServicioRepo->create([
                    'calle' => $direccionTexto ?: null,
                    'latitud' => $lat,
                    'longitud' => $lon
                ]);
                $this->servicioService->crear([
                    'cliente_id' => $clienteId,
                    'plan_id' => 1,
                    'nap_id' => $idNap,
                    'direccion_id' => $dirSrvId,
                    'alias' => 'Principal'
                ]);
            }

            $this->render(['status' => 'success', 'message' => 'Cliente registrado correctamente.'], 200);
        } catch (ConflictException $e) {
            $this->render(['status' => 'error', 'message' => $e->getMessage()], 409);
        } catch (ValidationException $e) {
            $this->render(['status' => 'error', 'message' => 'Datos inválidos.', 'errors' => $e->getErrors()], 422);
        }
    }

    /**
     * Actualiza un cliente existente.
     *
     * @return void
     */
    public function update(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_cliente'] ?? 0);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'Cliente inválido'], 400);
            return;
        }

        if (isset($input['nombres']) && !isset($input['nombre'])) {
            $input['nombre'] = $input['nombres'];
        }
        if (isset($input['apellidos']) && !isset($input['apellido'])) {
            $input['apellido'] = $input['apellidos'];
        }

        $datos = [
            'nombre' => trim($input['nombre'] ?? ''),
            'apellido' => trim($input['apellido'] ?? ''),
            'cedula' => trim($input['cedula'] ?? ''),
            'correo' => trim($input['correo'] ?? ''),
            'telefono' => trim($input['telefono'] ?? '')
        ];

        try {
            ClienteValidator::validateUpdate($datos);
            $this->clienteService->actualizar($id, $datos);
            $this->render(['status' => 'success', 'message' => 'Cliente actualizado correctamente'], 200);
        } catch (ConflictException $e) {
            $this->render(['status' => 'error', 'message' => $e->getMessage()], 409);
        } catch (ValidationException $e) {
            $this->render(['status' => 'error', 'message' => 'Datos inválidos.', 'errors' => $e->getErrors()], 422);
        }
    }

    /**
     * Desactiva un cliente.
     *
     * @return void
     */
    public function destroy(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_cliente'] ?? 0);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'Cliente inválido'], 400);
            return;
        }

        $this->clienteService->desactivar($id);
        $this->render(['status' => 'success', 'message' => 'Cliente desactivado correctamente.'], 200);
    }
}