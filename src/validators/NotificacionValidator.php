<?php
// validators/NotificacionValidator.php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para las operaciones de notificaciones (staff y cliente).
 *
 * GET / count_only no requieren validación de body (query params
 * saneados en el controller con max/min).
 *
 * PUT acepta dos formas mutuamente excluyentes:
 *   - { "id_notificacion": 5 }  → marca una como leída.
 *   - { "mark_all": true }      → marca todas las del usuario como leídas.
 */
final class NotificacionValidator
{
    /**
     * Valida el body del PUT.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateUpdate(array $datos): array
    {
        $markAll = !empty($datos['mark_all']);

        if ($markAll) {
            return Validator::make($datos)->validateOrFail();
        }

        return Validator::make($datos)
            ->required('id_notificacion', 'id de notificación')
            ->int('id_notificacion', 1)
            ->validateOrFail();
    }
}