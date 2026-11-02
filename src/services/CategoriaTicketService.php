<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\NotFoundException;
use App\Exceptions\ConflictException;
use App\Exceptions\DomainException;
use App\Repositories\CategoriaTicketRepository;

/**
 * Servicio para la gestión de categorías de tickets.
 */
final class CategoriaTicketService extends BaseService
{
    /** @var CategoriaTicketRepository */
    private CategoriaTicketRepository $categoriaRepo;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->categoriaRepo = new CategoriaTicketRepository($pdo);
    }

    /**
     * Crea una nueva categoría de ticket.
     *
     * @param array $datos
     * @return int
     * @throws ConflictException
     */
    public function crear(array $datos): int
    {
        if (isset($datos['nombre']) && $this->categoriaRepo->findByNombre($datos['nombre'])) {
            throw new ConflictException('Ya existe una categoría con ese nombre.');
        }

        return $this->categoriaRepo->create($datos);
    }

    /**
     * Actualiza una categoría de ticket existente.
     *
     * @param int $id
     * @param array $datos
     * @return void
     * @throws NotFoundException
     * @throws ConflictException
     */
    public function actualizar(int $id, array $datos): void
    {
        if (!$this->categoriaRepo->exists($id)) {
            throw new NotFoundException('Categoría no encontrada.');
        }

        if (isset($datos['nombre'])) {
            $existente = $this->categoriaRepo->findByNombre($datos['nombre']);
            if ($existente && (int) $existente['id'] !== $id) {
                throw new ConflictException('Ya existe otra categoría con ese nombre.');
            }
        }

        $this->categoriaRepo->update($id, $datos);
    }

    /**
     * Elimina una categoría de ticket si no tiene tickets asociados.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     * @throws DomainException
     */
    public function eliminar(int $id): void
    {
        if (!$this->categoriaRepo->exists($id)) {
            throw new NotFoundException('Categoría no encontrada.');
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tickets WHERE categoria_id = ?");
        $stmt->execute([$id]);
        $count = (int) $stmt->fetchColumn();

        if ($count > 0) {
            throw new DomainException('La categoría tiene tickets asociados. Desactívala (activa=0) en lugar de eliminarla.');
        }

        $this->categoriaRepo->delete($id);
    }

    /**
     * Obtiene todas las categorías ordenadas por nombre.
     *
     * @return array
     */
    public function listar(): array
    {
        return $this->categoriaRepo->findAllOrderedByNombre();
    }

    /**
     * Obtiene solo las categorías activas, ordenadas por nombre.
     *
     * Para el portal de cliente. El endpoint staff (`index()`) devuelve
     * todas (incluidas las inactivas) porque el admin las gestiona.
     * El portal solo debe ver las activas, las que se pueden usar al
     * crear un ticket.
     *
     * @return array
     */
    public function listarActivas(): array
    {
        return $this->categoriaRepo->findActivas();
    }
}