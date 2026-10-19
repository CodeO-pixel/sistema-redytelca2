<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\AuthService;
use App\Exceptions\AuthenticationException;

/**
 * Controlador para la autenticación y gestión de sesiones.
 */
final class AuthController extends BaseController
{
    /** @var AuthService */
    private AuthService $authService;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->authService = new AuthService($pdo);
    }

    /**
     * Inicia sesión y retorna el token y permisos.
     *
     * @return void
     */
    public function login(): void
    {
        $input = $this->input();
        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');

        if ($username === '' || $password === '') {
            $this->render(['status' => 'error', 'message' => 'Credenciales incompletas.'], 400);
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

        try {
            $r = $this->authService->login($username, $password, $ip, $ua);
            $this->render([
                'status' => 'success',
                'usuario' => $r['user'],
                'roles' => $r['roles'],
                'rol_nombre' => $r['roleName'],
                'token' => $r['token'],
                'modulos_permitidos' => $r['modules'],
                'must_change_password' => $r['must_change_password']
            ], 200);
        } catch (AuthenticationException $e) {
            $this->render(['status' => 'error', 'message' => $e->getMessage()], 401);
        }
    }

    /**
     * Cierra la sesión actual.
     *
     * @return void
     */
    public function logout(): void
    {
        $token = $_SERVER['HTTP_X_SESSION_TOKEN'] ?? '';
        $this->authService->logout(trim($token));
        $this->render(['status' => 'success'], 200);
    }

    /**
     * Valida el estado de la sesión actual.
     *
     * @return void
     */
    public function state(): void
    {
        $token = $_SERVER['HTTP_X_SESSION_TOKEN'] ?? '';
        if (trim($token) === '') {
            $this->render(['status' => 'success', 'authenticated' => false], 200);
            return;
        }

        try {
            $r = $this->authService->validateSession(trim($token));
            if ($r === null) {
                $this->render(['status' => 'success', 'authenticated' => false], 200);
                return;
            }

            $this->render([
                'status' => 'success',
                'authenticated' => true,
                'user' => $r['user'],
                'roles' => $r['roles'],
                'roleName' => $r['roleName'],
                'token' => $r['token'],
                'modules' => $r['modules'],
                'must_change_password' => $r['must_change_password']
            ], 200);
        } catch (\Throwable $e) {
            $this->render(['status' => 'error', 'message' => 'Error validando la sesión.'], 500);
        }
    }
}