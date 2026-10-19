<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad Cliente
 */
final class Cliente extends BaseModel
{
    /** @var string */
    protected string $nombre = '';

    /** @var string */
    protected string $apellido = '';

    /** @var string */
    protected string $cedula = '';

    /** @var string */
    protected string $correo = '';

    /** @var string|null */
    protected ?string $fecha_registro = null;

    /** @var string|null */
    protected ?string $actualizado_en = null;

    /** @var string */
    protected string $estado = 'activo';

    /** @var string|null */
    protected ?string $segmento = null;

    /** @var int|null */
    protected ?int $direccion_id = null;

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
     * @return string
     */
    public function getApellido(): string
    {
        return $this->apellido;
    }

    /**
     * @param string $apellido
     * @return self
     */
    public function setApellido(string $apellido): self
    {
        $this->apellido = $apellido;
        return $this;
    }

    /**
     * @return string
     */
    public function getCedula(): string
    {
        return $this->cedula;
    }

    /**
     * @param string $cedula
     * @return self
     */
    public function setCedula(string $cedula): self
    {
        $this->cedula = $cedula;
        return $this;
    }

    /**
     * @return string
     */
    public function getCorreo(): string
    {
        return $this->correo;
    }

    /**
     * @param string $correo
     * @return self
     */
    public function setCorreo(string $correo): self
    {
        $this->correo = $correo;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFechaRegistro(): ?string
    {
        return $this->fecha_registro;
    }

    /**
     * @param string|null $fecha_registro
     * @return self
     */
    public function setFechaRegistro(?string $fecha_registro): self
    {
        $this->fecha_registro = $fecha_registro;
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
    public function getSegmento(): ?string
    {
        return $this->segmento;
    }

    /**
     * @param string|null $segmento
     * @return self
     */
    public function setSegmento(?string $segmento): self
    {
        $this->segmento = $segmento;
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
}