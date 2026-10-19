<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\PagoService;
use App\Repositories\PagoRepository;
use App\Validators\PagoValidator;
use App\Exceptions\NotFoundException;

/**
 * Controlador para la gestión de pagos.
 */
final class PagoController extends BaseController
{
    /** @var PagoService */
    private PagoService $pagoService;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->pagoService = new PagoService($pdo);
    }

    /**
     * Lista los pagos.
     *
     * @return void
     */
    public function index(): void
    {
        $pagos = (new PagoRepository($this->pdo))->findAll();
        $this->render(['status' => 'success', 'pagos' => $pagos], 200);
    }

    /**
     * Crea un nuevo pago.
     *
     * @return void
     */
    public function store(): void
    {
        $input = $this->input();

        if (isset($input['factura_id']) && $input['factura_id'] !== '') {
            $facturaId = (int) $input['factura_id'];
            if ($facturaId < 1) {
                unset($input['factura_id']);
            } else {
                $input['factura_id'] = $facturaId;
            }
        } else {
            unset($input['factura_id']);
        }

        $validated = PagoValidator::validateCreate($input);
        $this->pagoService->crear($validated);
        $this->render(['status' => 'success', 'message' => 'Pago registrado correctamente, pendiente de validación'], 200);
    }

    /**
     * Actualiza un pago existente.
     *
     * @param array $staffSession
     * @return void
     */
    public function update(array $staffSession): void
    {
        $input = $this->input();
        $id = (int) ($input['id_pago'] ?? 0);
        unset($input['id_pago']);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'Pago inválido'], 400);
            return;
        }

        $pagoActual = (new PagoRepository($this->pdo))->findById($id);
        if (!$pagoActual) {
            throw new NotFoundException('El pago ya no existe.');
        }

        $nuevoEstado = trim($input['estado'] ?? $pagoActual['estado']);
        if (!in_array($nuevoEstado, ['pendiente', 'validado', 'rechazado'], true)) {
            $nuevoEstado = $pagoActual['estado'];
        }

        $estadosSensibles = ['validado', 'rechazado', 'pendiente'];
        $cambioDeEstadoSensible = $nuevoEstado !== $pagoActual['estado'] && in_array($nuevoEstado, $estadosSensibles, true);

        if ($cambioDeEstadoSensible && !in_array(1, $staffSession['roles'], true)) {
            $this->render(['status' => 'error', 'message' => 'Solo un Administrador puede validar o rechazar un pago.'], 403);
            return;
        }

        $idUsuarioValido = $cambioDeEstadoSensible ? (int) $staffSession['id_usuario'] : null;

        $this->pagoService->actualizar($id, $input, $idUsuarioValido);
        $this->render(['status' => 'success', 'message' => 'Pago actualizado correctamente'], 200);
    }

    /**
     * Elimina un pago.
     *
     * @return void
     */
    public function destroy(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $this->pagoService->eliminar($id);
        $this->render(['status' => 'success', 'message' => 'Pago eliminado'], 200);
    }
}