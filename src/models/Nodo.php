<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad Nodo
 */
final class Nodo extends BaseModel
{
    /** @var string */
    protected string $nombre = '';

    /** @var string|null */
    protected ?string $ubicacion = null;

    /** @var float */
    protected float $latitud = 0.0;

    /** @var float */
    protected float $longitud = 0.0;

    /** @var string */
    protected string $estado = 'activo';

    /** @var string|null */
    protected ?string $creado_en = null;

    /** @var string|null */
    protected ?string $actualizado_en = null;

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
    public function getUbicacion(): ?string
    {
        return $this->ubicacion;
    }

    /**
     * @param string|null $ubicacion
     * @return self
     */
    public function setUbicacion(?string $ubicacion): self
    {
        $this->ubicacion = $ubicacion;
        return $this;
    }

    /**
     * @return float
     */
    public function getLatitud(): float
    {
        return $this->latitud;
    }

    /**
     * @param float $latitud
     * @return self
     */
    public function setLatitud(float $latitud): self
    {
        $this->latitud = $latitud;
        return $this;
    }

    /**
     * @return float
     */
    public function getLongitud(): float
    {
        return $this->longitud;
    }

    /**
     * @param float $longitud
     * @return self
     */
    public function setLongitud(float $longitud): self
    {
        $this->longitud = $longitud;
        return $this;
    }

    /**
     * @return string
     */
    public function getEstado(): string
    {
        return $this->estado;
    }

    /**
     * @param string $estado
     * @return self
     */
    public function setEstado(string $estado): self
    {
        $this->estado = $estado;
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

    /**
     * @return string|null
     */
    public function getActualizadoEn(): ?string
    {
        return $this->actualizado_en;
    }

    /**
     * @param string|null $actualizado_en
     * @return self
     */
    public function setActualizadoEn(?string $actualizado_en): self
    {
        $this->actualizado_en = $actualizado_en;
        return $this;
    }
}