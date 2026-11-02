<?php
// controllers/PortalAuthController.php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\PortalAuthService;
use App\Validators\PortalAuthValidator;
use App\Exceptions\AuthenticationException;
use App\Exceptions\ValidationException;

/**
 * Controlador de autenticación del portal de cliente.
 *
 * Endpoints:
 *   - POST api_portal_login.php            → login()
 *   - POST api_portal_logout.php           → logout()
 *   - GET  api_portal_auth_state.php       → state()
 *   - POST api_portal_cambiar_password.php → cambiarPassword()
 *
 * Patrón espejo de AuthController (staff), pero sin roles ni módulos.
 */
final class PortalAuthController extends BaseController
{
    private PortalAuthService $portalAuthService;

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->portalAuthService = new PortalAuthService($pdo);
    }

    /**
     * Login del cliente.
     *
     * @return void
     */
    public function login(): void
    {
        $input = $this->input();
        $validated = PortalAuthValidator::validateLogin($input);

        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

        try {
            $r = $this->portalAuthService->login($validated['username'], $validated['password'], $ip, $ua);
            $this->render([
                'status' => 'success',
                'cliente' => $r['cliente'],
                'token' => $r['token'],
                'must_change_password' => $r['must_change_password']
            ], 200);
        } catch (AuthenticationException $e) {
            $this->render(['status' => 'error', 'message' => $e->getMessage()], 401);
        }
    }

    /**
     * Logout del cliente. El token viene del header X-Session-Token.
     *
     * @param array $clienteSession
     * @return void
     */
    public function logout(array $clienteSession): void
    {
        $token = $_SERVER['HTTP_X_SESSION_TOKEN'] ?? '';
        $this->portalAuthService->logout(trim($token));
        $this->render(['status' => 'success'], 200);
    }

    /**
     * Estado de autenticación del cliente.
     *
     * NO usa requireClientAuth: el cliente con must_change_password=1 debe
     * poder consultar su estado para que el frontend lo redirija al cambio
     * de contraseña. La lógica de validación vive en el service, que
     * setea el audit context solo si el token es válido.
     *
     * @return void
     */
    public function state(): void
    {
        $token = $_SERVER['HTTP_X_SESSION_TOKEN'] ?? '';
        $r = $this->portalAuthService->validateSession(trim($token));

        if ($r === null) {
            $this->render(['status' => 'success', 'authenticated' => false], 200);
            return;
        }

        $this->render([
            'status' => 'success',
            'authenticated' => true,
            'cliente' => $r['cliente'],
            'token' => $r['token'],
            'must_change_password' => $r['must_change_password']
        ], 200);
    }

    /**
     * Cambio de contraseña del cliente autenticado.
     *
     * @param array $clienteSession
     * @return void
     */
    public function cambiarPassword(array $clienteSession): void
    {
        $input = $this->input();

        try {
            $validated = PortalAuthValidator::validateCambiarPassword($input);
            $this->portalAuthService->cambiarPassword(
                (int) $clienteSession['id_credencial'],
                $validated['password_actual'],
                $validated['password_nueva']
            );
            $this->render([
                'status' => 'success',
                'message' => 'Contraseña actualizada. Inicia sesión de nuevo.'
            ], 200);
        } catch (ValidationException $e) {
            $this->render(['status' => 'error', 'message' => 'Datos inválidos.', 'errors' => $e->getErrors()], 422);
        }
    }
}