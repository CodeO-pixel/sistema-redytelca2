<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad Modulo
 */
final class Modulo extends BaseModel
{
    /** @var string */
    protected string $nombre_modulo = '';

    /**
     * @return string
     */
    public function getNombreModulo(): string
    {
        return $this->nombre_modulo;
    }

    /**
     * @param string $nombre_modulo
     * @return self
     */
    public function setNombreModulo(string $nombre_modulo): self
    {
        $this->nombre_modulo = $nombre_modulo;
        return $this;
    }
}