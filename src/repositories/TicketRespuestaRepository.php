<?php
// repositories/TicketRespuestaRepository.php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad TicketRespuesta.
 */
final class TicketRespuestaRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'ticket_respuestas';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene las respuestas de un ticket, ordenadas cronológicamente.
     *
     * @param int $ticketId
     * @return array
     */
    public function findByTicketId(int $ticketId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE ticket_id = ? ORDER BY creado_en ASC";
        $stmt = $this->prepareAndExecute($sql, [$ticketId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta el total de respuestas de un ticket.
     *
     * @param int $ticketId
     * @return int
     */
    public function countByTicketId(int $ticketId): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE ticket_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$ticketId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Obtiene las respuestas de un ticket con los datos del autor
     * resueltos vía JOIN, para que el portal muestre "operador1" o
     * "María López" en lugar del ID crudo.
     *
     * Patrón de industria: Zendesk expone `author_id` con un endpoint
     * aparte para resolver el nombre; Salesforce resuelve el autor vía
     * `CreatedBy` en la respuesta; ServiceNow usa `sys_user` en el
     * join. Aquí resolvemos ambos casos en una sola query:
     *   - Si `tipo_autor='staff'`, `autor_username` viene de `usuarios`.
     *   - Si `tipo_autor='cliente'`, `autor_nombre` / `autor_apellido`
     *     vienen de `clientes` (JOIN vía `clientes_credenciales`).
     *
     * El CHECK `chk_ticket_respuestas_discriminador` garantiza que
     * `tipo_autor='staff'` implica `usuario_id` not null y
     * `credencial_id` null, y viceversa. Los 3 LEFT JOIN no colisionan.
     *
     * A2-B.5.a — método nuevo (aditivo). No rompe `findByTicketId()`
     * (usado por el endpoint staff `api_ticket_respuestas.php`).
     *
     * @param int $ticketId
     * @return array
     */
    public function findByTicketIdEnriched(int $ticketId): array
    {
        $sql = "SELECT tr.*,
                       u.username AS autor_username,
                       c.nombre AS autor_nombre,
                       c.apellido AS autor_apellido
                FROM {$this->table} tr
                LEFT JOIN usuarios u ON u.id = tr.usuario_id
                LEFT JOIN clientes_credenciales cc ON cc.id = tr.credencial_id
                LEFT JOIN clientes c ON c.id = cc.cliente_id
                WHERE tr.ticket_id = ?
                ORDER BY tr.creado_en ASC";
        $stmt = $this->prepareAndExecute($sql, [$ticketId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}