<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad CategoriaTicket
 */
final class CategoriaTicket extends BaseModel
{
    /** @var string */
    protected string $nombre = '';

    /** @var string|null */
    protected ?string $descripcion = null;

    /** @var string */
    protected string $color = '#104aa8';

    /** @var bool */
    protected bool $activa = true;

    /** @var string|null */
    protected ?string $creado_en = null;

    /**
     * @return string
     */
    public function getNombre(): string
    {
        return $this->nombre;
    }

    /**
     * @param string $nombre
     * @return self
     */
    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;
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

    /**
     * @return string
     */
    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * @param string $color
     * @return self
     */
    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @return bool
     */
    public function getActiva(): bool
    {
        return $this->activa;
    }

    /**
     * @param bool $activa
     * @return self
     */
    public function setActiva(bool $activa): self
    {
        $this->activa = $activa;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCreadoEn(): ?string
    {
        return $this->creado_en;
    }

    /**
     * @param string|null $creado_en
     * @return self
     */
    public function setCreadoEn(?string $creado_en): self
    {
        $this->creado_en = $creado_en;
        return $this;
    }
}