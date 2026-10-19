<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\NotFoundException;
use App\Exceptions\ConflictException;
use App\Exceptions\DomainException;
use App\Repositories\RolRepository;
use App\Repositories\UsuarioRolRepository;

/**
 * Servicio para la gestión de roles.
 */
final class RolService extends BaseService
{
    /** @var RolRepository */
    private RolRepository $rolRepo;

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
        $this->rolRepo = new RolRepository($pdo);
        $this->usuarioRolRepo = new UsuarioRolRepository($pdo);
    }

    /**
     * Crea un nuevo rol.
     *
     * @param array $datos
     * @return int
     * @throws ConflictException
     */
    public function crear(array $datos): int
    {
        if (isset($datos['nombre_rol']) && $this->rolRepo->findByNombreRol($datos['nombre_rol'])) {
            throw new ConflictException('Ya existe un rol con ese nombre.');
        }

        return $this->rolRepo->create($datos);
    }

    /**
     * Actualiza un rol existente.
     *
     * @param int $id
     * @param array $datos
     * @return void
     * @throws NotFoundException
     * @throws ConflictException
     */
    public function actualizar(int $id, array $datos): void
    {
        if (!$this->rolRepo->exists($id)) {
            throw new NotFoundException('Rol no encontrado.');
        }

        if (isset($datos['nombre_rol'])) {
            $existente = $this->rolRepo->findByNombreRol($datos['nombre_rol']);
            if ($existente && (int) $existente['id'] !== $id) {
                throw new ConflictException('Ya existe otro rol con ese nombre.');
            }
        }

        $this->rolRepo->update($id, $datos);
    }

    /**
     * Elimina un rol si no tiene usuarios asignados.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     * @throws DomainException
     */
    public function eliminar(int $id): void
    {
        if (!$this->rolRepo->exists($id)) {
            throw new NotFoundException('Rol no encontrado.');
        }

        if ($id === 1) {
            throw new DomainException('El rol Administrador no se puede eliminar.');
        }

        if ($this->usuarioRolRepo->countByRolId($id) > 0) {
            throw new DomainException('El rol tiene usuarios asignados; reasígnalos primero.');
        }

        $this->rolRepo->delete($id);
    }

    /**
     * Busca un rol por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->rolRepo->findById($id);
    }

    /**
     * Busca un rol por su nombre.
     *
     * @param string $nombreRol
     * @return array|null
     */
    public function buscarPorNombre(string $nombreRol): ?array
    {
        return $this->rolRepo->findByNombreRol($nombreRol);
    }

    /**
     * Obtiene todos los roles ordenados por ID.
     *
     * @return array
     */
    public function listar(): array
    {
        return $this->rolRepo->findAllOrderedById();
    }
}