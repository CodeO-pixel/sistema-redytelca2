<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\UsuarioService;
use App\Exceptions\ConflictException;
use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;

/**
 * Controlador para la gestión de usuarios.
 *
 * Fase 4.2-hotfix1: el PUT de edición (usuarios_editar.php) YA NO toca
 * roles. Los roles se cambian solo vía PUT usuarios_roles.php
 * (UsuarioController::saveRoles). Es el patrón de Salesforce / Azure AD:
 * "Edit User" (identidad) y "Edit Roles" (permisos) son acciones separadas.
 */
final class UsuarioController extends BaseController
{
    /** @var UsuarioService */
    private UsuarioService $usuarioService;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->usuarioService = new UsuarioService($pdo);
    }

    /**
     * Lista todos los usuarios con sus roles.
     *
     * @return void
     */
    public function index(): void
    {
        $usuarios = $this->usuarioService->listarConRoles();
        $this->render(['status' => 'success', 'usuarios' => $usuarios], 200);
    }

    /**
     * Crea un nuevo usuario.
     *
     * @return void
     */
    public function store(): void
    {
        $input = $this->input();
        $rolesRaw = isset($input['roles']) && is_array($input['roles'])
            ? $input['roles']
            : (isset($input['id_rol']) && $input['id_rol'] !== '' ? [$input['id_rol']] : []);
        $roles = array_values(array_unique(array_filter(array_map('intval', $rolesRaw), fn($r) => $r > 0)));

        $username = trim($input['username'] ?? '');
        $email = trim($input['email'] ?? '');

        if ($username === '' || $email === '' || empty($roles)) {
            $this->render(['status' => 'error', 'message' => 'Usuario, correo y al menos un rol son obligatorios.'], 400);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->render(['status' => 'error', 'message' => 'Ingresa un correo electrónico válido.'], 400);
            return;
        }

        $defaultPassword = 'Redytelca123!';

        try {
            $id = $this->usuarioService->crear([
                'username' => $username,
                'email' => $email,
                'password' => $defaultPassword,
                'roles' => $roles
            ]);

            $this->render([
                'status' => 'success',
                'message' => 'Usuario creado correctamente. Contraseña temporal: ' . $defaultPassword . '. El usuario deberá cambiarla en su primer ingreso.',
                'id_usuario' => $id
            ], 200);
        } catch (ConflictException $e) {
            $this->render(['status' => 'error', 'message' => $e->getMessage()], 409);
        }
    }

    /**
     * Actualiza un usuario existente (SOLO identidad: username + email).
     *
     * Fase 4.2-hotfix1: YA NO procesa roles, aunque el body los traiga.
     * Los roles se cambian exclusivamente vía saveRoles (PUT usuarios_roles.php).
     * Si un cliente viejo manda roles en el PUT, se ignoran silenciosamente.
     *
     * @return void
     */
    public function update(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_usuario'] ?? 0);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'Usuario inválido'], 400);
            return;
        }

        try {
            $this->usuarioService->actualizar($id, [
                'username' => trim($input['username'] ?? ''),
                'email' => trim($input['email'] ?? '')
                // roles NO se pasa: viven en saveRoles
            ]);

            $this->render(['status' => 'success', 'message' => 'Usuario actualizado correctamente.'], 200);
        } catch (DomainException $e) {
            $this->render(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (ConflictException $e) {
            $this->render(['status' => 'error', 'message' => $e->getMessage()], 409);
        }
    }

    /**
     * Actualiza SOLO los roles de un usuario.
     *
     * Ruta dedicada (PUT usuarios_roles.php). Reemplaza la asignación
     * completa. Protege al último administrador activo: si el usuario
     * tenía el rol 1 (Administrador) y se le intenta quitar siendo el
     * último admin activo, lanza DomainException → 422.
     *
     * @return void
     */
    public function saveRoles(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_usuario'] ?? 0);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'Usuario inválido'], 400);
            return;
        }

        $rolesRaw = isset($input['roles']) && is_array($input['roles'])
            ? $input['roles']
            : (isset($input['id_rol']) && $input['id_rol'] !== '' ? [$input['id_rol']] : []);
        $roles = array_values(array_unique(array_filter(array_map('intval', $rolesRaw), fn($r) => $r > 0)));

        if (empty($roles)) {
            $this->render(['status' => 'error', 'message' => 'Debes asignar al menos un rol.'], 400);
            return;
        }

        // Protección del último admin activo.
        $rolesActuales = $this->usuarioService->listarRoles($id);
        $idsActuales = array_column($rolesActuales, 'id');
        $eraAdmin = in_array(1, $idsActuales, true);
        $seraAdmin = in_array(1, $roles, true);

        if ($eraAdmin && !$seraAdmin && $this->usuarioService->contarOtrosAdminsActivos($id) === 0) {
            throw new DomainException('No puedes quitarle el rol de Administrador: es el único administrador activo del sistema.');
        }

        $this->usuarioService->asignarRoles($id, $roles);
        $this->render(['status' => 'success', 'message' => 'Roles actualizados correctamente.'], 200);
    }

    /**
     * Desactiva un usuario.
     *
     * @return void
     */
    public function destroy(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_usuario'] ?? 0);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'Usuario inválido'], 400);
            return;
        }

        try {
            $this->usuarioService->desactivar($id);
            $this->render(['status' => 'success', 'message' => 'Usuario desactivado correctamente.'], 200);
        } catch (DomainException $e) {
            $this->render(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Cambia o restablece la contraseña de un usuario.
     *
     * @param array $staffSession
     * @return void
     */
    public function cambiarPassword(array $staffSession): void
    {
        $input = $this->input();
        $passwordNueva = trim($input['password_nueva'] ?? '');
        $userId = isset($input['user_id']) ? (int) $input['user_id'] : 0;

        if ($passwordNueva === '') {
            $this->render(['status' => 'error', 'message' => 'La nueva contraseña es obligatoria'], 400);
            return;
        }

        try {
            if ($userId > 0) {
                if (!in_array(1, $staffSession['roles'], true)) {
                    $this->render(['status' => 'error', 'message' => 'Solo un Administrador puede restablecer la contraseña de otro usuario.'], 403);
                    return;
                }
                $this->usuarioService->resetearPassword($userId, $passwordNueva);
                $this->render(['status' => 'success', 'message' => 'Contraseña restablecida. El usuario deberá cambiarla.'], 200);
            } else {
                $passwordActual = trim($input['password_actual'] ?? '');
                $username = trim($input['username'] ?? '');

                if ($username !== $staffSession['username']) {
                    $this->render(['status' => 'error', 'message' => 'No puedes cambiar la contraseña de otro usuario por esta vía.'], 403);
                    return;
                }

                if ($passwordActual === '') {
                    $this->render(['status' => 'error', 'message' => 'La contraseña actual es obligatoria'], 400);
                    return;
                }

                $this->usuarioService->cambiarPassword((int) $staffSession['id_usuario'], $passwordActual, $passwordNueva);
                $this->render(['status' => 'success', 'message' => 'Contraseña actualizada correctamente'], 200);
            }
        } catch (ValidationException $e) {
            $this->render(['status' => 'error', 'message' => 'Datos inválidos.', 'errors' => $e->getErrors()], 422);
        }
    }

    /**
     * Verifica el correo electrónico de un usuario.
     *
     * @return void
     */
    public function verificarEmail(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_usuario'] ?? 0);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'ID de usuario inválido'], 400);
            return;
        }

        $this->usuarioService->verificarEmail($id);
        $this->render(['status' => 'success', 'message' => 'Correo verificado correctamente.'], 200);
    }
}