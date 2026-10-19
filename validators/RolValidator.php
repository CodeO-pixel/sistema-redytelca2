<?php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para la entidad Rol.
 *
 * No valida módulos ni permisos: los gestiona permisos_guardar.php.
 */
final class RolValidator
{
    /**
     * Valida los datos para crear un rol.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateCreate(array $datos): array
    {
        return Validator::make($datos)
            ->required('nombre_rol', 'nombre del rol')
            ->string('nombre_rol', 1, 50)
            ->string('descripcion', null, 255)
            ->validateOrFail();
    }

    /**
     * Valida los datos para actualizar un rol.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateUpdate(array $datos): array
    {
        return Validator::make($datos)
            ->string('nombre_rol', 1, 50)
            ->string('descripcion', null, 255)
            ->validateOrFail();
    }
}