<?php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para la entidad SlaDefinicion.
 */
final class SlaDefinicionValidator
{
    /**
     * Valida los datos para crear una definición SLA.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateCreate(array $datos): array
    {
        return Validator::make($datos)
            ->required('categoria_id', 'categoría')
            ->int('categoria_id', 1)
            ->required('prioridad', 'prioridad')
            ->enum('prioridad', ['Alta', 'Media', 'Baja'])
            ->required('horas_respuesta', 'horas de respuesta')
            ->int('horas_respuesta', 1, 999)
            ->required('horas_resolucion', 'horas de resolución')
            ->int('horas_resolucion', 1, 999)
            ->string('descripcion', null, 255)
            ->validateOrFail();
    }

    /**
     * Valida los datos para actualizar una definición SLA.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateUpdate(array $datos): array
    {
        return Validator::make($datos)
            ->int('categoria_id', 1)
            ->enum('prioridad', ['Alta', 'Media', 'Baja'])
            ->int('horas_respuesta', 1, 999)
            ->int('horas_resolucion', 1, 999)
            ->string('descripcion', null, 255)
            ->validateOrFail();
    }
}