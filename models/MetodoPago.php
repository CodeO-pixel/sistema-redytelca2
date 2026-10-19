<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad MetodoPago
 */
final class MetodoPago extends BaseModel
{
    /** @var string */
    protected string $codigo = '';

    /** @var string */
    protected string $nombre = '';

    /** @var bool */
    protected bool $requiere_referencia = false;

    /** @var bool */
    protected bool $activo = true;

    /** @var int */
    protected int $orden = 0;

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
     * @return bool
     */
    public function getRequiereReferencia(): bool
    {
        return $this->requiere_referencia;
    }

    /**
     * @param bool $requiere_referencia
     * @return self
     */
    public function setRequiereReferencia(bool $requiere_referencia): self
    {
        $this->requiere_referencia = $requiere_referencia;
        return $this;
    }

    /**
     * @return bool
     */
    public function getActivo(): bool
    {
        return $this->activo;
    }

    /**
     * @param bool $activo
     * @return self
     */
    public function setActivo(bool $activo): self
    {
        $this->activo = $activo;
        return $this;
    }

    /**
     * @return int
     */
    public function getOrden(): int
    {
        return $this->orden;
    }

    /**
     * @param int $orden
     * @return self
     */
    public function setOrden(int $orden): self
    {
        $this->orden = $orden;
        return $this;
    }
}