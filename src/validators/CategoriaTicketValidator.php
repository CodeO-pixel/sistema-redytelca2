<?php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para la entidad CategoriaTicket.
 */
final class CategoriaTicketValidator
{
    /**
     * Valida los datos para crear una categoría de ticket.
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
            ->string('descripcion', null, 255)
            ->regex('color', '/^#[0-9a-fA-F]{6}$/', 'El color debe tener formato #RRGGBB.')
            ->int('activa', 0, 1)
            ->validateOrFail();
    }

    /**
     * Valida los datos para actualizar una categoría de ticket.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateUpdate(array $datos): array
    {
        return Validator::make($datos)
            ->string('nombre', 1, 60)
            ->string('descripcion', null, 255)
            ->regex('color', '/^#[0-9a-fA-F]{6}$/', 'El color debe tener formato #RRGGBB.')
            ->int('activa', 0, 1)
            ->validateOrFail();
    }
}