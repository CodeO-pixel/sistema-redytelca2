<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Modulo.
 */
final class ModuloRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'modulos';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene todos los módulos ordenados por ID ascendente.
     *
     * @return array
     */
    public function findAllOrderedById(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY id ASC";
        $stmt = $this->prepareAndExecute($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}