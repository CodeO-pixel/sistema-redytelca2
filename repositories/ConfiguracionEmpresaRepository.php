<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad ConfiguracionEmpresa.
 */
final class ConfiguracionEmpresaRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'configuracion_empresa';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene la configuración global de la empresa.
     *
     * @return array|null
     */
    public function findSingleton(): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = 1 LIMIT 1";
        $stmt = $this->prepareAndExecute($sql);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Obtiene únicamente los datos públicos de branding de la empresa.
     *
     * @return array|null
     */
    public function findPublicBranding(): ?array
    {
        $sql = "SELECT nombre_empresa, logo_path, color_primario FROM {$this->table} WHERE id = 1 LIMIT 1";
        $stmt = $this->prepareAndExecute($sql);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }
}