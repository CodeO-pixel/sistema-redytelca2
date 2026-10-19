<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\NotFoundException;
use App\Exceptions\DomainException;
use App\Repositories\NodoRepository;

/**
 * Servicio para la gestión de nodos de red.
 */
final class NodoService extends BaseService
{
    /** @var NodoRepository */
    private NodoRepository $nodoRepo;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->nodoRepo = new NodoRepository($pdo);
    }

    /**
     * Crea un nuevo nodo.
     *
     * @param array $datos
     * @return int
     */
    public function crear(array $datos): int
    {
        return $this->nodoRepo->create($datos);
    }

    /**
     * Actualiza un nodo existente.
     *
     * @param int $id
     * @param array $datos
     * @return void
     * @throws NotFoundException
     */
    public function actualizar(int $id, array $datos): void
    {
        if (!$this->nodoRepo->exists($id)) {
            throw new NotFoundException('Nodo no encontrado.');
        }

        $this->nodoRepo->update($id, $datos);
    }

    /**
     * Elimina un nodo si no tiene OLTs asociadas.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     * @throws DomainException
     */
    public function eliminar(int $id): void
    {
        if (!$this->nodoRepo->exists($id)) {
            throw new NotFoundException('Nodo no encontrado.');
        }

        if ($this->nodoRepo->countOltsByNodoId($id) > 0) {
            throw new DomainException('El nodo tiene OLTs asociadas; reasígnalas primero.');
        }

        $this->nodoRepo->delete($id);
    }

    /**
     * Busca un nodo por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->nodoRepo->findById($id);
    }

    /**
     * Busca un nodo por su nombre.
     *
     * @param string $nombre
     * @return array|null
     */
    public function buscarPorNombre(string $nombre): ?array
    {
        return $this->nodoRepo->findByNombre($nombre);
    }

    /**
     * Obtiene todos los nodos ordenados por nombre.
     *
     * @return array
     */
    public function listar(): array
    {
        return $this->nodoRepo->findAllOrderedByNombre();
    }

    /**
     * Cuenta las OLTs asociadas a un nodo.
     *
     * @param int $id
     * @return int
     */
    public function contarOlts(int $id): int
    {
        return $this->nodoRepo->countOltsByNodoId($id);
    }
}