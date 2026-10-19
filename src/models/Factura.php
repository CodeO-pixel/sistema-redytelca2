<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad Factura
 */
final class Factura extends BaseModel
{
    /** @var int */
    protected int $servicio_id = 0;

    /** @var string */
    protected string $periodo = '';

    /** @var float */
    protected float $monto = 0.0;

    /** @var string */
    protected string $moneda = 'USD';

    /** @var float|null */
    protected ?float $tasa_cambio = null;

    /** @var float */
    protected float $impuesto_aplicado = 0.0;

    /** @var string */
    protected string $fecha_emision = '';

    /** @var string */
    protected string $fecha_vencimiento = '';

    /** @var string */
    protected string $estado = 'pendiente';

    /** @var string|null */
    protected ?string $creado_en = null;

    /** @var string|null */
    protected ?string $actualizado_en = null;

    /**
     * @return int
     */
    public function getServicioId(): int
    {
        return $this->servicio_id;
    }

    /**
     * @param int $servicio_id
     * @return self
     */
    public function setServicioId(int $servicio_id): self
    {
        $this->servicio_id = $servicio_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getPeriodo(): string
    {
        return $this->periodo;
    }

    /**
     * @param string $periodo
     * @return self
     */
    public function setPeriodo(string $periodo): self
    {
        $this->periodo = $periodo;
        return $this;
    }

    /**
     * @return float
     */
    public function getMonto(): float
    {
        return $this->monto;
    }

    /**
     * @param float $monto
     * @return self
     */
    public function setMonto(float $monto): self
    {
        $this->monto = $monto;
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
     * @return float|null
     */
    public function getTasaCambio(): ?float
    {
        return $this->tasa_cambio;
    }

    /**
     * @param float|null $tasa_cambio
     * @return self
     */
    public function setTasaCambio(?float $tasa_cambio): self
    {
        $this->tasa_cambio = $tasa_cambio;
        return $this;
    }

    /**
     * @return float
     */
    public function getImpuestoAplicado(): float
    {
        return $this->impuesto_aplicado;
    }

    /**
     * @param float $impuesto_aplicado
     * @return self
     */
    public function setImpuestoAplicado(float $impuesto_aplicado): self
    {
        $this->impuesto_aplicado = $impuesto_aplicado;
        return $this;
    }

    /**
     * @return string
     */
    public function getFechaEmision(): string
    {
        return $this->fecha_emision;
    }

    /**
     * @param string $fecha_emision
     * @return self
     */
    public function setFechaEmision(string $fecha_emision): self
    {
        $this->fecha_emision = $fecha_emision;
        return $this;
    }

    /**
     * @return string
     */
    public function getFechaVencimiento(): string
    {
        return $this->fecha_vencimiento;
    }

    /**
     * @param string $fecha_vencimiento
     * @return self
     */
    public function setFechaVencimiento(string $fecha_vencimiento): self
    {
        $this->fecha_vencimiento = $fecha_vencimiento;
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