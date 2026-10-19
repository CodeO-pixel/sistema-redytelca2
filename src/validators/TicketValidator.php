<?php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para la entidad Ticket.
 *
 * No exige estado ni origen en create: el service fuerza 'Abierto'
 * y 'backoffice'. No valida sla_vence_en: lo calcula el trigger
 * trg_tickets_sla_insert.
 */
final class TicketValidator
{
    /**
     * Valida los datos para crear un ticket.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateCreate(array $datos): array
    {
        return Validator::make($datos)
            ->required('asunto', 'asunto')
            ->string('asunto', 1, 150)
            ->string('descripcion', null, 65535)
            ->enum('estado', ['Abierto', 'En proceso', 'Cerrado'])
            ->enum('prioridad', ['Alta', 'Media', 'Baja'])
            ->enum('origen', ['portal_cliente', 'backoffice'])
            ->int('categoria_id', 1)
            ->required('cliente_id', 'cliente')
            ->int('cliente_id', 1)
            ->int('servicio_id', 1)
            ->int('usuario_creador_id', 1)
            ->validateOrFail();
    }

    /**
     * Valida los datos para actualizar un ticket.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateUpdate(array $datos): array
    {
        return Validator::make($datos)
            ->string('asunto', 1, 150)
            ->string('descripcion', null, 65535)
            ->enum('estado', ['Abierto', 'En proceso', 'Cerrado'])
            ->enum('prioridad', ['Alta', 'Media', 'Baja'])
            ->enum('origen', ['portal_cliente', 'backoffice'])
            ->int('categoria_id', 1)
            ->int('cliente_id', 1)
            ->int('servicio_id', 1)
            ->int('usuario_creador_id', 1)
            ->validateOrFail();
    }
}