<?php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para la entidad PedidoMaterial.
 */
final class PedidoMaterialValidator
{
    /**
     * Valida los datos para crear un material de pedido.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateCreate(array $datos): array
    {
        return Validator::make($datos)
            ->required('pedido_id', 'pedido')
            ->int('pedido_id', 1)
            ->required('descripcion', 'descripción')
            ->string('descripcion', 1, 150)
            ->numeric('cantidad', 0.01)
            ->string('unidad', 1, 20)
            ->int('equipo_id', 1)
            ->string('notas', null, 255)
            ->validateOrFail();
    }

    /**
     * Valida los datos para actualizar un material de pedido.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateUpdate(array $datos): array
    {
        return Validator::make($datos)
            ->int('pedido_id', 1)
            ->string('descripcion', 1, 150)
            ->numeric('cantidad', 0.01)
            ->string('unidad', 1, 20)
            ->int('equipo_id', 1)
            ->string('notas', null, 255)
            ->validateOrFail();
    }
}