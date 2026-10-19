<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad Usuario
 */
final class Usuario extends BaseModel
{
    /** @var string */
    protected string $username = '';

    /** @var string */
    protected string $password = '';

    /** @var string */
    protected string $email = '';

    /** @var bool */
    protected bool $must_change_password = false;

    /** @var bool */
    protected bool $email_verified = false;

    /** @var string */
    protected string $estado = 'Activo';

    /** @var int */
    protected int $intentos_fallidos = 0;

    /** @var string|null */
    protected ?string $bloqueado_hasta = null;

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return self
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return self
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return self
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return bool
     */
    public function getMustChangePassword(): bool
    {
        return $this->must_change_password;
    }

    /**
     * @param bool $must_change_password
     * @return self
     */
    public function setMustChangePassword(bool $must_change_password): self
    {
        $this->must_change_password = $must_change_password;
        return $this;
    }

    /**
     * @return bool
     */
    public function getEmailVerified(): bool
    {
        return $this->email_verified;
    }

    /**
     * @param bool $email_verified
     * @return self
     */
    public function setEmailVerified(bool $email_verified): self
    {
        $this->email_verified = $email_verified;
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
    public function getIntentosFallidos(): int
    {
        return $this->intentos_fallidos;
    }

    /**
     * @param int $intentos_fallidos
     * @return self
     */
    public function setIntentosFallidos(int $intentos_fallidos): self
    {
        $this->intentos_fallidos = $intentos_fallidos;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getBloqueadoHasta(): ?string
    {
        return $this->bloqueado_hasta;
    }

    /**
     * @param string|null $bloqueado_hasta
     * @return self
     */
    public function setBloqueadoHasta(?string $bloqueado_hasta): self
    {
        $this->bloqueado_hasta = $bloqueado_hasta;
        return $this;
    }
}