<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad Nap
 */
final class Nap extends BaseModel
{
    /** @var string */
    protected string $codigo = '';

    /** @var int */
    protected int $cantidad_puertos_max = 16;

    /** @var string|null */
    protected ?string $ubicacion_fisica = null;

    /** @var float */
    protected float $latitud = 0.0;

    /** @var float */
    protected float $longitud = 0.0;

    /** @var int */
    protected int $olt_id = 0;

    /** @var string|null */
    protected ?string $creado_en = null;

    /** @var string|null */
    protected ?string $actualizado_en = null;

    /**
     * @return string
     */
    public function getCodigo(): string
    {
        return $this->codigo;
    }

    /**
     * @param string $codigo
     * @return self
     */
    public function setCodigo(string $codigo): self
    {
        $this->codigo = $codigo;
        return $this;
    }

    /**
     * @return int
     */
    public function getCantidadPuertosMax(): int
    {
        return $this->cantidad_puertos_max;
    }

    /**
     * @param int $cantidad_puertos_max
     * @return self
     */
    public function setCantidadPuertosMax(int $cantidad_puertos_max): self
    {
        $this->cantidad_puertos_max = $cantidad_puertos_max;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUbicacionFisica(): ?string
    {
        return $this->ubicacion_fisica;
    }

    /**
     * @param string|null $ubicacion_fisica
     * @return self
     */
    public function setUbicacionFisica(?string $ubicacion_fisica): self
    {
        $this->ubicacion_fisica = $ubicacion_fisica;
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
     * @return int
     */
    public function getOltId(): int
    {
        return $this->olt_id;
    }

    /**
     * @param int $olt_id
     * @return self
     */
    public function setOltId(int $olt_id): self
    {
        $this->olt_id = $olt_id;
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