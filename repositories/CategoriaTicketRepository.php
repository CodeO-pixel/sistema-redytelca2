<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad CategoriaTicket.
 */
final class CategoriaTicketRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'categorias_ticket';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene todas las categorías ordenadas por nombre ascendente.
     *
     * @return array
     */
    public function findAllOrderedByNombre(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY nombre ASC";
        $stmt = $this->prepareAndExecute($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca una categoría por su nombre.
     *
     * @param string $nombre
     * @return array|null
     */
    public function findByNombre(string $nombre): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE nombre = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$nombre]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Obtiene las categorías activas ordenadas por nombre ascendente.
     *
     * @return array
     */
    public function findActivas(): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE activa = 1 ORDER BY nombre ASC";
        $stmt = $this->prepareAndExecute($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}