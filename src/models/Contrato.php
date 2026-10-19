<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad Contrato
 */
final class Contrato extends BaseModel
{
    /** @var int */
    protected int $servicio_id = 0;

    /** @var string */
    protected string $fecha_inicio = '';

    /** @var string|null */
    protected ?string $fecha_fin = null;

    /** @var string */
    protected string $tipo_contrato = 'indefinido';

    /** @var string */
    protected string $estado = 'vigente';

    /** @var string|null */
    protected ?string $observaciones = null;

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
    public function getFechaInicio(): string
    {
        return $this->fecha_inicio;
    }

    /**
     * @param string $fecha_inicio
     * @return self
     */
    public function setFechaInicio(string $fecha_inicio): self
    {
        $this->fecha_inicio = $fecha_inicio;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFechaFin(): ?string
    {
        return $this->fecha_fin;
    }

    /**
     * @param string|null $fecha_fin
     * @return self
     */
    public function setFechaFin(?string $fecha_fin): self
    {
        $this->fecha_fin = $fecha_fin;
        return $this;
    }

    /**
     * @return string
     */
    public function getTipoContrato(): string
    {
        return $this->tipo_contrato;
    }

    /**
     * @param string $tipo_contrato
     * @return self
     */
    public function setTipoContrato(string $tipo_contrato): self
    {
        $this->tipo_contrato = $tipo_contrato;
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
    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    /**
     * @param string|null $observaciones
     * @return self
     */
    public function setObservaciones(?string $observaciones): self
    {
        $this->observaciones = $observaciones;
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