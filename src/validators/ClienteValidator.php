<?php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para la entidad Cliente.
 */
final class ClienteValidator
{
    /**
     * Valida los datos para crear un cliente.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateCreate(array $datos): array
    {
        return Validator::make($datos)
            ->required('nombre', 'nombre')
            ->string('nombre', 1, 65)
            ->required('apellido', 'apellido')
            ->string('apellido', 1, 65)
            ->required('cedula', 'cédula')
            ->regex('cedula', '/^([VEJPG]-)?[0-9]{6,9}$/', 'La cédula debe tener formato V-12345678.')
            ->required('correo', 'correo')
            ->email('correo')
            ->string('correo', 1, 80)
            ->enum('estado', ['activo', 'suspendido', 'inactivo'])
            ->string('segmento', null, 30)
            ->int('direccion_id', 1, null)
            ->validateOrFail();
    }

    /**
     * Valida los datos para actualizar un cliente.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateUpdate(array $datos): array
    {
        return Validator::make($datos)
            ->string('nombre', 1, 65)
            ->string('apellido', 1, 65)
            ->regex('cedula', '/^([VEJPG]-)?[0-9]{6,9}$/', 'La cédula debe tener formato V-12345678.')
            ->email('correo')
            ->string('correo', 1, 80)
            ->enum('estado', ['activo', 'suspendido', 'inactivo'])
            ->string('segmento', null, 30)
            ->int('direccion_id', 1, null)
            ->validateOrFail();
    }
}