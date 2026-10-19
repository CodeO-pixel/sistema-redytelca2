<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad SlaDefinicion
 */
final class SlaDefinicion extends BaseModel
{
    /** @var int */
    protected int $categoria_id = 0;

    /** @var string */
    protected string $prioridad = 'Media';

    /** @var int */
    protected int $horas_respuesta = 0;

    /** @var int */
    protected int $horas_resolucion = 0;

    /** @var string|null */
    protected ?string $descripcion = null;

    /** @var string|null */
    protected ?string $creado_en = null;

    /**
     * @return int
     */
    public function getCategoriaId(): int
    {
        return $this->categoria_id;
    }

    /**
     * @param int $categoria_id
     * @return self
     */
    public function setCategoriaId(int $categoria_id): self
    {
        $this->categoria_id = $categoria_id;
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
     * @return int
     */
    public function getHorasRespuesta(): int
    {
        return $this->horas_respuesta;
    }

    /**
     * @param int $horas_respuesta
     * @return self
     */
    public function setHorasRespuesta(int $horas_respuesta): self
    {
        $this->horas_respuesta = $horas_respuesta;
        return $this;
    }

    /**
     * @return int
     */
    public function getHorasResolucion(): int
    {
        return $this->horas_resolucion;
    }

    /**
     * @param int $horas_resolucion
     * @return self
     */
    public function setHorasResolucion(int $horas_resolucion): self
    {
        $this->horas_resolucion = $horas_resolucion;
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