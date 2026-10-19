<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad NotaDebito
 */
final class NotaDebito extends BaseModel
{
    /** @var int */
    protected int $cliente_id = 0;

    /** @var int|null */
    protected ?int $factura_id = null;

    /** @var float */
    protected float $monto = 0.0;

    /** @var string */
    protected string $moneda = 'USD';

    /** @var string */
    protected string $motivo = '';

    /** @var string */
    protected string $estado = 'emitida';

    /** @var int|null */
    protected ?int $usuario_emisor_id = null;

    /** @var string|null */
    protected ?string $emitida_en = null;

    /** @var string|null */
    protected ?string $aplicada_en = null;

    /**
     * @return int
     */
    public function getClienteId(): int
    {
        return $this->cliente_id;
    }

    /**
     * @param int $cliente_id
     * @return self
     */
    public function setClienteId(int $cliente_id): self
    {
        $this->cliente_id = $cliente_id;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getFacturaId(): ?int
    {
        return $this->factura_id;
    }

    /**
     * @param int|null $factura_id
     * @return self
     */
    public function setFacturaId(?int $factura_id): self
    {
        $this->factura_id = $factura_id;
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
     * @return string
     */
    public function getMotivo(): string
    {
        return $this->motivo;
    }

    /**
     * @param string $motivo
     * @return self
     */
    public function setMotivo(string $motivo): self
    {
        $this->motivo = $motivo;
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
     * @return int|null
     */
    public function getUsuarioEmisorId(): ?int
    {
        return $this->usuario_emisor_id;
    }

    /**
     * @param int|null $usuario_emisor_id
     * @return self
     */
    public function setUsuarioEmisorId(?int $usuario_emisor_id): self
    {
        $this->usuario_emisor_id = $usuario_emisor_id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmitidaEn(): ?string
    {
        return $this->emitida_en;
    }

    /**
     * @param string|null $emitida_en
     * @return self
     */
    public function setEmitidaEn(?string $emitida_en): self
    {
        $this->emitida_en = $emitida_en;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAplicadaEn(): ?string
    {
        return $this->aplicada_en;
    }

    /**
     * @param string|null $aplicada_en
     * @return self
     */
    public function setAplicadaEn(?string $aplicada_en): self
    {
        $this->aplicada_en = $aplicada_en;
        return $this;
    }
}