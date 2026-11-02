<?php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

final class BancoValidator
{
    public static function validateCreate(array $datos): array
    {
        $validated = Validator::make($datos)
            ->required('nombre', 'nombre del banco')
            ->string('nombre', 2, 120, 'nombre del banco')
            ->validateOrFail();

        return array_intersect_key($validated, array_flip(['nombre']));
    }

    public static function validateUpdate(array $datos): array
    {
        $validated = Validator::make($datos)
            ->required('id', 'banco')
            ->int('id', 1)
            ->required('activo', 'estado del banco')
            ->int('activo', 0, 1)
            ->validateOrFail();

        return array_intersect_key($validated, array_flip(['id', 'activo']));
    }
}
