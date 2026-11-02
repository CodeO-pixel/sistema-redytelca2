<?php
// services/PortalAuthService.php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\BaseService;
use App\Exceptions\AuthenticationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\ClienteCredencialRepository;
use App\Repositories\ClienteRepository;
use App\Repositories\SesionRepository;

/**
 * Servicio de autenticación para el portal de cliente.
 *
 * Separado de AuthService (que gestiona staff con roles y módulos RBAC).
 * El portal cliente tiene una sola identidad (clientes_credenciales) sin
 * roles ni módulos. Mezclar ambos violaría SRP.
 *
 * Flujo de login:
 *   1. Buscar credencial por username.
 *   2. Si no existe → dummy password_verify (timing attack mitigation) → 401.
 *   3. Verificar estado='activa', bloqueado_hasta, password_verify.
 *   4. Verificar clientes.estado != 'inactivo'.
 *   5. Reset intentos, update ultimo_acceso, limpieza de sesiones.
 *   6. Crear sesión (tipo_usuario='cliente', credencial_id, usuario_id=null).
 *   7. Devolver datos del cliente + token + must_change_password.
 *
 * Cambio de contraseña:
 *   1. Validar password_actual.
 *   2. Validar fuerza de password_nueva.
 *   3. UPDATE password + must_change_password=0.
 *   4. Revocar TODAS las sesiones de la credencial (OWASP ASVS V7.4.3).
 */
final class PortalAuthService extends BaseService
{
    private const MAX_INTENTOS = 5;
    private const MINUTOS_BLOQUEO = 15;
    private const DIAS_SESION = 30;
    private const DIAS_LIMPIEZA_SESIONES = 60;

    /**
     * Hash bcrypt válido usado para igualar el tiempo de respuesta
     * cuando el username no existe (mitigación de timing oracle).
     * No corresponde a ninguna contraseña conocida. Mismo patrón que
     * AuthService::DUMMY_BCRYPT_HASH.
     */
    private const DUMMY_BCRYPT_HASH = '$2y$10$3F914JmsON9GvY8A2Y6pxuV1/Yap2xLDhc0L9BUFvEK/.9v9pz3Im';

    /** @var ClienteCredencialRepository */
    private ClienteCredencialRepository $credencialRepo;

    /** @var ClienteRepository */
    private ClienteRepository $clienteRepo;

