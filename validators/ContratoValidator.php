<?php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para la entidad Contrato.
 *
 * No exige estado en create: el service fuerza 'vigente'.
 */
final class ContratoValidator
{
    /**
     * Valida los datos para crear un contrato.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateCreate(array $datos): array
    {
        return Validator::make($datos)
            ->required('servicio_id', 'servicio')
            ->int('servicio_id', 1)
            ->required('fecha_inicio', 'fecha de inicio')
            ->date('fecha_inicio')
            ->date('fecha_fin')
            ->enum('tipo_contrato', ['indefinido', 'plazo_fijo', 'promocional'])
            ->enum('estado', ['vigente', 'vencido', 'rescindido'])
            ->string('observaciones', null, 1000)
            ->validateOrFail();
    }

    /**
     * Valida los datos para actualizar un contrato.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateUpdate(array $datos): array
    {
        return Validator::make($datos)
            ->int('servicio_id', 1)
            ->date('fecha_inicio')
            ->date('fecha_fin')
            ->enum('tipo_contrato', ['indefinido', 'plazo_fijo', 'promocional'])
            ->enum('estado', ['vigente', 'vencido', 'rescindido'])
            ->string('observaciones', null, 1000)
            ->validateOrFail();
    }
}