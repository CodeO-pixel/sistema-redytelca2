<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad PedidoAsignacion
 */
final class PedidoAsignacion extends BaseModel
{
    /** @var int */
    protected int $pedido_id = 0;

    /** @var int */
    protected int $tecnico_id = 0;

    /** @var int|null */
    protected ?int $usuario_asignador_id = null;

    /** @var string */
    protected string $estado = 'activa';

    /** @var string|null */
    protected ?string $motivo_rechazo = null;

    /** @var string|null */
    protected ?string $asignado_en = null;

    /** @var string|null */
    protected ?string $cerrado_en = null;

    /**
     * @return int
     */
    public function getPedidoId(): int
    {
        return $this->pedido_id;
    }

    /**
     * @param int $pedido_id
     * @return self
     */
    public function setPedidoId(int $pedido_id): self
    {
        $this->pedido_id = $pedido_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getTecnicoId(): int
    {
        return $this->tecnico_id;
    }

    /**
     * @param int $tecnico_id
     * @return self
     */
    public function setTecnicoId(int $tecnico_id): self
    {
        $this->tecnico_id = $tecnico_id;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUsuarioAsignadorId(): ?int
    {
        return $this->usuario_asignador_id;
    }

    /**
     * @param int|null $usuario_asignador_id
     * @return self
     */
    public function setUsuarioAsignadorId(?int $usuario_asignador_id): self
    {
        $this->usuario_asignador_id = $usuario_asignador_id;
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
    public function getMotivoRechazo(): ?string
    {
        return $this->motivo_rechazo;
    }

    /**
     * @param string|null $motivo_rechazo
     * @return self
     */
    public function setMotivoRechazo(?string $motivo_rechazo): self
    {
        $this->motivo_rechazo = $motivo_rechazo;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAsignadoEn(): ?string
    {
        return $this->asignado_en;
    }

    /**
     * @param string|null $asignado_en
     * @return self
     */
    public function setAsignadoEn(?string $asignado_en): self
    {
        $this->asignado_en = $asignado_en;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCerradoEn(): ?string
    {
        return $this->cerrado_en;
    }

    /**
     * @param string|null $cerrado_en
     * @return self
     */
    public function setCerradoEn(?string $cerrado_en): self
    {
        $this->cerrado_en = $cerrado_en;
        return $this;
    }
}