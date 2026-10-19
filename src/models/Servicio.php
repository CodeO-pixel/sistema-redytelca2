<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad Servicio
 */
final class Servicio extends BaseModel
{
    /** @var int */
    protected int $cliente_id = 0;

    /** @var string|null */
    protected ?string $alias = null;

    /** @var string */
    protected string $estado_comercial = 'pendiente';

    /** @var int */
    protected int $plan_id = 0;

    /** @var int|null */
    protected ?int $nap_id = null;

    /** @var int|null */
    protected ?int $direccion_id = null;

    /** @var string|null */
    protected ?string $creado_en = null;

    /** @var string|null */
    protected ?string $actualizado_en = null;

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
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @param string|null $alias
     * @return self
     */
    public function setAlias(?string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * @return string
     */
    public function getEstadoComercial(): string
    {
        return $this->estado_comercial;
    }

    /**
     * @param string $estado_comercial
     * @return self
     */
    public function setEstadoComercial(string $estado_comercial): self
    {
        $this->estado_comercial = $estado_comercial;
        return $this;
    }

    /**
     * @return int
     */
    public function getPlanId(): int
    {
        return $this->plan_id;
    }

    /**
     * @param int $plan_id
     * @return self
     */
    public function setPlanId(int $plan_id): self
    {
        $this->plan_id = $plan_id;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getNapId(): ?int
    {
        return $this->nap_id;
    }

    /**
     * @param int|null $nap_id
     * @return self
     */
    public function setNapId(?int $nap_id): self
    {
        $this->nap_id = $nap_id;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getDireccionId(): ?int
    {
        return $this->direccion_id;
    }

    /**
     * @param int|null $direccion_id
     * @return self
     */
    public function setDireccionId(?int $direccion_id): self
    {
        $this->direccion_id = $direccion_id;
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