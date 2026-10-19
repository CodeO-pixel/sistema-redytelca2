<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad Pedido
 */
final class Pedido extends BaseModel
{
    /** @var int */
    protected int $cliente_id = 0;

    /** @var int|null */
    protected ?int $servicio_id = null;

    /** @var string */
    protected string $tipo = 'instalacion';

    /** @var string */
    protected string $estado = 'pendiente';

    /** @var string */
    protected string $prioridad = 'Media';

    /** @var string|null */
    protected ?string $descripcion = null;

    /** @var int|null */
    protected ?int $usuario_creador_id = null;

    /** @var int|null */
    protected ?int $tecnico_asignado_id = null;

    /** @var string|null */
    protected ?string $fecha_solicitud = null;

    /** @var string|null */
    protected ?string $fecha_programada = null;

    /** @var string|null */
    protected ?string $fecha_inicio = null;

    /** @var string|null */
    protected ?string $fecha_completado = null;

    /** @var string|null */
    protected ?string $observaciones_cierre = null;

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
     * @return int|null
     */
    public function getTecnicoAsignadoId(): ?int
    {
        return $this->tecnico_asignado_id;
    }

    /**
     * @param int|null $tecnico_asignado_id
     * @return self
     */
    public function setTecnicoAsignadoId(?int $tecnico_asignado_id): self
    {
        $this->tecnico_asignado_id = $tecnico_asignado_id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFechaSolicitud(): ?string
    {
        return $this->fecha_solicitud;
    }

    /**
     * @param string|null $fecha_solicitud
     * @return self
     */
    public function setFechaSolicitud(?string $fecha_solicitud): self
    {
        $this->fecha_solicitud = $fecha_solicitud;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFechaProgramada(): ?string
    {
        return $this->fecha_programada;
    }

    /**
     * @param string|null $fecha_programada
     * @return self
     */
    public function setFechaProgramada(?string $fecha_programada): self
    {
        $this->fecha_programada = $fecha_programada;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFechaInicio(): ?string
    {
        return $this->fecha_inicio;
    }

    /**
     * @param string|null $fecha_inicio
     * @return self
     */
    public function setFechaInicio(?string $fecha_inicio): self
    {
        $this->fecha_inicio = $fecha_inicio;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFechaCompletado(): ?string
    {
        return $this->fecha_completado;
    }

    /**
     * @param string|null $fecha_completado
     * @return self
     */
    public function setFechaCompletado(?string $fecha_completado): self
    {
        $this->fecha_completado = $fecha_completado;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getObservacionesCierre(): ?string
    {
        return $this->observaciones_cierre;
    }

    /**
     * @param string|null $observaciones_cierre
     * @return self
     */
    public function setObservacionesCierre(?string $observaciones_cierre): self
    {
        $this->observaciones_cierre = $observaciones_cierre;
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