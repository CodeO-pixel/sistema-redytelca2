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
     * SELECT * incluye logo_path_sidebar (B3.11).
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
     * Devuelve los tres campos de branding que se usan pre-login:
     *   - nombre_empresa
     *   - logo_path         (logo para fondo claro: login)
     *   - logo_path_sidebar (logo para fondo oscuro: sidebar)
     *   - color_primario
     *
     * B3.11: añadido logo_path_sidebar para que el sidebar pueda tener un
     * logo distinto al del login (Sonar pattern: "logo" + "logo_dark").
     *
     * @return array|null
     */
    public function findPublicBranding(): ?array
    {
        $sql = "SELECT nombre_empresa, logo_path, logo_path_sidebar, color_primario
                FROM {$this->table} WHERE id = 1 LIMIT 1";
        $stmt = $this->prepareAndExecute($sql);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }
}