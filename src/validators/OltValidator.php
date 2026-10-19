<?php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para la entidad Olt.
 */
final class OltValidator
{
    /**
     * Valida los datos para crear una OLT.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateCreate(array $datos): array
    {
        return Validator::make($datos)
            ->required('marca_modelo', 'marca y modelo')
            ->string('marca_modelo', 1, 100)
            ->required('puertos_pon', 'puertos PON')
            ->int('puertos_pon', 1)
            ->required('nodo_id', 'nodo')
            ->int('nodo_id', 1)
            ->string('ip_gestion', null, 45)
            ->string('codigo', null, 20)
            ->validateOrFail();
    }

    /**
     * Valida los datos para actualizar una OLT.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateUpdate(array $datos): array
    {
        return Validator::make($datos)
            ->string('marca_modelo', 1, 100)
            ->int('puertos_pon', 1)
            ->int('nodo_id', 1)
            ->string('ip_gestion', null, 45)
            ->string('codigo', null, 20)
            ->validateOrFail();
    }
}