    /** @var SesionRepository */
    private SesionRepository $sesionRepo;

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->credencialRepo = new ClienteCredencialRepository($pdo);
        $this->clienteRepo = new ClienteRepository($pdo);
        $this->sesionRepo = new SesionRepository($pdo);
    }

    /**
     * Autentica un cliente y crea una nueva sesión.
     *
     * @param string $username
     * @param string $password
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return array{cliente: array, token: string, must_change_password: bool}
     * @throws AuthenticationException
     */
    public function login(string $username, string $password, ?string $ipAddress, ?string $userAgent): array
    {
        $credencial = $this->credencialRepo->findByUsername($username);

        if (!$credencial) {
            // Mitigación de timing oracle: ejecutar password_verify contra
            // un hash dummy válido para que el tiempo de respuesta sea
            // indistinguible del path "credencial existe".
            password_verify($password, self::DUMMY_BCRYPT_HASH);
            throw new AuthenticationException('Usuario o contraseña incorrectos.');
        }

        $ahora = gmdate('Y-m-d H:i:s');
        if ($credencial['bloqueado_hasta'] !== null && $credencial['bloqueado_hasta'] > $ahora) {
            throw new AuthenticationException('Cuenta bloqueada. Intente en 15 minutos.');
        }

        if ($credencial['estado'] !== 'activa') {
            throw new AuthenticationException('Cuenta suspendida. Contacta al administrador.');
        }

        if (!password_verify($password, $credencial['password'])) {
            $this->registrarIntentoFallido($credencial);
            throw new AuthenticationException('Usuario o contraseña incorrectos.');
        }

        $cliente = $this->clienteRepo->findById((int) $credencial['cliente_id']);
        if (!$cliente) {
            throw new AuthenticationException('Cliente no encontrado.');
        }
        if (($cliente['estado'] ?? '') === 'inactivo') {
            throw new AuthenticationException('Cuenta inactiva. Contacta al administrador.');
        }

        $this->credencialRepo->resetFailedAttempts((int) $credencial['id']);
        $this->credencialRepo->updateLastAccess((int) $credencial['id']);
        $this->sesionRepo->deleteExpiradasByCredencialId((int) $credencial['id'], self::DIAS_LIMPIEZA_SESIONES);

        $token = bin2hex(random_bytes(32));
        $expiraEn = gmdate('Y-m-d H:i:s', strtotime('+' . self::DIAS_SESION . ' days'));

        $this->sesionRepo->create([
            'token' => $token,
            'tipo_usuario' => 'cliente',
            'usuario_id' => null,
            'credencial_id' => (int) $credencial['id'],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expira_en' => $expiraEn
        ]);

        return [
            'cliente' => [
                'id' => (int) $cliente['id'],
                'username' => $credencial['username'],
                'nombre' => $cliente['nombre'],
                'apellido' => $cliente['apellido'],
                'cedula' => $cliente['cedula'],
                'correo' => $cliente['correo']
            ],
            'token' => $token,
            'must_change_password' => (bool) $credencial['must_change_password']
        ];
    }

    /**
     * Revoca la sesión actual del cliente.
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
     * Valida un token de sesión de cliente y devuelve los datos del cliente.
     *
     * Setea el audit context de la conexión PDO para que los triggers de
     * auditoría registren al cliente como actor. Renueva la expiración
     * 30 días más (renovación deslizante, igual que staff).
     *
     * @param string $token
     * @return array{cliente: array, token: string, must_change_password: bool}|null
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
        if (($sesion['tipo_usuario'] ?? '') !== 'cliente') {
            return null;
        }

        $idCredencial = (int) $sesion['credencial_id'];
        $credencial = $this->credencialRepo->findById($idCredencial);
        if (!$credencial) {
            return null;
        }

        $cliente = $this->clienteRepo->findById((int) $credencial['cliente_id']);
        if (!$cliente) {
            return null;
        }
        if (($cliente['estado'] ?? '') === 'inactivo') {
            return null;
        }

        $expiraEn = gmdate('Y-m-d H:i:s', strtotime('+' . self::DIAS_SESION . ' days'));
        $this->sesionRepo->update((int) $sesion['id'], ['expira_en' => $expiraEn]);

        Auth::setAuditContext($this->pdo, 'cliente', null, $idCredencial);

        return [
            'cliente' => [
                'id' => (int) $cliente['id'],
                'username' => $credencial['username'],
                'nombre' => $cliente['nombre'],
                'apellido' => $cliente['apellido'],
                'cedula' => $cliente['cedula'],
                'correo' => $cliente['correo']
            ],
            'token' => $token,
            'must_change_password' => (bool) $credencial['must_change_password']
        ];
    }

    /**
     * Cambia la contraseña del cliente autenticado.
     *
     * Revoca TODAS las sesiones de la credencial tras el cambio
     * (OWASP ASVS V7.4.3). El cliente debe volver a loguearse.
     *
     * @param int $idCredencial
     * @param string $passwordActual
     * @param string $passwordNueva
     * @return void
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function cambiarPassword(int $idCredencial, string $passwordActual, string $passwordNueva): void
    {
        $credencial = $this->credencialRepo->findById($idCredencial);
        if (!$credencial) {
            throw new NotFoundException('Credencial no encontrada.');
        }

        if (!password_verify($passwordActual, $credencial['password'])) {
            throw new ValidationException(['password_actual' => 'La contraseña actual es incorrecta.']);
        }

        if (strlen($passwordNueva) < 8) {
            throw new ValidationException(['password_nueva' => 'La nueva contraseña debe tener al menos 8 caracteres.']);
        }

        if ($passwordNueva === $passwordActual) {
            throw new ValidationException(['password_nueva' => 'La nueva contraseña debe ser distinta a la actual.']);
        }

        $this->transaction(function () use ($idCredencial, $passwordNueva) {
            $this->credencialRepo->update($idCredencial, [
                'password' => password_hash($passwordNueva, PASSWORD_DEFAULT),
                'must_change_password' => 0
            ]);
            $this->sesionRepo->revocarTodasByCredencialId($idCredencial);
        });
    }

    /**
     * Registra un intento fallido de login y bloquea la cuenta si aplica.
     *
     * @param array $credencial
     * @return void
     */
    private function registrarIntentoFallido(array $credencial): void
    {
        $nuevosIntentos = (int) $credencial['intentos_fallidos'] + 1;
        $datosUpdate = ['intentos_fallidos' => $nuevosIntentos];

        if ($nuevosIntentos >= self::MAX_INTENTOS) {
            $datosUpdate['bloqueado_hasta'] = gmdate('Y-m-d H:i:s', strtotime('+' . self::MINUTOS_BLOQUEO . ' minutes'));
        }

        $this->credencialRepo->update((int) $credencial['id'], $datosUpdate);
    }
}