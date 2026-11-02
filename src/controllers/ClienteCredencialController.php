<?php
// controllers/ClienteCredencialController.php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\ClienteCredencialService;
use App\Validators\ClienteCredencialValidator;

/**
 * Controlador de gestión de credenciales del portal de cliente (admin).
 *
 * Endpoints:
 *   - GET  api_cliente_credenciales.php?cliente_id=N → index()
 *   - POST api_cliente_credenciales.php              → store() (reset password)
 *   - PUT  api_cliente_credenciales.php              → update() (toggle estado)
 *
 * Gate: requirePageAccess('clientes') + requireOperation('clientes', 'editar').
 * Se usa el permiso de clientes.editar porque la credencial es un atributo
 * del cliente. No se crea un módulo "credenciales" separado: es
 * sobreingeniería para tesis (ver Splynx, Sonar, SmartOLT — todos exponen
 * el reset desde la ficha del cliente).
 */
final class ClienteCredencialController extends BaseController
{
    private ClienteCredencialService $credencialService;

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->credencialService = new ClienteCredencialService($pdo);
    }

    /**
     * Devuelve el estado de la credencial de un cliente.
     * Query: ?cliente_id=N
     *
     * @return void
     */
    public function index(): void
    {
        $input = $this->input();
        $clienteId = isset($_GET['cliente_id'])
            ? (int) $_GET['cliente_id']
            : (int) ($input['cliente_id'] ?? 0);

        $validated = ClienteCredencialValidator::validateClienteId(['cliente_id' => $clienteId]);

        $estado = $this->credencialService->getEstado((int) $validated['cliente_id']);

        $this->render([
            'status' => 'success',
            'credencial' => $estado
        ], 200);
    }

    /**
     * Resetea la contraseña de un cliente.
     * Body: {cliente_id, password_nueva?}
     *
     * Si password_nueva no viene, se genera CSPRNG. Se devuelve UNA VEZ.
     *
     * @return void
     */
    public function store(): void
    {
        $input = $this->input();
        $validated = ClienteCredencialValidator::validateReset($input);

        $resultado = $this->credencialService->resetPassword(
            (int) $validated['cliente_id'],
            $validated['password_nueva'] ?? null
        );

        $this->render([
            'status' => 'success',
            'message' => 'Contraseña restablecida. Comunícala al cliente: no se volverá a mostrar.',
            'credencial' => [
                'username' => $resultado['username'],
                'password_temporal' => $resultado['password_temporal']
            ]
        ], 200);
    }

    /**
     * Activa o suspende la credencial de un cliente.
     * Body: {cliente_id, estado: 'activa'|'suspendida'}
     *
     * @return void
     */
    public function update(): void
    {
        $input = $this->input();
        $validated = ClienteCredencialValidator::validateToggle($input);

        $this->credencialService->toggleEstado(
            (int) $validated['cliente_id'],
            $validated['estado']
        );

        $mensaje = $validated['estado'] === 'suspendida'
            ? 'Credencial suspendida. Sesiones activas revocadas.'
            : 'Credencial activada.';

        $this->render(['status' => 'success', 'message' => $mensaje], 200);
    }
}