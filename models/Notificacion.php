<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad Notificacion
 */
final class Notificacion extends BaseModel
{
    /** @var string */
    protected string $titulo = '';

    /** @var string */
    protected string $mensaje = '';

    /** @var string|null */
    protected ?string $fecha_envio = null;

    /** @var string */
    protected string $estado = 'no leido';

    /** @var int */
    protected int $cliente_id = 0;

    /** @var int|null */
    protected ?int $usuario_id = null;

    /** @var string|null */
    protected ?string $tipo_evento = null;

    /** @var string|null */
    protected ?string $url_destino = null;

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
    public function getFechaEnvio(): ?string
    {
        return $this->fecha_envio;
    }

    /**
     * @param string|null $fecha_envio
     * @return self
     */
    public function setFechaEnvio(?string $fecha_envio): self
    {
        $this->fecha_envio = $fecha_envio;
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
    public function getUsuarioId(): ?int
    {
        return $this->usuario_id;
    }

    /**
     * @param int|null $usuario_id
     * @return self
     */
    public function setUsuarioId(?int $usuario_id): self
    {
        $this->usuario_id = $usuario_id;
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