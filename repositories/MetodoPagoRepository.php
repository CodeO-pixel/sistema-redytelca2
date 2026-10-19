<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad MetodoPago.
 */
final class MetodoPagoRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'metodos_pago';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene los métodos de pago activos, ordenados por orden y nombre.
     *
     * @return array
     */
    public function findActivos(): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE activo = 1 ORDER BY orden ASC, nombre ASC";
        $stmt = $this->prepareAndExecute($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca un método de pago por su código.
     *
     * @param string $codigo
     * @return array|null
     */
    public function findByCodigo(string $codigo): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE codigo = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$codigo]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }
}