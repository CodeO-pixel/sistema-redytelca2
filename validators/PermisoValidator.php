<?php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para la entidad Permiso.
 */
final class PermisoValidator
{
    /**
     * Valida los datos para crear un permiso.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateCreate(array $datos): array
    {
        return Validator::make($datos)
            ->required('nombre', 'nombre')
            ->string('nombre', 1, 30)
            ->validateOrFail();
    }

    /**
     * Valida los datos para actualizar un permiso.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateUpdate(array $datos): array
    {
        return Validator::make($datos)
            ->string('nombre', 1, 30)
            ->validateOrFail();
    }
}