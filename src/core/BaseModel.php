<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Clase base para las entidades del dominio.
 */
abstract class BaseModel
{
    /**
     * @var int|null Identificador único de la entidad.
     */
    protected ?int $id = null;

    /**
     * Constructor de la entidad.
     *
     * @param array $data Datos iniciales para poblar la entidad.
     */
    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->fromArray($data);
        }
    }

    /**
     * Obtiene el identificador de la entidad.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Asigna el identificador de la entidad.
     *
     * @param int|null $id
     * @return self
     */
    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Pobla las propiedades de la entidad desde un array asociativo.
     *
     * @param array $data
     * @return self
     */
    public function fromArray(array $data): self
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        return $this;
    }

    /**
     * Convierte la entidad a un array asociativo.
     * Las subclases pueden sobrescribir este método para exponer
     * solo un subconjunto de propiedades.
     *
     * @return array
     */
    public function toArray(): array
    {
        $vars = get_object_vars($this);
        $result = [];

        foreach ($vars as $key => $value) {
            if (strpos($key, '_') !== 0) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Indica si la entidad es nueva (no tiene ID asignado).
     *
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->id === null;
    }
}