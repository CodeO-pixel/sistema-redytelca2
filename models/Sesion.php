<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad Sesion
 */
final class Sesion extends BaseModel
{
    /** @var string */
    protected string $token = '';

    /** @var string */
    protected string $tipo_usuario = 'staff';

    /** @var int|null */
    protected ?int $usuario_id = null;

    /** @var int|null */
    protected ?int $credencial_id = null;

    /** @var string|null */
    protected ?string $ip_address = null;

    /** @var string|null */
    protected ?string $user_agent = null;

    /** @var string */
    protected string $creado_en = '';

    /** @var string */
    protected string $expira_en = '';

    /** @var string|null */
    protected ?string $revocado_en = null;

    /** @var string|null */
    protected ?string $motivo_cierre = null;

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return self
     */
    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return string
     */
    public function getTipoUsuario(): string
    {
        return $this->tipo_usuario;
    }

    /**
     * @param string $tipo_usuario
     * @return self
     */
    public function setTipoUsuario(string $tipo_usuario): self
    {
        $this->tipo_usuario = $tipo_usuario;
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
    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    /**
     * @param string|null $ip_address
     * @return self
     */
    public function setIpAddress(?string $ip_address): self
    {
        $this->ip_address = $ip_address;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }

    /**
     * @param string|null $user_agent
     * @return self
     */
    public function setUserAgent(?string $user_agent): self
    {
        $this->user_agent = $user_agent;
        return $this;
    }

    /**
     * @return string
     */
    public function getCreadoEn(): string
    {
        return $this->creado_en;
    }

    /**
     * @param string $creado_en
     * @return self
     */
    public function setCreadoEn(string $creado_en): self
    {
        $this->creado_en = $creado_en;
        return $this;
    }

    /**
     * @return string
     */
    public function getExpiraEn(): string
    {
        return $this->expira_en;
    }

    /**
     * @param string $expira_en
     * @return self
     */
    public function setExpiraEn(string $expira_en): self
    {
        $this->expira_en = $expira_en;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRevocadoEn(): ?string
    {
        return $this->revocado_en;
    }

    /**
     * @param string|null $revocado_en
     * @return self
     */
    public function setRevocadoEn(?string $revocado_en): self
    {
        $this->revocado_en = $revocado_en;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMotivoCierre(): ?string
    {
        return $this->motivo_cierre;
    }

    /**
     * @param string|null $motivo_cierre
     * @return self
     */
    public function setMotivoCierre(?string $motivo_cierre): self
    {
        $this->motivo_cierre = $motivo_cierre;
        return $this;
    }
}