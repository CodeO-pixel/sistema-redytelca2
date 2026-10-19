<?php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para la entidad Plan.
 */
final class PlanValidator
{
    /**
     * Valida los datos para crear un plan.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateCreate(array $datos): array
    {
        return Validator::make($datos)
            ->required('nombre', 'nombre')
            ->string('nombre', 1, 60)
            ->required('precio_mensual', 'precio mensual')
            ->numeric('precio_mensual', 0)
            ->numeric('velocidad_bajada_mbps', 0.01)
            ->numeric('velocidad_subida_mbps', 0.01)
            ->enum('tecnologia', ['FTTH', 'HFC', 'WISP', 'OTRO'])
            ->enum('moneda', ['USD', 'VES', 'COP', 'EUR'])
            ->enum('estado', ['activo', 'inactivo'])
            ->validateOrFail();
    }

    /**
     * Valida los datos para actualizar un plan.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateUpdate(array $datos): array
    {
        return Validator::make($datos)
            ->string('nombre', 1, 60)
            ->numeric('precio_mensual', 0)
            ->numeric('velocidad_bajada_mbps', 0.01)
            ->numeric('velocidad_subida_mbps', 0.01)
            ->enum('tecnologia', ['FTTH', 'HFC', 'WISP', 'OTRO'])
            ->enum('moneda', ['USD', 'VES', 'COP', 'EUR'])
            ->enum('estado', ['activo', 'inactivo'])
            ->validateOrFail();
    }
}