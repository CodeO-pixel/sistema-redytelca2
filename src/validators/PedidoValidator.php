<?php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para la entidad Pedido.
 *
 * No exige estado en create: el service fuerza 'pendiente'.
 * No valida fecha_solicitud: la pone la BD.
 */
final class PedidoValidator
{
    /**
     * Valida los datos para crear un pedido.
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
            ->required('tipo', 'tipo')
            ->enum('tipo', ['instalacion', 'reparacion', 'retiro', 'cambio_plan', 'traslado'])
            ->enum('estado', ['pendiente', 'asignado', 'en_curso', 'completado', 'cancelado'])
            ->enum('prioridad', ['Alta', 'Media', 'Baja'])
            ->int('servicio_id', 1)
            ->int('usuario_creador_id', 1)
            ->int('tecnico_asignado_id', 1)
            ->string('descripcion', null, 65535)
            ->date('fecha_programada', 'Y-m-d')
            ->date('fecha_inicio', 'Y-m-d H:i:s')
            ->date('fecha_completado', 'Y-m-d H:i:s')
            ->string('observaciones_cierre', null, 65535)
            ->validateOrFail();
    }

    /**
     * Valida los datos para actualizar un pedido.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateUpdate(array $datos): array
    {
        return Validator::make($datos)
            ->int('cliente_id', 1)
            ->enum('tipo', ['instalacion', 'reparacion', 'retiro', 'cambio_plan', 'traslado'])
            ->enum('estado', ['pendiente', 'asignado', 'en_curso', 'completado', 'cancelado'])
            ->enum('prioridad', ['Alta', 'Media', 'Baja'])
            ->int('servicio_id', 1)
            ->int('usuario_creador_id', 1)
            ->int('tecnico_asignado_id', 1)
            ->string('descripcion', null, 65535)
            ->date('fecha_programada', 'Y-m-d')
            ->date('fecha_inicio', 'Y-m-d H:i:s')
            ->date('fecha_completado', 'Y-m-d H:i:s')
            ->string('observaciones_cierre', null, 65535)
            ->validateOrFail();
    }
}