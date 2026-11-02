<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;
use PDO;

/**
 * Repositorio para la entidad de solo lectura TransicionEstado.
 */
final class TransicionEstadoRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'transicion_estado';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene todas las transiciones de estado ordenadas por tabla, origen y destino.
     *
     * @return array
     */
    public function findAllOrdered(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY tabla ASC, estado_origen ASC, estado_destino ASC";
        $stmt = $this->prepareAndExecute($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las transiciones de estado de una tabla específica.
     *
     * @param string $tabla Nombre de la tabla.
     * @return array
     */
    public function findByTabla(string $tabla): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE tabla = ? ORDER BY estado_origen ASC, estado_destino ASC";
        $stmt = $this->prepareAndExecute($sql, [$tabla]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}