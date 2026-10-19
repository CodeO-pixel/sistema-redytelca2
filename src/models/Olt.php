<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad Olt
 */
final class Olt extends BaseModel
{
    /** @var string */
    protected string $marca_modelo = '';

    /** @var int */
    protected int $puertos_pon = 16;

    /** @var string|null */
    protected ?string $ip_gestion = null;

    /** @var int */
    protected int $nodo_id = 0;

    /** @var string|null */
    protected ?string $codigo = null;

    /** @var string|null */
    protected ?string $creado_en = null;

    /** @var string|null */
    protected ?string $actualizado_en = null;

    /**
     * @return string
     */
    public function getMarcaModelo(): string
    {
        return $this->marca_modelo;
    }

    /**
     * @param string $marca_modelo
     * @return self
     */
    public function setMarcaModelo(string $marca_modelo): self
    {
        $this->marca_modelo = $marca_modelo;
        return $this;
    }

    /**
     * @return int
     */
    public function getPuertosPon(): int
    {
        return $this->puertos_pon;
    }

    /**
     * @param int $puertos_pon
     * @return self
     */
    public function setPuertosPon(int $puertos_pon): self
    {
        $this->puertos_pon = $puertos_pon;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getIpGestion(): ?string
    {
        return $this->ip_gestion;
    }

    /**
     * @param string|null $ip_gestion
     * @return self
     */
    public function setIpGestion(?string $ip_gestion): self
    {
        $this->ip_gestion = $ip_gestion;
        return $this;
    }

    /**
     * @return int
     */
    public function getNodoId(): int
    {
        return $this->nodo_id;
    }

    /**
     * @param int $nodo_id
     * @return self
     */
    public function setNodoId(int $nodo_id): self
    {
        $this->nodo_id = $nodo_id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    /**
     * @param string|null $codigo
     * @return self
     */
    public function setCodigo(?string $codigo): self
    {
        $this->codigo = $codigo;
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