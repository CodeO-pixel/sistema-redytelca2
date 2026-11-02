<?php
// repositories/TicketRepository.php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Ticket.
 */
final class TicketRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'tickets';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene todos los tickets ordenados por fecha de creación descendente.
     * Incluye nombres, apellidos y cédula del cliente, el username del
     * creador (creador), y nombre + color de la categoría
     * (categoria_nombre, categoria_color).
     *
     * @return array
     */
    public function findAllOrderedByCreadoEnDesc(): array
    {
        $sql = "SELECT t.*, 
                       c.nombre AS nombres, 
                       c.apellido AS apellidos, 
                       c.cedula AS cedula,
                       u.username AS creador,
                       ct.nombre AS categoria_nombre,
                       ct.color AS categoria_color
                FROM {$this->table} t
                LEFT JOIN clientes c ON c.id = t.cliente_id
                LEFT JOIN usuarios u ON u.id = t.usuario_creador_id
                LEFT JOIN categorias_ticket ct ON ct.id = t.categoria_id
                ORDER BY t.creado_en DESC";
        $stmt = $this->prepareAndExecute($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los tickets cuya creado_en cae en el rango [desde, hasta].
     * Mismo SELECT enriquecido que findAllOrderedByCreadoEnDesc() + WHERE
     * de rango.
     *
     * B3.15 — Reportes con periodo (filter server-side).
     *
     * IMPORTANTE: `tickets.creado_en` es TIMESTAMP, no DATE. El filtro
     * compara contra el rango completo del día ('YYYY-MM-DD 00:00:00'
     * a 'YYYY-MM-DD 23:59:59') para no perder los tickets creados
     * durante el último día del rango. Mismo criterio que
     * PagoRepository::findAllByFechaPagoRange.
     *
     * @param string $desde YYYY-MM-DD (inclusive).
     * @param string $hasta YYYY-MM-DD (inclusive).
     * @return array
     */
    public function findAllByCreadoEnRange(string $desde, string $hasta): array
    {
        $sql = "SELECT t.*, 
                       c.nombre AS nombres, 
                       c.apellido AS apellidos, 
                       c.cedula AS cedula,
                       u.username AS creador,
                       ct.nombre AS categoria_nombre,
                       ct.color AS categoria_color
                FROM {$this->table} t
                LEFT JOIN clientes c ON c.id = t.cliente_id
                LEFT JOIN usuarios u ON u.id = t.usuario_creador_id
                LEFT JOIN categorias_ticket ct ON ct.id = t.categoria_id
                WHERE t.creado_en >= ? AND t.creado_en <= ?
                ORDER BY t.creado_en DESC, t.id DESC";
        $stmt = $this->prepareAndExecute($sql, [$desde . ' 00:00:00', $hasta . ' 23:59:59']);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los tickets de un cliente.
     *
     * @param int $clienteId
     * @return array
     */
    public function findByClienteId(int $clienteId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE cliente_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$clienteId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los tickets de un servicio.
     *
     * @param int $servicioId
     * @return array
     */
    public function findByServicioId(int $servicioId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE servicio_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$servicioId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los tickets filtrados por estado.
     *
     * @param string $estado
     * @return array
     */
    public function findByEstado(string $estado): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE estado = ?";
        $stmt = $this->prepareAndExecute($sql, [$estado]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los tickets filtrados por prioridad.
     *
     * @param string $prioridad
     * @return array
     */
    public function findByPrioridad(string $prioridad): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE prioridad = ?";
        $stmt = $this->prepareAndExecute($sql, [$prioridad]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los tickets de una categoría.
     *
     * @param int $categoriaId
     * @return array
     */
    public function findByCategoriaId(int $categoriaId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE categoria_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$categoriaId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los tickets filtrados por origen.
     *
     * @param string $origen
     * @return array
     */
    public function findByOrigen(string $origen): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE origen = ?";
        $stmt = $this->prepareAndExecute($sql, [$origen]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta los tickets abiertos de un cliente.
     *
     * Replica la semántica de v_clientes_360 → tickets_abiertos,
     * que excluye los tickets con estado = 'Cerrado'.
     *
     * @param int $clienteId
     * @return int
     */
    public function countAbiertosByClienteId(int $clienteId): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE cliente_id = ? AND estado != 'Cerrado'";
        $stmt = $this->prepareAndExecute($sql, [$clienteId]);
        return (int) $stmt->fetchColumn();
    }

    // ─────────────────────────────────────────────────────────────
    // A2-B.5 — Portal de cliente (tickets)
    //
    // Los 3 métodos siguientes existen para el portal del cliente
    // (Fase 5). Filtran siempre por `cliente_id` (BOLA/IDOR). El
    // `cliente_id` viene de la sesión (`Auth::requireClientAuth`),
    // nunca del body. Patrón A2-B.4 (`FacturaRepository`,
    // `PagoRepository`, `ServicioRepository`).
    //
    // SELECT enriquecido para el portal:
    //   - c.nombre / c.apellido / c.cedula (JOIN clientes).
    //   - u.username AS creador (JOIN usuarios, staff que abrió el ticket).
    //   - ct.nombre / ct.color AS categoria_* (JOIN categorias_ticket).
    //   - s.alias AS servicio_alias (JOIN servicios).
    //   - respuestas_count (subquery, cuántas respuestas tiene el ticket).
    //
    // NO se exponen campos internos (password, intentos_fallidos, etc.).
    // ─────────────────────────────────────────────────────────────

    /**
     * Lista paginada de tickets del cliente, con datos enriquecidos para
     * el portal. Filtros opcionales: estado, prioridad.
     *
     * @param int $clienteId ID del cliente autenticado (de la sesión).
     * @param int $limit
     * @param int $offset
     * @param array $filtros ['estado' => string, 'prioridad' => string]
     * @return array
     */
    public function paginateByClienteId(int $clienteId, int $limit, int $offset, array $filtros = []): array
    {
        $sql = "SELECT t.*,
                       c.nombre AS nombres,
                       c.apellido AS apellidos,
                       c.cedula AS cedula,
                       u.username AS creador,
                       ct.nombre AS categoria_nombre,
                       ct.color AS categoria_color,
                       s.alias AS servicio_alias,
                       (SELECT COUNT(*) FROM ticket_respuestas tr WHERE tr.ticket_id = t.id) AS respuestas_count
                FROM {$this->table} t
                LEFT JOIN clientes c ON c.id = t.cliente_id
                LEFT JOIN usuarios u ON u.id = t.usuario_creador_id
                LEFT JOIN categorias_ticket ct ON ct.id = t.categoria_id
                LEFT JOIN servicios s ON s.id = t.servicio_id
                WHERE t.cliente_id = ?";

        $params = [$clienteId];

        if (!empty($filtros['estado'])) {
            $sql .= " AND t.estado = ?";
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['prioridad'])) {
            $sql .= " AND t.prioridad = ?";
            $params[] = $filtros['prioridad'];
        }

        $sql .= " ORDER BY t.creado_en DESC, t.id DESC LIMIT ? OFFSET ?";

        $stmt = $this->pdo->prepare($sql);
        $idx = 1;
        foreach ($params as $p) {
            $stmt->bindValue($idx++, $p, \PDO::PARAM_STR);
        }
        $stmt->bindValue($idx++, $limit, \PDO::PARAM_INT);
        $stmt->bindValue($idx, $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta el total de tickets del cliente con los mismos filtros que
     * `paginateByClienteId`.
     *
     * @param int $clienteId
     * @param array $filtros
     * @return int
     */
    public function countByClienteId(int $clienteId, array $filtros = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} t WHERE t.cliente_id = ?";
        $params = [$clienteId];

        if (!empty($filtros['estado'])) {
            $sql .= " AND t.estado = ?";
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['prioridad'])) {
            $sql .= " AND t.prioridad = ?";
            $params[] = $filtros['prioridad'];
        }

        $stmt = $this->prepareAndExecute($sql, $params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Devuelve un ticket del cliente (con datos enriquecidos para el
     * portal) o `null` si no existe o no pertenece a ese cliente.
     *
     * Defensa BOLA/IDOR (OWASP API Security Top 10 2023, API1:2023):
     * filtra `AND t.cliente_id = ?`. Si el ticket existe pero es de
     * otro cliente, retorna `null`. El controller lo convierte en 404
     * (no 403, para no filtrar existencia).
     *
     * @param int $id
     * @param int $clienteId
     * @return array|null
     */
    public function findByIdAndClienteId(int $id, int $clienteId): ?array
    {
        $sql = "SELECT t.*,
                       c.nombre AS nombres,
                       c.apellido AS apellidos,
                       c.cedula AS cedula,
                       u.username AS creador,
                       ct.nombre AS categoria_nombre,
                       ct.color AS categoria_color,
                       s.alias AS servicio_alias,
                       (SELECT COUNT(*) FROM ticket_respuestas tr WHERE tr.ticket_id = t.id) AS respuestas_count
                FROM {$this->table} t
                LEFT JOIN clientes c ON c.id = t.cliente_id
                LEFT JOIN usuarios u ON u.id = t.usuario_creador_id
                LEFT JOIN categorias_ticket ct ON ct.id = t.categoria_id
                LEFT JOIN servicios s ON s.id = t.servicio_id
                WHERE t.id = ? AND t.cliente_id = ?
                LIMIT 1";

        $stmt = $this->prepareAndExecute($sql, [$id, $clienteId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}