<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad Rol
 */
final class Rol extends BaseModel
{
    /** @var string */
    protected string $nombre_rol = '';

    /** @var string|null */
    protected ?string $descripcion = null;

    /**
     * @return string
     */
    public function getNombreRol(): string
    {
        return $this->nombre_rol;
    }

    /**
     * @param string $nombre_rol
     * @return self
     */
    public function setNombreRol(string $nombre_rol): self
    {
        $this->nombre_rol = $nombre_rol;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    /**
     * @param string|null $descripcion
     * @return self
     */
    public function setDescripcion(?string $descripcion): self
    {
        $this->descripcion = $descripcion;
        return $this;
    }
}