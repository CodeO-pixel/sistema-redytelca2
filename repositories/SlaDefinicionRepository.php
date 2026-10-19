<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad SlaDefinicion.
 */
final class SlaDefinicionRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'sla_definiciones';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene las definiciones SLA de una categoría.
     *
     * @param int $categoriaId
     * @return array
     */
    public function findByCategoriaId(int $categoriaId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE categoria_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$categoriaId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca una definición SLA por su categoría y prioridad.
     *
     * Replica la búsqueda que hacen los triggers
     * trg_tickets_sla_insert/update sobre sla_definiciones.
     *
     * @param int $categoriaId
     * @param string $prioridad
     * @return array|null
     */
    public function findByCategoriaIdAndPrioridad(int $categoriaId, string $prioridad): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE categoria_id = ? AND prioridad = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$categoriaId, $prioridad]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Obtiene todas las definiciones SLA ordenadas por categoría y prioridad.
     *
     * @return array
     */
    public function findAllOrderedByCategoria(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY categoria_id ASC, prioridad ASC";
        $stmt = $this->prepareAndExecute($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}