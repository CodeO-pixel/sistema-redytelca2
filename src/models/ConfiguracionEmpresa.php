<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad ConfiguracionEmpresa
 */
final class ConfiguracionEmpresa extends BaseModel
{
    /** @var string */
    protected string $nombre_empresa = '';

    /** @var string|null */
    protected ?string $logo_path = null;

    /** @var string|null */
    protected ?string $color_primario = '#104aa8';

    /** @var float */
    protected float $impuesto_porcentaje = 0.0;

    /** @var int */
    protected int $dia_facturacion = 1;

    /** @var int */
    protected int $dias_gracia_mora = 5;

    /** @var float */
    protected float $monto_recargo_mora = 0.0;

    /** @var int */
    protected int $dias_corte_automatico = 10;

    /** @var string|null */
    protected ?string $creado_en = null;

    /** @var string|null */
    protected ?string $actualizado_en = null;

    /**
     * @return string
     */
    public function getNombreEmpresa(): string
    {
        return $this->nombre_empresa;
    }

    /**
     * @param string $nombre_empresa
     * @return self
     */
    public function setNombreEmpresa(string $nombre_empresa): self
    {
        $this->nombre_empresa = $nombre_empresa;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLogoPath(): ?string
    {
        return $this->logo_path;
    }

    /**
     * @param string|null $logo_path
     * @return self
     */
    public function setLogoPath(?string $logo_path): self
    {
        $this->logo_path = $logo_path;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getColorPrimario(): ?string
    {
        return $this->color_primario;
    }

    /**
     * @param string|null $color_primario
     * @return self
     */
    public function setColorPrimario(?string $color_primario): self
    {
        $this->color_primario = $color_primario;
        return $this;
    }

    /**
     * @return float
     */
    public function getImpuestoPorcentaje(): float
    {
        return $this->impuesto_porcentaje;
    }

    /**
     * @param float $impuesto_porcentaje
     * @return self
     */
    public function setImpuestoPorcentaje(float $impuesto_porcentaje): self
    {
        $this->impuesto_porcentaje = $impuesto_porcentaje;
        return $this;
    }

    /**
     * @return int
     */
    public function getDiaFacturacion(): int
    {
        return $this->dia_facturacion;
    }

    /**
     * @param int $dia_facturacion
     * @return self
     */
    public function setDiaFacturacion(int $dia_facturacion): self
    {
        $this->dia_facturacion = $dia_facturacion;
        return $this;
    }

    /**
     * @return int
     */
    public function getDiasGraciaMora(): int
    {
        return $this->dias_gracia_mora;
    }

    /**
     * @param int $dias_gracia_mora
     * @return self
     */
    public function setDiasGraciaMora(int $dias_gracia_mora): self
    {
        $this->dias_gracia_mora = $dias_gracia_mora;
        return $this;
    }

    /**
     * @return float
     */
    public function getMontoRecargoMora(): float
    {
        return $this->monto_recargo_mora;
    }

    /**
     * @param float $monto_recargo_mora
     * @return self
     */
    public function setMontoRecargoMora(float $monto_recargo_mora): self
    {
        $this->monto_recargo_mora = $monto_recargo_mora;
        return $this;
    }

    /**
     * @return int
     */
    public function getDiasCorteAutomatico(): int
    {
        return $this->dias_corte_automatico;
    }

    /**
     * @param int $dias_corte_automatico
     * @return self
     */
    public function setDiasCorteAutomatico(int $dias_corte_automatico): self
    {
        $this->dias_corte_automatico = $dias_corte_automatico;
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