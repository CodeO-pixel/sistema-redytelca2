<?php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para la entidad Tarea.
 *
 * No exige estado en create: el service fuerza 'Pendiente'.
 */
final class TareaValidator
{
    /**
     * Valida los datos para crear una tarea.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateCreate(array $datos): array
    {
        return Validator::make($datos)
            ->required('titulo', 'título')
            ->string('titulo', 1, 150)
            ->string('descripcion', null, 65535)
            ->enum('estado', ['Pendiente', 'En curso', 'Completada'])
            ->enum('prioridad', ['Alta', 'Media', 'Baja'])
            ->int('cliente_id', 1)
            ->int('ticket_id', 1)
            ->int('servicio_id', 1)
            ->int('usuario_creador_id', 1)
            ->validateOrFail();
    }

    /**
     * Valida los datos para actualizar una tarea.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateUpdate(array $datos): array
    {
        return Validator::make($datos)
            ->string('titulo', 1, 150)
            ->string('descripcion', null, 65535)
            ->enum('estado', ['Pendiente', 'En curso', 'Completada'])
            ->enum('prioridad', ['Alta', 'Media', 'Baja'])
            ->int('cliente_id', 1)
            ->int('ticket_id', 1)
            ->int('servicio_id', 1)
            ->int('usuario_creador_id', 1)
            ->validateOrFail();
    }
}