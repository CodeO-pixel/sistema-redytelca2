<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\ServicioService;
use App\Repositories\ServicioRepository;
use App\Repositories\DireccionServicioRepository;
use App\Validators\ServicioValidator;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;

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
        $validated = ServicioValidator::validateCreate($input);

        $direccionTexto = trim($input['direccion_texto'] ?? '');
        if ($direccionTexto === '') {
            throw new ValidationException(['direccion_texto' => 'La dirección del servicio es obligatoria.']);
        }

        $latRaw = isset($input['latitud']) && $input['latitud'] !== '' ? $input['latitud'] : null;
        $lonRaw = isset($input['longitud']) && $input['longitud'] !== '' ? $input['longitud'] : null;

        $lat = null;
        if ($latRaw !== null) {
            $latNorm = str_replace(',', '.', (string) $latRaw);
            if (!is_numeric($latNorm)) {
                throw new ValidationException(['latitud' => 'La latitud debe ser numérica (usa punto como separador decimal, ej. 10.4806).']);
            }
            $lat = (float) $latNorm;
        }

        $lon = null;
        if ($lonRaw !== null) {
            $lonNorm = str_replace(',', '.', (string) $lonRaw);
            if (!is_numeric($lonNorm)) {
                throw new ValidationException(['longitud' => 'La longitud debe ser numérica (usa punto como separador decimal, ej. -66.9036).']);
            }
            $lon = (float) $lonNorm;
        }

        $dirId = $this->direccionServicioRepo->create([
            'calle' => $direccionTexto,
            'latitud' => $lat,
            'longitud' => $lon
        ]);

        $this->servicioService->crear([
            'cliente_id' => (int) $validated['cliente_id'],
            'plan_id' => (int) $validated['plan_id'],
            'nap_id' => isset($validated['nap_id']) && $validated['nap_id'] !== null && $validated['nap_id'] !== ''
                ? (int) $validated['nap_id']
                : null,
            'alias' => trim($input['alias'] ?? '') ?: null,
            'direccion_id' => $dirId,
            'estado_comercial' => 'pendiente'
        ]);

        $this->render(['status' => 'success', 'message' => 'Servicio añadido correctamente'], 200);
    }

    /**
     * Actualiza un servicio existente.
     *
     * Partial update real: solo modifica los campos presentes en el body.
     * Los campos ausentes conservan su valor actual.
     *
     * @return void
     */
    public function update(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_servicio'] ?? 0);
        unset($input['id_servicio']);

        if ($id <= 0) {
            throw new ValidationException(['id_servicio' => 'Servicio inválido.']);
        }

        $servicioActual = $this->servicioService->buscarPorId($id);
        if (!$servicioActual) {
            throw new NotFoundException('Servicio no encontrado.');
        }

        $validated = ServicioValidator::validateUpdate($input);

        $direccionTexto = trim($input['direccion_texto'] ?? '');
        if ($direccionTexto === '') {
            throw new ValidationException(['direccion_texto' => 'La dirección del servicio es obligatoria.']);
        }

        $dirActual = null;
        if (!empty($servicioActual['direccion_id'])) {
            $dirActual = $this->direccionServicioRepo->findById((int) $servicioActual['direccion_id']);
        }

        $latRaw = array_key_exists('latitud', $input) && $input['latitud'] !== '' ? $input['latitud'] : null;
        $lonRaw = array_key_exists('longitud', $input) && $input['longitud'] !== '' ? $input['longitud'] : null;

        $lat = null;
        if ($latRaw !== null) {
            $latNorm = str_replace(',', '.', (string) $latRaw);
            if (!is_numeric($latNorm)) {
                throw new ValidationException(['latitud' => 'La latitud debe ser numérica (usa punto como separador decimal, ej. 10.4806).']);
            }
            $lat = (float) $latNorm;
        } elseif ($dirActual !== null && $dirActual['latitud'] !== null) {
            $lat = (float) $dirActual['latitud'];
        }

        $lon = null;
        if ($lonRaw !== null) {
            $lonNorm = str_replace(',', '.', (string) $lonRaw);
            if (!is_numeric($lonNorm)) {
                throw new ValidationException(['longitud' => 'La longitud debe ser numérica (usa punto como separador decimal, ej. -66.9036).']);
            }
            $lon = (float) $lonNorm;
        } elseif ($dirActual !== null && $dirActual['longitud'] !== null) {
            $lon = (float) $dirActual['longitud'];
        }

        $datos = [
            'estado_comercial' => array_key_exists('estado_comercial', $validated) && $validated['estado_comercial'] !== null && $validated['estado_comercial'] !== ''
                ? $validated['estado_comercial']
                : $servicioActual['estado_comercial'],
            'direccion_texto' => $direccionTexto,
            'latitud' => $lat,
            'longitud' => $lon
        ];

        if (array_key_exists('alias', $input)) {
            $datos['alias'] = trim($input['alias']) ?: null;
        }

        if (array_key_exists('plan_id', $validated) && $validated['plan_id'] !== null && $validated['plan_id'] !== '') {
            $datos['plan_id'] = (int) $validated['plan_id'];
        }

        if (array_key_exists('nap_id', $validated)) {
            $datos['nap_id'] = ($validated['nap_id'] !== null && $validated['nap_id'] !== '')
                ? (int) $validated['nap_id']
                : null;
        }

        $this->servicioService->actualizar($id, $datos);

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