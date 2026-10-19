<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad Plan
 */
final class Plan extends BaseModel
{
    /** @var string */
    protected string $nombre = '';

    /** @var float|null */
    protected ?float $velocidad_bajada_mbps = null;

    /** @var float|null */
    protected ?float $velocidad_subida_mbps = null;

    /** @var string */
    protected string $tecnologia = 'FTTH';

    /** @var float */
    protected float $precio_mensual = 0.0;

    /** @var string */
    protected string $moneda = 'USD';

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
     * @return float|null
     */
    public function getVelocidadBajadaMbps(): ?float
    {
        return $this->velocidad_bajada_mbps;
    }

    /**
     * @param float|null $velocidad_bajada_mbps
     * @return self
     */
    public function setVelocidadBajadaMbps(?float $velocidad_bajada_mbps): self
    {
        $this->velocidad_bajada_mbps = $velocidad_bajada_mbps;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getVelocidadSubidaMbps(): ?float
    {
        return $this->velocidad_subida_mbps;
    }

    /**
     * @param float|null $velocidad_subida_mbps
     * @return self
     */
    public function setVelocidadSubidaMbps(?float $velocidad_subida_mbps): self
    {
        $this->velocidad_subida_mbps = $velocidad_subida_mbps;
        return $this;
    }

    /**
     * @return string
     */
    public function getTecnologia(): string
    {
        return $this->tecnologia;
    }

    /**
     * @param string $tecnologia
     * @return self
     */
    public function setTecnologia(string $tecnologia): self
    {
        $this->tecnologia = $tecnologia;
        return $this;
    }

    /**
     * @return float
     */
    public function getPrecioMensual(): float
    {
        return $this->precio_mensual;
    }

    /**
     * @param float $precio_mensual
     * @return self
     */
    public function setPrecioMensual(float $precio_mensual): self
    {
        $this->precio_mensual = $precio_mensual;
        return $this;
    }

    /**
     * @return string
     */
    public function getMoneda(): string
    {
        return $this->moneda;
    }

    /**
     * @param string $moneda
     * @return self
     */
    public function setMoneda(string $moneda): self
    {
        $this->moneda = $moneda;
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