<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

final class BancoRepository extends BaseRepository
{
    protected string $table = 'bancos';
    protected string $primaryKey = 'id';

    public function findAllOrderedByNombre(): array
    {
        $stmt = $this->prepareAndExecute("SELECT * FROM {$this->table} ORDER BY nombre ASC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findActivos(): array
    {
        $stmt = $this->prepareAndExecute("SELECT * FROM {$this->table} WHERE activo = 1 ORDER BY nombre ASC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findByNombre(string $nombre): ?array
    {
        $stmt = $this->prepareAndExecute("SELECT * FROM {$this->table} WHERE nombre = ? LIMIT 1", [$nombre]);
        $banco = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $banco ?: null;
    }

    public function findActivoById(int $id): ?array
    {
        $stmt = $this->prepareAndExecute(
            "SELECT * FROM {$this->table} WHERE id = ? AND activo = 1 LIMIT 1",
            [$id]
        );
        $banco = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $banco ?: null;
    }
}
