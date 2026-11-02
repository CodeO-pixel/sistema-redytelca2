<?php
// validators/PortalTicketValidator.php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para los endpoints del portal de tickets (A2-B.5).
 *
 * Solo valida forma del body. Las reglas de negocio (ownership de
 * `servicio_id`, ticket no cerrado, categoría activa) las valida
 * `PortalTicketService`.
 *
 * NO valida:
 *   - `cliente_id`       → viene de la sesión (`requireClientAuth`).
 *   - `usuario_creador_id` → forzado a NULL por el service (portal).
 *   - `origen`           → forzado a 'portal_cliente' por el service.
 *   - `estado`           → forzado a 'Abierto' al crear.
 *   - `tipo_autor`       → forzado a 'cliente' al responder.
 *   - `credencial_id`    → viene de la sesión.
 *   - `usuario_id`       → forzado a NULL al responder desde el portal.
 *   - `sla_vence_en`     → calculado por trigger.
 */
final class PortalTicketValidator
{
    /**
     * Valida el body del POST api_portal_tickets.php.
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
            ->enum('prioridad', ['Alta', 'Media', 'Baja'])
            ->int('categoria_id', 1)
            ->int('servicio_id', 1)
            ->validateOrFail();
    }

    /**
     * Valida el body del POST api_portal_ticket_respuestas.php.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateResponder(array $datos): array
    {
        return Validator::make($datos)
            ->required('id_ticket', 'ticket')
            ->int('id_ticket', 1)
            ->required('mensaje', 'mensaje')
            ->string('mensaje', 1, 65535)
            ->validateOrFail();
    }
}