<?php
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
}