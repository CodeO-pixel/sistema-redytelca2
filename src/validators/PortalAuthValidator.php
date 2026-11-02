<?php
// validators/PortalAuthValidator.php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para las operaciones de autenticación del portal de cliente.
 */
final class PortalAuthValidator
{
    /**
     * Valida los datos del login.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateLogin(array $datos): array
    {
        return Validator::make($datos)
            ->required('username', 'usuario')
            ->string('username', 1, 50)
            ->required('password', 'contraseña')
            ->string('password', 1, 255)
            ->validateOrFail();
    }

    /**
     * Valida los datos del cambio de contraseña.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateCambiarPassword(array $datos): array
    {
        return Validator::make($datos)
            ->required('password_actual', 'contraseña actual')
            ->string('password_actual', 1, 255)
            ->required('password_nueva', 'contraseña nueva')
            ->string('password_nueva', 8, 255)
            ->validateOrFail();
    }
}