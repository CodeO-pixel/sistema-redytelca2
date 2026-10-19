<?php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para la entidad Nodo.
 */
final class NodoValidator
{
    /**
     * Valida los datos para crear un nodo.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateCreate(array $datos): array
    {
        return Validator::make($datos)
            ->required('nombre', 'nombre')
            ->string('nombre', 1, 100)
            ->required('latitud', 'latitud')
            ->numeric('latitud')
            ->inRange('latitud', -90, 90)
            ->required('longitud', 'longitud')
            ->numeric('longitud')
            ->inRange('longitud', -180, 180)
            ->string('ubicacion', null, 255)
            ->enum('estado', ['activo', 'mantenimiento', 'inactivo'])
            ->validateOrFail();
    }

    /**
     * Valida los datos para actualizar un nodo.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateUpdate(array $datos): array
    {
        return Validator::make($datos)
            ->string('nombre', 1, 100)
            ->numeric('latitud')
            ->inRange('latitud', -90, 90)
            ->numeric('longitud')
            ->inRange('longitud', -180, 180)
            ->string('ubicacion', null, 255)
            ->enum('estado', ['activo', 'mantenimiento', 'inactivo'])
            ->validateOrFail();
    }
}