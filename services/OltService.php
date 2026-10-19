<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\NotFoundException;
use App\Exceptions\DomainException;
use App\Repositories\OltRepository;
use App\Repositories\NodoRepository;

/**
 * Servicio para la gestión de OLTs.
 */
final class OltService extends BaseService
{
    /** @var OltRepository */
    private OltRepository $oltRepo;

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
        $this->oltRepo = new OltRepository($pdo);
        $this->nodoRepo = new NodoRepository($pdo);
    }

    /**
     * Crea una nueva OLT.
     *
     * @param array $datos
     * @return int
     * @throws NotFoundException
     */
    public function crear(array $datos): int
    {
        if (!isset($datos['nodo_id']) || !$this->nodoRepo->exists((int) $datos['nodo_id'])) {
            throw new NotFoundException('El nodo especificado no existe.');
        }

        return $this->oltRepo->create($datos);
    }

    /**
     * Actualiza una OLT existente.
     *
     * @param int $id
     * @param array $datos
     * @return void
     * @throws NotFoundException
     */
    public function actualizar(int $id, array $datos): void
    {
        if (!$this->oltRepo->exists($id)) {
            throw new NotFoundException('OLT no encontrada.');
        }

        if (isset($datos['nodo_id']) && !$this->nodoRepo->exists((int) $datos['nodo_id'])) {
            throw new NotFoundException('El nodo especificado no existe.');
        }

        $this->oltRepo->update($id, $datos);
    }

    /**
     * Elimina una OLT si no tiene NAPs asociadas.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     * @throws DomainException
     */
    public function eliminar(int $id): void
    {
        if (!$this->oltRepo->exists($id)) {
            throw new NotFoundException('OLT no encontrada.');
        }

        if ($this->oltRepo->countNapsByOltId($id) > 0) {
            throw new DomainException('La OLT tiene NAPs asociadas; reasígnalas primero.');
        }

        $this->oltRepo->delete($id);
    }

    /**
     * Busca una OLT por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->oltRepo->findById($id);
    }

    /**
     * Busca una OLT por su código.
     *
     * @param string $codigo
     * @return array|null
     */
    public function buscarPorCodigo(string $codigo): ?array
    {
        return $this->oltRepo->findByCodigo($codigo);
    }

    /**
     * Busca una OLT por su IP de gestión.
     *
     * @param string $ipGestion
     * @return array|null
     */
    public function buscarPorIp(string $ipGestion): ?array
    {
        return $this->oltRepo->findByIpGestion($ipGestion);
    }

    /**
     * Obtiene todas las OLTs ordenadas por ID.
     *
     * @return array
     */
    public function listar(): array
    {
        return $this->oltRepo->findAllOrderedById();
    }

    /**
     * Obtiene las OLTs asociadas a un nodo.
     *
     * @param int $nodoId
     * @return array
     */
    public function listarPorNodo(int $nodoId): array
    {
        return $this->oltRepo->findByNodoId($nodoId);
    }

    /**
     * Cuenta las NAPs asociadas a una OLT.
     *
     * @param int $id
     * @return int
     */
    public function contarNaps(int $id): int
    {
        return $this->oltRepo->countNapsByOltId($id);
    }
}