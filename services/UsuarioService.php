<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\NotFoundException;
use App\Exceptions\ConflictException;
use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;
use App\Repositories\UsuarioRepository;
use App\Repositories\RolRepository;
use App\Repositories\SesionRepository;
use App\Repositories\UsuarioRolRepository;

/**
 * Servicio para la gestión de usuarios y sus roles.
 * La baja de un usuario es lógica (estado='Inactivo') + revocación de sesiones. El borrado físico no se permite. La desactivación del último administrador activo está protegida.
 */
final class UsuarioService extends BaseService
{
    /** @var UsuarioRepository */
    private UsuarioRepository $usuarioRepo;

    /** @var RolRepository */
    private RolRepository $rolRepo;

    /** @var SesionRepository */
    private SesionRepository $sesionRepo;

    /** @var UsuarioRolRepository */
    private UsuarioRolRepository $usuarioRolRepo;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->usuarioRepo = new UsuarioRepository($pdo);
        $this->rolRepo = new RolRepository($pdo);
        $this->sesionRepo = new SesionRepository($pdo);
        $this->usuarioRolRepo = new UsuarioRolRepository($pdo);
    }

    /**
     * Crea un nuevo usuario.
     *
     * @param array $datos
     * @return int
     * @throws NotFoundException
     * @throws ConflictException
     * @throws ValidationException
     */
    public function crear(array $datos): int
    {
        if (!empty($datos['roles'])) {
            foreach ($datos['roles'] as $rolId) {
                if (!$this->rolRepo->exists((int) $rolId)) {
                    throw new NotFoundException('El rol especificado no existe.');
                }
            }
        }

        if (isset($datos['username']) && $this->usuarioRepo->findByUsername($datos['username'])) {
            throw new ConflictException('El nombre de usuario ya está en uso.');
        }

        if (isset($datos['email']) && $this->usuarioRepo->findByEmail($datos['email'])) {
            throw new ConflictException('El correo electrónico ya está en uso.');
        }

        $this->validarEstado($datos);

        if (!isset($datos['password']) || strlen($datos['password']) < 8) {
            throw new ValidationException(['password' => 'La contraseña debe tener al menos 8 caracteres.']);
        }
        $datos['password'] = password_hash($datos['password'], PASSWORD_DEFAULT);

        $datos['must_change_password'] = 1;

        return $this->transaction(function () use ($datos) {
            $roles = $datos['roles'] ?? [];
            unset($datos['roles']);

            $id = $this->usuarioRepo->create($datos);

            if (!empty($roles)) {
                $this->asignarRoles($id, $roles);
            }

            return $id;
        });
    }

    /**
     * Actualiza un usuario existente.
     *
     * @param int $id
     * @param array $datos
     * @return void
     * @throws NotFoundException
     * @throws ConflictException
     * @throws ValidationException
     * @throws DomainException
     */
    public function actualizar(int $id, array $datos): void
    {
        if (!$this->usuarioRepo->exists($id)) {
            throw new NotFoundException('Usuario no encontrado.');
        }

        if (isset($datos['username'])) {
            $existente = $this->usuarioRepo->findByUsername($datos['username']);
            if ($existente && (int) $existente['id'] !== $id) {
                throw new ConflictException('El nombre de usuario ya está en uso.');
            }
        }

        if (isset($datos['email'])) {
            $existente = $this->usuarioRepo->findByEmail($datos['email']);
            if ($existente && (int) $existente['id'] !== $id) {
                throw new ConflictException('El correo electrónico ya está en uso.');
            }
        }

        $this->validarEstado($datos);

        // No se permite cambiar la contraseña por esta vía
        unset($datos['password']);

        if (isset($datos['roles'])) {
            $rolesActuales = $this->listarRoles($id);
            $idsActuales = array_column($rolesActuales, 'id');
            $eraAdmin = in_array(1, $idsActuales, true);
            $seraAdmin = in_array(1, array_map('intval', $datos['roles']), true);

            if ($eraAdmin && !$seraAdmin && $this->contarOtrosAdminsActivos($id) === 0) {
                throw new DomainException('No puedes quitarle el rol de Administrador: es el único administrador activo del sistema.');
            }
        }

        $this->transaction(function () use ($id, $datos) {
            $roles = $datos['roles'] ?? null;
            unset($datos['roles']);

            if (!empty($datos)) {
                $this->usuarioRepo->update($id, $datos);
            }

            if ($roles !== null) {
                $this->asignarRoles($id, $roles);
            }
        });
    }

    /**
     * Desactiva un usuario (baja lógica) y revoca TODAS sus sesiones no revocadas.
     *
     * La revocación delega en SesionRepository::revocarTodasByUsuarioId,
     * que actualiza todas las filas de sesiones del usuario (sin el LIMIT 20
     * de findByUsuarioId) y fija motivo_cierre='revocada_por_admin'.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     * @throws DomainException
     */
    
    public function desactivar(int $id): void
    {
        if (!$this->usuarioRepo->exists($id)) {
            throw new NotFoundException('Usuario no encontrado.');
        }

        $roles = $this->listarRoles($id);
        $esAdmin = false;
        foreach ($roles as $rol) {
            if ((int) $rol['id'] === 1) {
                $esAdmin = true;
                break;
            }
        }

        if ($esAdmin && $this->contarOtrosAdminsActivos($id) === 0) {
            throw new DomainException('No puedes desactivar al único administrador activo del sistema.');
        }

        $this->sesionRepo->revocarTodasByUsuarioId($id);

        $this->usuarioRepo->update($id, ['estado' => 'Inactivo']);
    }

    /**
     * Cambia la contraseña de un usuario validando la actual.
     *
     * @param int $id
     * @param string $passwordActual
     * @param string $passwordNueva
     * @return void
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function cambiarPassword(int $id, string $passwordActual, string $passwordNueva): void
    {
        $usuario = $this->usuarioRepo->findById($id);
        if (!$usuario) {
            throw new NotFoundException('Usuario no encontrado.');
        }

        if (!password_verify($passwordActual, $usuario['password'])) {
            throw new ValidationException(['password_actual' => 'La contraseña actual es incorrecta.']);
        }

        if (strlen($passwordNueva) < 8) {
            throw new ValidationException(['password' => 'La nueva contraseña debe tener al menos 8 caracteres.']);
        }

        $this->usuarioRepo->update($id, [
            'password' => password_hash($passwordNueva, PASSWORD_DEFAULT),
            'must_change_password' => 0
        ]);
    }

    /**
     * Restablece la contraseña de un usuario (acción de administrador).
     *
     * @param int $id
     * @param string $passwordNueva
     * @return void
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function resetearPassword(int $id, string $passwordNueva): void
    {
        if (!$this->usuarioRepo->exists($id)) {
            throw new NotFoundException('Usuario no encontrado.');
        }

        if (strlen($passwordNueva) < 8) {
            throw new ValidationException(['password' => 'La nueva contraseña debe tener al menos 8 caracteres.']);
        }

        $this->usuarioRepo->update($id, [
            'password' => password_hash($passwordNueva, PASSWORD_DEFAULT),
            'must_change_password' => 1
        ]);
    }

    /**
     * Marca el correo electrónico de un usuario como verificado.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     */
    public function verificarEmail(int $id): void
    {
        if (!$this->usuarioRepo->exists($id)) {
            throw new NotFoundException('Usuario no encontrado.');
        }

        $this->usuarioRepo->update($id, ['email_verified' => 1]);
    }

    /**
     * Bloquea a un usuario.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     */
    public function bloquear(int $id): void
    {
        if (!$this->usuarioRepo->exists($id)) {
            throw new NotFoundException('Usuario no encontrado.');
        }

        $this->usuarioRepo->update($id, ['estado' => 'Bloqueado']);
    }

    /**
     * Activa a un usuario.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     */
    public function activar(int $id): void
    {
        if (!$this->usuarioRepo->exists($id)) {
            throw new NotFoundException('Usuario no encontrado.');
        }

        $this->usuarioRepo->update($id, ['estado' => 'Activo']);
    }

    /**
     * Asigna roles a un usuario.
     *
     * @param int $id
     * @param array $idsRoles
     * @return void
     * @throws NotFoundException
     */
    public function asignarRoles(int $id, array $idsRoles): void
    {
        if (!$this->usuarioRepo->exists($id)) {
            throw new NotFoundException('Usuario no encontrado.');
        }

        $rolesNormalizados = array_filter(array_unique(array_map('intval', $idsRoles)), fn($r) => $r > 0);

        foreach ($rolesNormalizados as $rolId) {
            if (!$this->rolRepo->exists($rolId)) {
                throw new NotFoundException('El rol especificado no existe.');
            }
        }

        $this->usuarioRolRepo->deleteByUsuarioId($id);

        foreach ($rolesNormalizados as $rolId) {
            $this->usuarioRolRepo->insertIgnore($id, $rolId);
        }
    }

    /**
     * Obtiene los roles asignados a un usuario.
     *
     * @param int $id
     * @return array
     * @throws NotFoundException
     */
    public function listarRoles(int $id): array
    {
        if (!$this->usuarioRepo->exists($id)) {
            throw new NotFoundException('Usuario no encontrado.');
        }

        return $this->usuarioRolRepo->findRolesByUsuarioId($id);
    }

    /**
     * Cuenta cuántos otros administradores activos existen en el sistema.
     *
     * @param int $idUsuario
     * @return int
     */
    public function contarOtrosAdminsActivos(int $idUsuario): int
    {
        return $this->usuarioRolRepo->countOtrosAdminsActivos($idUsuario);
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
     * Busca un usuario por su nombre de usuario.
     *
     * @param string $username
     * @return array|null
     */
    public function buscarPorUsername(string $username): ?array
    {
        return $this->usuarioRepo->findByUsername($username);
    }

    /**
     * Busca un usuario por su correo electrónico.
     *
     * @param string $email
     * @return array|null
     */
    public function buscarPorEmail(string $email): ?array
    {
        return $this->usuarioRepo->findByEmail($email);
    }

    /**
     * Obtiene todos los usuarios ordenados por nombre de usuario.
     *
     * @return array
     */
    public function listar(): array
    {
        return $this->usuarioRepo->findAllOrderedByUsername();
    }

    /**
     * Obtiene todos los usuarios con sus roles y última conexión.
     *
     * @return array
     */
    public function listarConRoles(): array
    {
        $sqlUsuarios = "
            SELECT u.id, u.username, u.email, u.must_change_password,
                   u.email_verified, u.estado,
                   (SELECT MAX(creado_en) FROM sesiones WHERE usuario_id = u.id) AS ultima_conexion
            FROM usuarios u ORDER BY u.username ASC
        ";
        $stmtUsuarios = $this->pdo->query($sqlUsuarios);
        $usuarios = $stmtUsuarios->fetchAll(\PDO::FETCH_ASSOC);

        $sqlRoles = "
            SELECT ur.usuario_id, r.id AS id_rol, r.nombre_rol
            FROM usuario_rol ur INNER JOIN rol r ON r.id = ur.rol_id
            ORDER BY r.id
        ";
        $stmtRoles = $this->pdo->query($sqlRoles);
        $rolesData = $stmtRoles->fetchAll(\PDO::FETCH_ASSOC);

        $rolesPorUsuario = [];
        foreach ($rolesData as $row) {
            $idU = (int) $row['usuario_id'];
            if (!isset($rolesPorUsuario[$idU])) {
                $rolesPorUsuario[$idU] = [];
            }
            $rolesPorUsuario[$idU][] = [
                'id_rol' => (int) $row['id_rol'],
                'nombre_rol' => $row['nombre_rol']
            ];
        }

        foreach ($usuarios as &$u) {
            $idU = (int) $u['id'];
            $u['roles'] = $rolesPorUsuario[$idU] ?? [];
            $u['roles_ids'] = array_column($u['roles'], 'id_rol');
            $u['roles_nombres'] = implode(', ', array_column($u['roles'], 'nombre_rol'));
            $u['es_admin'] = in_array(1, $u['roles_ids'], true);
        }
        unset($u);

        return $usuarios;
    }

    /**
     * Método privado. Valida el ENUM de estado. No se llama desde fuera del service.
     *
     * @param array $datos
     * @return void
     * @throws ValidationException
     */
    private function validarEstado(array $datos): void
    {
        if (isset($datos['estado']) && !in_array($datos['estado'], ['Activo', 'Inactivo', 'Bloqueado'], true)) {
            throw new ValidationException(['estado' => 'Estado de usuario inválido.']);
        }
    }
}