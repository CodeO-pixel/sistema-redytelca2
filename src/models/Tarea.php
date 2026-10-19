<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad Tarea
 */
final class Tarea extends BaseModel
{
    /** @var string */
    protected string $titulo = '';

    /** @var string|null */
    protected ?string $descripcion = null;

    /** @var string */
    protected string $estado = 'Pendiente';

    /** @var string */
    protected string $prioridad = 'Media';

    /** @var int|null */
    protected ?int $cliente_id = null;

    /** @var int|null */
    protected ?int $ticket_id = null;

    /** @var int|null */
    protected ?int $servicio_id = null;

    /** @var int|null */
    protected ?int $usuario_creador_id = null;

    /** @var string|null */
    protected ?string $creado_en = null;

    /** @var string|null */
    protected ?string $actualizado_en = null;

    /**
     * @return string
     */
    public function getTitulo(): string
    {
        return $this->titulo;
    }

    /**
     * @param string $titulo
     * @return self
     */
    public function setTitulo(string $titulo): self
    {
        $this->titulo = $titulo;
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
    public function getTicketId(): ?int
    {
        return $this->ticket_id;
    }

    /**
     * @param int|null $ticket_id
     * @return self
     */
    public function setTicketId(?int $ticket_id): self
    {
        $this->ticket_id = $ticket_id;
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