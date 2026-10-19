<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\AuthenticationException;
use App\Repositories\UsuarioRepository;
use App\Repositories\SesionRepository;
use App\Repositories\UsuarioRolRepository;
use App\Repositories\RolModuloPermisoRepository;

/**
 * Servicio para la autenticación y gestión de sesiones del staff.
 */
final class AuthService extends BaseService
{
    private const MAX_INTENTOS = 5;
    private const MINUTOS_BLOQUEO = 15;
    private const DIAS_SESION = 30;
    private const DIAS_LIMPIEZA_SESIONES = 60;
    private const ID_ROL_ADMIN = 1;
    private const ID_MODULO_CONFIG = 4;

    /** @var UsuarioRepository */
    private UsuarioRepository $usuarioRepo;

    /** @var SesionRepository */
    private SesionRepository $sesionRepo;

    /** @var UsuarioRolRepository */
    private UsuarioRolRepository $usuarioRolRepo;

    /** @var RolModuloPermisoRepository */
    private RolModuloPermisoRepository $rolModuloPermisoRepo;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->usuarioRepo = new UsuarioRepository($pdo);
        $this->sesionRepo = new SesionRepository($pdo);
        $this->usuarioRolRepo = new UsuarioRolRepository($pdo);
        $this->rolModuloPermisoRepo = new RolModuloPermisoRepository($pdo);
    }

    /**
     * Autentica a un usuario y crea una nueva sesión.
     *
     * @param string $username
     * @param string $password
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return array
     * @throws AuthenticationException
     */
    public function login(string $username, string $password, ?string $ipAddress, ?string $userAgent): array
    {
        $usuario = $this->usuarioRepo->findByUsername($username);
        if (!$usuario) {
            throw new AuthenticationException('Usuario o contraseña incorrectos.');
        }

        $ahora = gmdate('Y-m-d H:i:s');
        if ($usuario['bloqueado_hasta'] !== null && $usuario['bloqueado_hasta'] > $ahora) {
            throw new AuthenticationException('Cuenta bloqueada. Intente en 15 minutos.');
        }

        if ($usuario['estado'] !== 'Activo') {
            throw new AuthenticationException('Usuario inactivo o bloqueado.');
        }

        if (!password_verify($password, $usuario['password'])) {
            $this->registrarIntentoFallido($usuario);
            throw new AuthenticationException('Usuario o contraseña incorrectos.');
        }

        $this->usuarioRepo->update((int) $usuario['id'], [
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null
        ]);

        $roles = $this->cargarRolesUsuario((int) $usuario['id']);
        $idsRoles = array_map(fn($r) => (int) $r['id'], $roles);
        $rolNombre = !empty($roles) ? $roles[0]['nombre_rol'] : 'Sin rol';

        $modulosPermitidos = $this->cargarModulosPermitidos($idsRoles);

        $this->sesionRepo->deleteExpiradasByUsuarioId((int) $usuario['id'], self::DIAS_LIMPIEZA_SESIONES);

        $token = $this->generarToken();
        $expiraEn = gmdate('Y-m-d H:i:s', strtotime('+' . self::DIAS_SESION . ' days'));

        $this->sesionRepo->create([
            'token' => $token,
            'tipo_usuario' => 'staff',
            'usuario_id' => (int) $usuario['id'],
            'credencial_id' => null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expira_en' => $expiraEn
        ]);

        return [
            'user' => $usuario['username'],
            'roles' => $idsRoles,
            'roleName' => $rolNombre,
            'token' => $token,
            'modules' => $modulosPermitidos,
            'must_change_password' => (bool) $usuario['must_change_password']
        ];
    }

    /**
     * Revoca una sesión activa.
     *
     * @param string $token
     * @return void
     */
    public function logout(string $token): void
    {
        if (trim($token) === '') {
            return;
        }

        $this->sesionRepo->revocarByToken($token, 'logout');
    }

    /**
     * Valida un token de sesión y retorna los datos del usuario si es vigente.
     *
     * @param string $token
     * @return array|null
     */
    public function validateSession(string $token): ?array
    {
        if (trim($token) === '') {
            return null;
        }

        $sesion = $this->sesionRepo->findVigenteByToken($token);
        if (!$sesion) {
            return null;
        }

        $expiraEn = gmdate('Y-m-d H:i:s', strtotime('+' . self::DIAS_SESION . ' days'));
        $this->sesionRepo->update((int) $sesion['id'], ['expira_en' => $expiraEn]);

        $usuario = $this->usuarioRepo->findById((int) $sesion['usuario_id']);
        if (!$usuario) {
            return null;
        }

        $roles = $this->cargarRolesUsuario((int) $usuario['id']);
        $idsRoles = array_map(fn($r) => (int) $r['id'], $roles);
        $rolNombre = !empty($roles) ? $roles[0]['nombre_rol'] : 'Sin rol';

        $modulosPermitidos = $this->cargarModulosPermitidos($idsRoles);

        return [
            'user' => $usuario['username'],
            'roles' => $idsRoles,
            'roleName' => $rolNombre,
            'token' => $token,
            'modules' => $modulosPermitidos,
            'must_change_password' => (bool) $usuario['must_change_password']
        ];
    }

    /**
     * Busca un usuario por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->usuarioRepo->findById($id);
    }

    /**
     * Genera un token criptográficamente seguro.
     *
     * @return string
     */
    private function generarToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Registra un intento fallido de inicio de sesión y bloquea la cuenta si es necesario.
     *
     * @param array $usuario
     * @return void
     */
    private function registrarIntentoFallido(array $usuario): void
    {
        $nuevosIntentos = (int) $usuario['intentos_fallidos'] + 1;
        $datosUpdate = ['intentos_fallidos' => $nuevosIntentos];

        if ($nuevosIntentos >= self::MAX_INTENTOS) {
            $datosUpdate['bloqueado_hasta'] = gmdate('Y-m-d H:i:s', strtotime('+' . self::MINUTOS_BLOQUEO . ' minutes'));
        }

        $this->usuarioRepo->update((int) $usuario['id'], $datosUpdate);
    }

    /**
     * Carga los roles asignados a un usuario.
     *
     * @param int $idUsuario
     * @return array
     */
    private function cargarRolesUsuario(int $idUsuario): array
    {
        return $this->usuarioRolRepo->findRolesByUsuarioId($idUsuario);
    }

    /**
     * Carga los módulos y permisos asociados a una lista de roles.
     *
     * @param array $idsRoles
     * @return array
     */
    private function cargarModulosPermitidos(array $idsRoles): array
    {
        if (empty($idsRoles)) {
            return [];
        }

        $resultado = $this->rolModuloPermisoRepo->findModulosPermitidosByRoles($idsRoles);

        if (!in_array(self::ID_ROL_ADMIN, $idsRoles, true)) {
            $resultado = array_values(array_filter($resultado, fn($m) => (int) $m['id_modulo'] !== self::ID_MODULO_CONFIG));
        }

        return $resultado;
    }
}