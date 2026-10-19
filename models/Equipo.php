<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad Equipo
 */
final class Equipo extends BaseModel
{
    /** @var string */
    protected string $tipo = 'ONU';

    /** @var string|null */
    protected ?string $marca = null;

    /** @var string|null */
    protected ?string $modelo = null;

    /** @var string */
    protected string $direccion_mac = '';

    /** @var string|null */
    protected ?string $serie = null;

    /** @var int */
    protected int $puerto_nap = 0;

    /** @var string */
    protected string $estado_fisico = 'stock';

    /** @var string */
    protected string $estado_inventario = 'stock';

    /** @var string|null */
    protected ?string $fecha_asignacion = null;

    /** @var string|null */
    protected ?string $fecha_devolucion = null;

    /** @var int */
    protected int $nap_id = 0;

    /** @var int|null */
    protected ?int $servicio_id = null;

    /** @var string */
    protected string $propiedad = 'empresa';

    /** @var string|null */
    protected ?string $creado_en = null;

    /** @var string|null */
    protected ?string $actualizado_en = null;

    /**
     * @return string
     */
    public function getTipo(): string
    {
        return $this->tipo;
    }

    /**
     * @param string $tipo
     * @return self
     */
    public function setTipo(string $tipo): self
    {
        $this->tipo = $tipo;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMarca(): ?string
    {
        return $this->marca;
    }

    /**
     * @param string|null $marca
     * @return self
     */
    public function setMarca(?string $marca): self
    {
        $this->marca = $marca;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getModelo(): ?string
    {
        return $this->modelo;
    }

    /**
     * @param string|null $modelo
     * @return self
     */
    public function setModelo(?string $modelo): self
    {
        $this->modelo = $modelo;
        return $this;
    }

    /**
     * @return string
     */
    public function getDireccionMac(): string
    {
        return $this->direccion_mac;
    }

    /**
     * @param string $direccion_mac
     * @return self
     */
    public function setDireccionMac(string $direccion_mac): self
    {
        $this->direccion_mac = $direccion_mac;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSerie(): ?string
    {
        return $this->serie;
    }

    /**
     * @param string|null $serie
     * @return self
     */
    public function setSerie(?string $serie): self
    {
        $this->serie = $serie;
        return $this;
    }

    /**
     * @return int
     */
    public function getPuertoNap(): int
    {
        return $this->puerto_nap;
    }

    /**
     * @param int $puerto_nap
     * @return self
     */
    public function setPuertoNap(int $puerto_nap): self
    {
        $this->puerto_nap = $puerto_nap;
        return $this;
    }

    /**
     * @return string
     */
    public function getEstadoFisico(): string
    {
        return $this->estado_fisico;
    }

    /**
     * @param string $estado_fisico
     * @return self
     */
    public function setEstadoFisico(string $estado_fisico): self
    {
        $this->estado_fisico = $estado_fisico;
        return $this;
    }

    /**
     * @return string
     */
    public function getEstadoInventario(): string
    {
        return $this->estado_inventario;
    }

    /**
     * @param string $estado_inventario
     * @return self
     */
    public function setEstadoInventario(string $estado_inventario): self
    {
        $this->estado_inventario = $estado_inventario;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFechaAsignacion(): ?string
    {
        return $this->fecha_asignacion;
    }

    /**
     * @param string|null $fecha_asignacion
     * @return self
     */
    public function setFechaAsignacion(?string $fecha_asignacion): self
    {
        $this->fecha_asignacion = $fecha_asignacion;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFechaDevolucion(): ?string
    {
        return $this->fecha_devolucion;
    }

    /**
     * @param string|null $fecha_devolucion
     * @return self
     */
    public function setFechaDevolucion(?string $fecha_devolucion): self
    {
        $this->fecha_devolucion = $fecha_devolucion;
        return $this;
    }

    /**
     * @return int
     */
    public function getNapId(): int
    {
        return $this->nap_id;
    }

    /**
     * @param int $nap_id
     * @return self
     */
    public function setNapId(int $nap_id): self
    {
        $this->nap_id = $nap_id;
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
    public function getPropiedad(): string
    {
        return $this->propiedad;
    }

    /**
     * @param string $propiedad
     * @return self
     */
    public function setPropiedad(string $propiedad): self
    {
        $this->propiedad = $propiedad;
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