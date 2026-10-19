<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad FacturaLinea
 */
final class FacturaLinea extends BaseModel
{
    /** @var int */
    protected int $factura_id = 0;

    /** @var int|null */
    protected ?int $servicio_id = null;

    /** @var string */
    protected string $concepto = '';

    /** @var float */
    protected float $cantidad = 1.0;

    /** @var float */
    protected float $precio_unitario = 0.0;

    /** @var float */
    protected float $monto_linea = 0.0;

    /** @var float */
    protected float $impuesto_porcentaje = 0.0;

    /** @var float */
    protected float $impuesto_monto = 0.0;

    /** @var float */
    protected float $descuento_monto = 0.0;

    /** @var float */
    protected float $total_linea = 0.0;

    /** @var string|null */
    protected ?string $creado_en = null;

    /**
     * @return int
     */
    public function getFacturaId(): int
    {
        return $this->factura_id;
    }

    /**
     * @param int $factura_id
     * @return self
     */
    public function setFacturaId(int $factura_id): self
    {
        $this->factura_id = $factura_id;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getServicioId(): ?int
    {
        return $this->servicio_id;
    }

    /**
     * @param int|null $servicio_id
     * @return self
     */
    public function setServicioId(?int $servicio_id): self
    {
        $this->servicio_id = $servicio_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getConcepto(): string
    {
        return $this->concepto;
    }

    /**
     * @param string $concepto
     * @return self
     */
    public function setConcepto(string $concepto): self
    {
        $this->concepto = $concepto;
        return $this;
    }

    /**
     * @return float
     */
    public function getCantidad(): float
    {
        return $this->cantidad;
    }

    /**
     * @param float $cantidad
     * @return self
     */
    public function setCantidad(float $cantidad): self
    {
        $this->cantidad = $cantidad;
        return $this;
    }

    /**
     * @return float
     */
    public function getPrecioUnitario(): float
    {
        return $this->precio_unitario;
    }

    /**
     * @param float $precio_unitario
     * @return self
     */
    public function setPrecioUnitario(float $precio_unitario): self
    {
        $this->precio_unitario = $precio_unitario;
        return $this;
    }

    /**
     * @return float
     */
    public function getMontoLinea(): float
    {
        return $this->monto_linea;
    }

    /**
     * @param float $monto_linea
     * @return self
     */
    public function setMontoLinea(float $monto_linea): self
    {
        $this->monto_linea = $monto_linea;
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
     * @return float
     */
    public function getImpuestoMonto(): float
    {
        return $this->impuesto_monto;
    }

    /**
     * @param float $impuesto_monto
     * @return self
     */
    public function setImpuestoMonto(float $impuesto_monto): self
    {
        $this->impuesto_monto = $impuesto_monto;
        return $this;
    }

    /**
     * @return float
     */
    public function getDescuentoMonto(): float
    {
        return $this->descuento_monto;
    }

    /**
     * @param float $descuento_monto
     * @return self
     */
    public function setDescuentoMonto(float $descuento_monto): self
    {
        $this->descuento_monto = $descuento_monto;
        return $this;
    }

    /**
     * @return float
     */
    public function getTotalLinea(): float
    {
        return $this->total_linea;
    }

    /**
     * @param float $total_linea
     * @return self
     */
    public function setTotalLinea(float $total_linea): self
    {
        $this->total_linea = $total_linea;
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