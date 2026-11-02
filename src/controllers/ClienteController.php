<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\ClienteService;
use App\Services\ServicioService;
use App\Repositories\DireccionServicioRepository;
use App\Repositories\PlanRepository;
use App\Validators\ClienteValidator;
use App\Exceptions\ConflictException;
use App\Exceptions\ValidationException;

/**
 * Controlador para la gestión de clientes.
 *
 * A2-B.3: store() devuelve la credencial del portal (username +
 * password_temporal) en el response, una sola vez.
 */
final class ClienteController extends BaseController
{
    /** @var ClienteService */
    private ClienteService $clienteService;

    /** @var ServicioService */
    private ServicioService $servicioService;

    /** @var DireccionServicioRepository */
    private DireccionServicioRepository $direccionServicioRepo;

    /** @var PlanRepository */
    private PlanRepository $planRepo;

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
        $this->planRepo = new PlanRepository($pdo);
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
     * A2-B.3: el service devuelve la credencial del portal
     * (username + password_temporal). Se incluye UNA VEZ en el response.
     * El admin la comunica al cliente. No se vuelve a mostrar.
     *
     * @return void
     */
    public function store(): void
    {
        $input = $this->input();

        // Direccion del cliente (nueva, opcional).
        $direccionCliente = null;
        if (isset($input['direccion']) && is_array($input['direccion'])) {
            $direccionCliente = $input['direccion'];
        }

        // Path legacy de instalacion (direccion del servicio).
        $planId = isset($input['plan_id']) && $input['plan_id'] !== '' ? (int) $input['plan_id'] : null;
        $direccionServicioTexto = trim((string) ($input['direccion_servicio'] ?? ''));
        $idNap = isset($input['id_nap']) && $input['id_nap'] !== '' ? (int) $input['id_nap'] : null;
        $lat = isset($input['latitud']) && $input['latitud'] !== '' ? (float) str_replace(',', '.', (string) $input['latitud']) : null;
        $lon = isset($input['longitud']) && $input['longitud'] !== '' ? (float) str_replace(',', '.', (string) $input['longitud']) : null;

        $hayDatosInstalacion = ($direccionServicioTexto !== '' || $idNap !== null || $lat !== null || $lon !== null);

        if ($hayDatosInstalacion) {
            if ($planId === null) {
                $this->render(['status' => 'error', 'message' => 'Debes seleccionar un plan para el primer servicio.'], 422);
                return;
            }
            if (!$this->planRepo->exists($planId)) {
                $this->render(['status' => 'error', 'message' => 'El plan seleccionado no existe.'], 422);
                return;
            }
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

        if ($direccionCliente !== null) {
            $datos['direccion'] = $direccionCliente;
        }

        try {
            ClienteValidator::validateCreate($datos);

            $this->pdo->beginTransaction();
            try {
                $resultado = $this->clienteService->crear($datos);
                $clienteId = $resultado['cliente_id'];

                if ($hayDatosInstalacion) {
                    $dirSrvId = $this->direccionServicioRepo->create([
                        'calle' => $direccionServicioTexto ?: null,
                        'latitud' => $lat,
                        'longitud' => $lon
                    ]);
                    $this->servicioService->crear([
                        'cliente_id' => $clienteId,
                        'plan_id' => $planId,
                        'nap_id' => $idNap,
                        'direccion_id' => $dirSrvId,
                        'alias' => 'Principal'
                    ]);
                }
                $this->pdo->commit();

                $this->render([
                    'status' => 'success',
                    'message' => 'Cliente registrado correctamente. Credencial del portal generada.',
                    'cliente_id' => $clienteId,
                    'credencial' => [
                        'username' => $resultado['username'],
                        'password_temporal' => $resultado['password_temporal']
                    ]
                ], 200);
            } catch (\Throwable $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                throw $e;
            }
        } catch (ConflictException $e) {
            $this->render(['status' => 'error', 'message' => $e->getMessage()], 409);
        } catch (ValidationException $e) {
            $this->render(['status' => 'error', 'message' => 'Datos inválidos.', 'errors' => $e->getErrors()], 422);
        }
    }

    /**
     * Actualiza un cliente existente.
     *
     * Acepta partial update. Los campos no presentes conservan su valor.
     * Si llega "direccion" (array), se crea o actualiza la direccion
     * del cliente segun si ya tenia una asignada.
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

        $mapa = [
            'nombres' => 'nombre', 'apellidos' => 'apellido',
            'nombre' => 'nombre', 'apellido' => 'apellido',
            'cedula' => 'cedula', 'correo' => 'correo', 'telefono' => 'telefono'
        ];
        $datos = [];
        foreach ($mapa as $entrada => $columna) {
            if (array_key_exists($entrada, $input)) {
                $datos[$columna] = trim((string) $input[$entrada]);
            }
        }

        if (isset($input['direccion']) && is_array($input['direccion'])) {
            $datos['direccion'] = $input['direccion'];
        }

        if (empty($datos)) {
            $this->render(['status' => 'error', 'message' => 'No hay campos para actualizar'], 400);
            return;
        }

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