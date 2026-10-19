<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad TicketRespuesta
 */
final class TicketRespuesta extends BaseModel
{
    /** @var int */
    protected int $ticket_id = 0;

    /** @var string */
    protected string $tipo_autor = 'staff';

    /** @var string */
    protected string $mensaje = '';

    /** @var string|null */
    protected ?string $adjunto_path = null;

    /** @var int|null */
    protected ?int $usuario_id = null;

    /** @var int|null */
    protected ?int $credencial_id = null;

    /** @var string|null */
    protected ?string $creado_en = null;

    /**
     * @return int
     */
    public function getTicketId(): int
    {
        return $this->ticket_id;
    }

    /**
     * @param int $ticket_id
     * @return self
     */
    public function setTicketId(int $ticket_id): self
    {
        $this->ticket_id = $ticket_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getTipoAutor(): string
    {
        return $this->tipo_autor;
    }

    /**
     * @param string $tipo_autor
     * @return self
     */
    public function setTipoAutor(string $tipo_autor): self
    {
        $this->tipo_autor = $tipo_autor;
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
    public function getAdjuntoPath(): ?string
    {
        return $this->adjunto_path;
    }

    /**
     * @param string|null $adjunto_path
     * @return self
     */
    public function setAdjuntoPath(?string $adjunto_path): self
    {
        $this->adjunto_path = $adjunto_path;
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
     * @return int|null
     */
    public function getCredencialId(): ?int
    {
        return $this->credencial_id;
    }

    /**
     * @param int|null $credencial_id
     * @return self
     */
    public function setCredencialId(?int $credencial_id): self
    {
        $this->credencial_id = $credencial_id;
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