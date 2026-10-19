<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad Pago
 */
final class Pago extends BaseModel
{
    /** @var int|null */
    protected ?int $factura_id = null;

    /** @var int */
    protected int $cliente_id = 0;

    /** @var int */
    protected int $servicio_id = 0;

    /** @var float */
    protected float $monto = 0.0;

    /** @var string */
    protected string $moneda_pago = 'USD';

    /** @var float|null */
    protected ?float $tasa_cambio = null;

    /** @var string */
    protected string $fecha_pago = '';

    /** @var int|null */
    protected ?int $metodo_id = null;

    /** @var string|null */
    protected ?string $referencia_bancaria = null;

    /** @var string|null */
    protected ?string $comprobante_path = null;

    /** @var string */
    protected string $estado = 'pendiente';

    /** @var string|null */
    protected ?string $motivo_rechazo = null;

    /** @var string|null */
    protected ?string $fecha_validacion = null;

    /** @var int|null */
    protected ?int $usuario_valido_id = null;

    /** @var string */
    protected string $origen = 'backoffice';

    /** @var string|null */
    protected ?string $creado_en = null;

    /** @var string|null */
    protected ?string $actualizado_en = null;

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
    public function getMonedaPago(): string
    {
        return $this->moneda_pago;
    }

    /**
     * @param string $moneda_pago
     * @return self
     */
    public function setMonedaPago(string $moneda_pago): self
    {
        $this->moneda_pago = $moneda_pago;
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
     * @return string
     */
    public function getFechaPago(): string
    {
        return $this->fecha_pago;
    }

    /**
     * @param string $fecha_pago
     * @return self
     */
    public function setFechaPago(string $fecha_pago): self
    {
        $this->fecha_pago = $fecha_pago;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getMetodoId(): ?int
    {
        return $this->metodo_id;
    }

    /**
     * @param int|null $metodo_id
     * @return self
     */
    public function setMetodoId(?int $metodo_id): self
    {
        $this->metodo_id = $metodo_id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getReferenciaBancaria(): ?string
    {
        return $this->referencia_bancaria;
    }

    /**
     * @param string|null $referencia_bancaria
     * @return self
     */
    public function setReferenciaBancaria(?string $referencia_bancaria): self
    {
        $this->referencia_bancaria = $referencia_bancaria;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getComprobantePath(): ?string
    {
        return $this->comprobante_path;
    }

    /**
     * @param string|null $comprobante_path
     * @return self
     */
    public function setComprobantePath(?string $comprobante_path): self
    {
        $this->comprobante_path = $comprobante_path;
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
    public function getMotivoRechazo(): ?string
    {
        return $this->motivo_rechazo;
    }

    /**
     * @param string|null $motivo_rechazo
     * @return self
     */
    public function setMotivoRechazo(?string $motivo_rechazo): self
    {
        $this->motivo_rechazo = $motivo_rechazo;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFechaValidacion(): ?string
    {
        return $this->fecha_validacion;
    }

    /**
     * @param string|null $fecha_validacion
     * @return self
     */
    public function setFechaValidacion(?string $fecha_validacion): self
    {
        $this->fecha_validacion = $fecha_validacion;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUsuarioValidoId(): ?int
    {
        return $this->usuario_valido_id;
    }

    /**
     * @param int|null $usuario_valido_id
     * @return self
     */
    public function setUsuarioValidoId(?int $usuario_valido_id): self
    {
        $this->usuario_valido_id = $usuario_valido_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrigen(): string
    {
        return $this->origen;
    }

    /**
     * @param string $origen
     * @return self
     */
    public function setOrigen(string $origen): self
    {
        $this->origen = $origen;
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