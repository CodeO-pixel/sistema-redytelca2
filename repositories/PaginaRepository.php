<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Pagina.
 */
final class PaginaRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'paginas';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene todas las páginas ordenadas por módulo y luego por ID.
     *
     * @return array
     */
    public function findAllOrderedByModulo(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY modulo_id ASC, id ASC";
        $stmt = $this->prepareAndExecute($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las páginas asociadas a un módulo.
     *
     * @param int $moduloId
     * @return array
     */
    public function findByModuloId(int $moduloId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE modulo_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$moduloId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca una página por su URL.
     *
     * url_pagina no es UNIQUE en el esquema actual. Se usa LIMIT 1
     * asumiendo 1:1 en la práctica. El service (spec 137) debe
     * asumir el mismo contrato.
     *
     * @param string $urlPagina
     * @return array|null
     */
    public function findByUrlPagina(string $urlPagina): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE url_pagina = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$urlPagina]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }
}