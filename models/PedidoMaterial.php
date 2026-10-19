<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad PedidoMaterial
 */
final class PedidoMaterial extends BaseModel
{
    /** @var int */
    protected int $pedido_id = 0;

    /** @var int|null */
    protected ?int $equipo_id = null;

    /** @var string */
    protected string $descripcion = '';

    /** @var float */
    protected float $cantidad = 1.0;

    /** @var string */
    protected string $unidad = 'unidad';

    /** @var string|null */
    protected ?string $notas = null;

    /** @var string|null */
    protected ?string $creado_en = null;

    /**
     * @return int
     */
    public function getPedidoId(): int
    {
        return $this->pedido_id;
    }

    /**
     * @param int $pedido_id
     * @return self
     */
    public function setPedidoId(int $pedido_id): self
    {
        $this->pedido_id = $pedido_id;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getEquipoId(): ?int
    {
        return $this->equipo_id;
    }

    /**
     * @param int|null $equipo_id
     * @return self
     */
    public function setEquipoId(?int $equipo_id): self
    {
        $this->equipo_id = $equipo_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    /**
     * @param string $descripcion
     * @return self
     */
    public function setDescripcion(string $descripcion): self
    {
        $this->descripcion = $descripcion;
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
     * @return string
     */
    public function getUnidad(): string
    {
        return $this->unidad;
    }

    /**
     * @param string $unidad
     * @return self
     */
    public function setUnidad(string $unidad): self
    {
        $this->unidad = $unidad;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getNotas(): ?string
    {
        return $this->notas;
    }

    /**
     * @param string|null $notas
     * @return self
     */
    public function setNotas(?string $notas): self
    {
        $this->notas = $notas;
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