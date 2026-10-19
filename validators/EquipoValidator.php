<?php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para la entidad Equipo.
 */
final class EquipoValidator
{
    /**
     * Valida los datos para crear un equipo.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateCreate(array $datos): array
    {
        return Validator::make($datos)
            ->required('tipo', 'tipo')
            ->enum('tipo', ['ONU', 'COMBO', 'ROUTER'])
            ->required('direccion_mac', 'dirección MAC')
            ->regex('direccion_mac', '/^[0-9A-Fa-f]{2}(:[0-9A-Fa-f]{2}){5}$/', 'La dirección MAC debe tener formato AA:BB:CC:DD:EE:FF.')
            ->required('puerto_nap', 'puerto NAP')
            ->int('puerto_nap', 1)
            ->required('nap_id', 'NAP')
            ->int('nap_id', 1)
            ->string('serie', null, 100)
            ->string('marca', null, 50)
            ->string('modelo', null, 50)
            ->enum('estado_fisico', ['operativo', 'averiado', 'stock'])
            ->enum('estado_inventario', ['stock', 'asignado', 'devuelto', 'averiado', 'baja'])
            ->enum('propiedad', ['cliente', 'empresa'])
            ->int('servicio_id', 1)
            ->validateOrFail();
    }

    /**
     * Valida los datos para actualizar un equipo.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateUpdate(array $datos): array
    {
        return Validator::make($datos)
            ->enum('tipo', ['ONU', 'COMBO', 'ROUTER'])
            ->regex('direccion_mac', '/^[0-9A-Fa-f]{2}(:[0-9A-Fa-f]{2}){5}$/', 'La dirección MAC debe tener formato AA:BB:CC:DD:EE:FF.')
            ->int('puerto_nap', 1)
            ->int('nap_id', 1)
            ->string('serie', null, 100)
            ->string('marca', null, 50)
            ->string('modelo', null, 50)
            ->enum('estado_fisico', ['operativo', 'averiado', 'stock'])
            ->enum('estado_inventario', ['stock', 'asignado', 'devuelto', 'averiado', 'baja'])
            ->enum('propiedad', ['cliente', 'empresa'])
            ->int('servicio_id', 1)
            ->validateOrFail();
    }
}