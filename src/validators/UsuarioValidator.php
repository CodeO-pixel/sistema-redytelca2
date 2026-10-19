<?php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para la entidad Usuario.
 *
 * No valida roles (array): el service los procesa en asignarRoles.
 * En update, password es opcional (el service lo descarta y no
 * permite cambiar contraseña por esta vía).
 */
final class UsuarioValidator
{
    /**
     * Valida los datos para crear un usuario.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateCreate(array $datos): array
    {
        return Validator::make($datos)
            ->required('username', 'nombre de usuario')
            ->string('username', 1, 50)
            ->required('password', 'contraseña')
            ->string('password', 8, 255)
            ->required('email', 'correo')
            ->email('email')
            ->string('email', 1, 100)
            ->enum('estado', ['Activo', 'Inactivo', 'Bloqueado'])
            ->int('must_change_password', 0, 1)
            ->int('email_verified', 0, 1)
            ->validateOrFail();
    }

    /**
     * Valida los datos para actualizar un usuario.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateUpdate(array $datos): array
    {
        return Validator::make($datos)
            ->string('username', 1, 50)
            ->string('password', 8, 255)
            ->email('email')
            ->string('email', 1, 100)
            ->enum('estado', ['Activo', 'Inactivo', 'Bloqueado'])
            ->int('must_change_password', 0, 1)
            ->int('email_verified', 0, 1)
            ->validateOrFail();
    }
}