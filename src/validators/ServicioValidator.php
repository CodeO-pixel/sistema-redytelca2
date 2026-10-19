<?php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para la entidad Servicio.
 *
 * No exige estado_comercial en create: el service fuerza 'pendiente'.
 */
final class ServicioValidator
{
    /**
     * Valida los datos para crear un servicio.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateCreate(array $datos): array
    {
        return Validator::make($datos)
            ->required('cliente_id', 'cliente')
            ->int('cliente_id', 1)
            ->required('plan_id', 'plan')
            ->int('plan_id', 1)
            ->int('nap_id', 1, null)
            ->string('alias', null, 50)
            ->enum('estado_comercial', ['activo', 'suspendido', 'retirado', 'pendiente'])
            ->int('direccion_id', 1, null)
            ->validateOrFail();
    }

    /**
     * Valida los datos para actualizar un servicio.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateUpdate(array $datos): array
    {
        return Validator::make($datos)
            ->int('cliente_id', 1)
            ->int('plan_id', 1)
            ->int('nap_id', 1, null)
            ->string('alias', null, 50)
            ->enum('estado_comercial', ['activo', 'suspendido', 'retirado', 'pendiente'])
            ->int('direccion_id', 1, null)
            ->validateOrFail();
    }
}