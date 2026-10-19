<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad Ticket
 */
final class Ticket extends BaseModel
{
    /** @var string */
    protected string $asunto = '';

    /** @var string|null */
    protected ?string $descripcion = null;

    /** @var string */
    protected string $estado = 'Abierto';

    /** @var string */
    protected string $prioridad = 'Media';

    /** @var int|null */
    protected ?int $categoria_id = null;

    /** @var int|null */
    protected ?int $cliente_id = null;

    /** @var int|null */
    protected ?int $servicio_id = null;

    /** @var int|null */
    protected ?int $usuario_creador_id = null;

    /** @var string */
    protected string $origen = 'backoffice';

    /** @var string|null */
    protected ?string $creado_en = null;

    /** @var string|null */
    protected ?string $actualizado_en = null;

    /** @var string|null */
    protected ?string $sla_vence_en = null;

    /**
     * @return string
     */
    public function getAsunto(): string
    {
        return $this->asunto;
    }

    /**
     * @param string $asunto
     * @return self
     */
    public function setAsunto(string $asunto): self
    {
        $this->asunto = $asunto;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    /**
     * @param string|null $descripcion
     * @return self
     */
    public function setDescripcion(?string $descripcion): self
    {
        $this->descripcion = $descripcion;
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
     * @return string
     */
    public function getPrioridad(): string
    {
        return $this->prioridad;
    }

    /**
     * @param string $prioridad
     * @return self
     */
    public function setPrioridad(string $prioridad): self
    {
        $this->prioridad = $prioridad;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getCategoriaId(): ?int
    {
        return $this->categoria_id;
    }

    /**
     * @param int|null $categoria_id
     * @return self
     */
    public function setCategoriaId(?int $categoria_id): self
    {
        $this->categoria_id = $categoria_id;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getClienteId(): ?int
    {
        return $this->cliente_id;
    }

    /**
     * @param int|null $cliente_id
     * @return self
     */
    public function setClienteId(?int $cliente_id): self
    {
        $this->cliente_id = $cliente_id;
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
     * @return int|null
     */
    public function getUsuarioCreadorId(): ?int
    {
        return $this->usuario_creador_id;
    }

    /**
     * @param int|null $usuario_creador_id
     * @return self
     */
    public function setUsuarioCreadorId(?int $usuario_creador_id): self
    {
        $this->usuario_creador_id = $usuario_creador_id;
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

    /**
     * @return string|null
     */
    public function getSlaVenceEn(): ?string
    {
        return $this->sla_vence_en;
    }

    /**
     * @param string|null $sla_vence_en
     * @return self
     */
    public function setSlaVenceEn(?string $sla_vence_en): self
    {
        $this->sla_vence_en = $sla_vence_en;
        return $this;
    }
}