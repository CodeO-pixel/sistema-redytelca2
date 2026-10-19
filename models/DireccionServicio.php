<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad DireccionServicio
 */
final class DireccionServicio extends BaseModel
{
    /** @var string|null */
    protected ?string $casa = null;

    /** @var string|null */
    protected ?string $calle = null;

    /** @var string|null */
    protected ?string $avenida = null;

    /** @var float|null */
    protected ?float $latitud = null;

    /** @var float|null */
    protected ?float $longitud = null;

    /** @var string|null */
    protected ?string $creado_en = null;

    /** @var string|null */
    protected ?string $actualizado_en = null;

    /**
     * @return string|null
     */
    public function getCasa(): ?string
    {
        return $this->casa;
    }

    /**
     * @param string|null $casa
     * @return self
     */
    public function setCasa(?string $casa): self
    {
        $this->casa = $casa;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCalle(): ?string
    {
        return $this->calle;
    }

    /**
     * @param string|null $calle
     * @return self
     */
    public function setCalle(?string $calle): self
    {
        $this->calle = $calle;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAvenida(): ?string
    {
        return $this->avenida;
    }

    /**
     * @param string|null $avenida
     * @return self
     */
    public function setAvenida(?string $avenida): self
    {
        $this->avenida = $avenida;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getLatitud(): ?float
    {
        return $this->latitud;
    }

    /**
     * @param float|null $latitud
     * @return self
     */
    public function setLatitud(?float $latitud): self
    {
        $this->latitud = $latitud;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getLongitud(): ?float
    {
        return $this->longitud;
    }

    /**
     * @param float|null $longitud
     * @return self
     */
    public function setLongitud(?float $longitud): self
    {
        $this->longitud = $longitud;
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