<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad NotificacionStaff
 */
final class NotificacionStaff extends BaseModel
{
    /** @var string */
    protected string $titulo = '';

    /** @var string */
    protected string $mensaje = '';

    /** @var string|null */
    protected ?string $tipo_evento = null;

    /** @var string|null */
    protected ?string $url_destino = null;

    /** @var string */
    protected string $estado = 'no leido';

    /** @var int */
    protected int $usuario_id = 0;

    /** @var string|null */
    protected ?string $creado_en = null;

    /** @var string|null */
    protected ?string $leida_en = null;

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
     * @return string
     */
    public function getMensaje(): string
    {
        return $this->mensaje;
    }

    /**
     * @param string $mensaje
     * @return self
     */
    public function setMensaje(string $mensaje): self
    {
        $this->mensaje = $mensaje;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTipoEvento(): ?string
    {
        return $this->tipo_evento;
    }

    /**
     * @param string|null $tipo_evento
     * @return self
     */
    public function setTipoEvento(?string $tipo_evento): self
    {
        $this->tipo_evento = $tipo_evento;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUrlDestino(): ?string
    {
        return $this->url_destino;
    }

    /**
     * @param string|null $url_destino
     * @return self
     */
    public function setUrlDestino(?string $url_destino): self
    {
        $this->url_destino = $url_destino;
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
     * @return int
     */
    public function getUsuarioId(): int
    {
        return $this->usuario_id;
    }

    /**
     * @param int $usuario_id
     * @return self
     */
    public function setUsuarioId(int $usuario_id): self
    {
        $this->usuario_id = $usuario_id;
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
    public function getLeidaEn(): ?string
    {
        return $this->leida_en;
    }

    /**
     * @param string|null $leida_en
     * @return self
     */
    public function setLeidaEn(?string $leida_en): self
    {
        $this->leida_en = $leida_en;
        return $this;
    }
}