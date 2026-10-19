<?php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para la entidad Nap.
 */
final class NapValidator
{
    /**
     * Valida los datos para crear una NAP.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateCreate(array $datos): array
    {
        return Validator::make($datos)
            ->required('codigo', 'código')
            ->string('codigo', 1, 20)
            ->required('latitud', 'latitud')
            ->numeric('latitud')
            ->inRange('latitud', -90, 90)
            ->required('longitud', 'longitud')
            ->numeric('longitud')
            ->inRange('longitud', -180, 180)
            ->required('olt_id', 'OLT')
            ->int('olt_id', 1)
            ->int('cantidad_puertos_max', 1)
            ->string('ubicacion_fisica', null, 65535)
            ->validateOrFail();
    }

    /**
     * Valida los datos para actualizar una NAP.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateUpdate(array $datos): array
    {
        return Validator::make($datos)
            ->string('codigo', 1, 20)
            ->numeric('latitud')
            ->inRange('latitud', -90, 90)
            ->numeric('longitud')
            ->inRange('longitud', -180, 180)
            ->int('olt_id', 1)
            ->int('cantidad_puertos_max', 1)
            ->string('ubicacion_fisica', null, 65535)
            ->validateOrFail();
    }
}