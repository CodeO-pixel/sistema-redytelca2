<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad ClienteCredencial
 */
final class ClienteCredencial extends BaseModel
{
    /** @var int */
    protected int $cliente_id = 0;

    /** @var string */
    protected string $username = '';

    /** @var string */
    protected string $password = '';

    /** @var string|null */
    protected ?string $correo_recuperacion = null;

    /** @var string */
    protected string $estado = 'activa';

    /** @var int */
    protected int $intentos_fallidos = 0;

    /** @var string|null */
    protected ?string $bloqueado_hasta = null;

    /** @var bool */
    protected bool $must_change_password = true;

    /** @var string|null */
    protected ?string $creado_en = null;

    /** @var string|null */
    protected ?string $ultimo_acceso = null;

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
     * @return string|null
     */
    public function getCorreoRecuperacion(): ?string
    {
        return $this->correo_recuperacion;
    }

    /**
     * @param string|null $correo_recuperacion
     * @return self
     */
    public function setCorreoRecuperacion(?string $correo_recuperacion): self
    {
        $this->correo_recuperacion = $correo_recuperacion;
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
    public function getUltimoAcceso(): ?string
    {
        return $this->ultimo_acceso;
    }

    /**
     * @param string|null $ultimo_acceso
     * @return self
     */
    public function setUltimoAcceso(?string $ultimo_acceso): self
    {
        $this->ultimo_acceso = $ultimo_acceso;
        return $this;
    }
}