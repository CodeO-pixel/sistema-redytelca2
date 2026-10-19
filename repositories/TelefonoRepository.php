<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Telefono.
 */
final class TelefonoRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'telefono';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Busca un teléfono por su número.
     *
     * @param string $numero
     * @return array|null
     */
    public function findByNumero(string $numero): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE numero = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$numero]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Busca un teléfono por su número o lo crea si no existe.
     *
     * @param string $numero
     * @return int ID del teléfono.
     */
    public function findOrCreate(string $numero): int
    {
        $row = $this->findByNumero($numero);
        if ($row) {
            return (int) $row['id'];
        }

        return $this->create(['numero' => $numero]);
    }
}