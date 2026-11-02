<?php
// validators/ClienteCredencialValidator.php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para las operaciones de credenciales del portal de cliente.
 */
final class ClienteCredencialValidator
{
    /**
     * Valida un cliente_id.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateClienteId(array $datos): array
    {
        return Validator::make($datos)
            ->required('cliente_id', 'cliente')
            ->int('cliente_id', 1)
            ->validateOrFail();
    }

    /**
     * Valida los datos del reset de contraseña.
     *
     * password_nueva es opcional. Si viene, mínimo 8 chars.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateReset(array $datos): array
    {
        return Validator::make($datos)
            ->required('cliente_id', 'cliente')
            ->int('cliente_id', 1)
            ->string('password_nueva', 8, 255)
            ->validateOrFail();
    }

    /**
     * Valida los datos del toggle de estado.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateToggle(array $datos): array
    {
        return Validator::make($datos)
            ->required('cliente_id', 'cliente')
            ->int('cliente_id', 1)
            ->required('estado', 'estado')
            ->enum('estado', ['activa', 'suspendida'])
            ->validateOrFail();
    }
}