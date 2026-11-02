<?php
// services/ClienteCredencialService.php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\ClienteCredencialRepository;
use App\Repositories\ClienteRepository;
use App\Repositories\SesionRepository;

/**
 * Servicio de gestión de credenciales del portal de cliente.
 *
 * Separado de PortalAuthService (que gestiona login/logout/cambio de
 * contraseña del cliente). Este service lo usa el ADMIN para:
 *   - Ver el estado de la credencial de un cliente.
 *   - Resetear la contraseña (genera password temporal CSPRNG nueva).
 *   - Activar/suspender la credencial.
 *
 * Patrón de industria:
 *   - Salesforce: "Reset password" muestra una temporal UNA VEZ.
 *   - Azure AD: "Reset password" devuelve la temporal al admin.
 *   - Splynx: "Reset customer password" desde el panel admin.
 *   - Sonar: "Reset password" desde el panel admin.
 */
final class ClienteCredencialService extends BaseService
{
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
     * Devuelve el estado de la credencial de un cliente.
     *
     * El hash de la password NUNCA se devuelve. Solo metadatos.
     *
     * @param int $clienteId
     * @return array
     * @throws NotFoundException
     */
    public function getEstado(int $clienteId): array
    {
        if (!$this->clienteRepo->exists($clienteId)) {
            throw new NotFoundException('Cliente no encontrado.');
        }

        $credencial = $this->credencialRepo->findByClienteId($clienteId);
        if (!$credencial) {
            throw new NotFoundException('El cliente no tiene credencial de portal.');
        }

        return [
            'id' => (int) $credencial['id'],
            'cliente_id' => (int) $credencial['cliente_id'],
            'username' => $credencial['username'],
            'estado' => $credencial['estado'],
            'must_change_password' => (bool) $credencial['must_change_password'],
            'intentos_fallidos' => (int) $credencial['intentos_fallidos'],
            'bloqueado_hasta' => $credencial['bloqueado_hasta'],
            'ultimo_acceso' => $credencial['ultimo_acceso'],
            'creado_en' => $credencial['creado_en']
        ];
    }

    /**
     * Resetea la contraseña de un cliente.
     *
     * Genera password temporal con CSPRNG (16 chars hex). La devuelve
     * UNA VEZ en el return. Marca must_change_password=1 y revoca todas
     * las sesiones activas (OWASP ASVS V7.4.3).
     *
     * @param int $clienteId
     * @param string|null $passwordNueva Si es null, se genera aleatoria.
     * @return array{username: string, password_temporal: string}
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function resetPassword(int $clienteId, ?string $passwordNueva = null): array
    {
        if (!$this->clienteRepo->exists($clienteId)) {
            throw new NotFoundException('Cliente no encontrado.');
        }

        $credencial = $this->credencialRepo->findByClienteId($clienteId);
        if (!$credencial) {
            throw new NotFoundException('El cliente no tiene credencial de portal.');
        }

        $idCredencial = (int) $credencial['id'];

        if ($passwordNueva === null || $passwordNueva === '') {
            $passwordNueva = $this->generarPasswordTemporal();
        } else {
            $passwordNueva = trim($passwordNueva);
            if (strlen($passwordNueva) < 8) {
                throw new ValidationException(['password_nueva' => 'La contraseña debe tener al menos 8 caracteres.']);
            }
        }

        $this->transaction(function () use ($idCredencial, $passwordNueva) {
            $this->credencialRepo->update($idCredencial, [
                'password' => password_hash($passwordNueva, PASSWORD_DEFAULT),
                'must_change_password' => 1,
                'intentos_fallidos' => 0,
                'bloqueado_hasta' => null
            ]);
            $this->sesionRepo->revocarTodasByCredencialId($idCredencial);
        });

        return [
            'username' => $credencial['username'],
            'password_temporal' => $passwordNueva
        ];
    }

    /**
     * Activa o suspende la credencial de un cliente.
     *
     * Si se suspende, revoca todas las sesiones activas.
     * Si se activa, resetea intentos fallidos y bloqueo.
     *
     * @param int $clienteId
     * @param string $estado 'activa' | 'suspendida'
     * @return void
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function toggleEstado(int $clienteId, string $estado): void
    {
        if (!in_array($estado, ['activa', 'suspendida'], true)) {
            throw new ValidationException(['estado' => 'Estado inválido. Debe ser "activa" o "suspendida".']);
        }

        if (!$this->clienteRepo->exists($clienteId)) {
            throw new NotFoundException('Cliente no encontrado.');
        }

        $credencial = $this->credencialRepo->findByClienteId($clienteId);
        if (!$credencial) {
            throw new NotFoundException('El cliente no tiene credencial de portal.');
        }

        $idCredencial = (int) $credencial['id'];

        $this->transaction(function () use ($idCredencial, $estado) {
            $datos = ['estado' => $estado];
            if ($estado === 'activa') {
                $datos['intentos_fallidos'] = 0;
                $datos['bloqueado_hasta'] = null;
            }
            $this->credencialRepo->update($idCredencial, $datos);

            if ($estado === 'suspendida') {
                $this->sesionRepo->revocarTodasByCredencialId($idCredencial);
            }
        });
    }

    /**
     * Genera una password temporal con CSPRNG.
     *
     * 16 caracteres hex (128 bits de entropía). Cumple OWASP ASVS 5.0
     * V2.3.1 y V2.5.1. NIST SP 800-63B §5.1.1.2.
     *
     * @return string
     */
    private function generarPasswordTemporal(): string
    {
        return bin2hex(random_bytes(8));
    }
}