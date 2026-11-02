<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Factura.
 */
final class FacturaRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'facturas';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene todas las facturas.
     * Incluye nombres, apellidos y cédula del cliente, el alias del servicio
     * (servicio_alias), y el total de líneas de la factura (total_lineas).
     *
     * @return array
     */
    public function findAll(): array
    {
        $sql = "SELECT f.*,
                       c.nombre AS nombres,
                       c.apellido AS apellidos,
                       c.cedula AS cedula,
                       s.alias AS servicio_alias,
                       (SELECT COUNT(*) FROM factura_lineas fl WHERE fl.factura_id = f.id) AS total_lineas
                FROM {$this->table} f
                INNER JOIN servicios s ON s.id = f.servicio_id
                INNER JOIN clientes c ON c.id = s.cliente_id";
        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las facturas cuya fecha_emision cae en el rango [desde, hasta].
     * Mismo SELECT enriquecido que findAll() + WHERE f.fecha_emision BETWEEN ? AND ?.
     *
     * B3.15 — Reportes con periodo (filter server-side). Ordena por fecha_emision
     * descendente para que el operador vea primero las más recientes del periodo.
     *
     * @param string $desde YYYY-MM-DD (inclusive).
     * @param string $hasta YYYY-MM-DD (inclusive).
     * @return array
     */
    public function findAllByFechaEmisionRange(string $desde, string $hasta): array
    {
        $sql = "SELECT f.*,
                       c.nombre AS nombres,
                       c.apellido AS apellidos,
                       c.cedula AS cedula,
                       s.alias AS servicio_alias,
                       (SELECT COUNT(*) FROM factura_lineas fl WHERE fl.factura_id = f.id) AS total_lineas
                FROM {$this->table} f
                INNER JOIN servicios s ON s.id = f.servicio_id
                INNER JOIN clientes c ON c.id = s.cliente_id
                WHERE f.fecha_emision BETWEEN ? AND ?
                ORDER BY f.fecha_emision DESC, f.id DESC";
        $stmt = $this->prepareAndExecute($sql, [$desde, $hasta]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las facturas de un servicio, ordenadas por fecha de vencimiento descendente.
     *
     * @param int $servicioId
     * @return array
     */
    public function findByServicioId(int $servicioId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE servicio_id = ? ORDER BY fecha_vencimiento DESC";
        $stmt = $this->prepareAndExecute($sql, [$servicioId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca una factura específica por su servicio y periodo.
     *
     * @param int $servicioId
     * @param string $periodo
     * @return array|null
     */
    public function findByServicioIdAndPeriodo(int $servicioId, string $periodo): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE servicio_id = ? AND periodo = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$servicioId, $periodo]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Obtiene las facturas filtradas por estado.
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
     * Obtiene las facturas pendientes cuya fecha de vencimiento ya pasó.
     *
     * @return array
     */
    public function findPendientesVencidas(): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE estado = 'pendiente' AND fecha_vencimiento < CURDATE()";
        $stmt = $this->prepareAndExecute($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta el total de facturas asociadas a un servicio.
     *
     * @param int $servicioId
     * @return int
     */
    public function countByServicioId(int $servicioId): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE servicio_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$servicioId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Lista paginada de facturas del cliente para el portal, con datos
     * públicos (sin PII del cliente, que ya se conoce a sí mismo).
     *
     * Incluye servicio_alias y total_lineas (subquery).
     * NO incluye nombres/apellidos/cedula.
     *
     * A2-B.4.b — endpoint GET api_portal_facturas.php.
     *
     * Filtros opcionales:
     *   - estado: uno de pendiente|pagada|parcial|vencida|anulada.
     *   - periodo: YYYY-MM.
     * Los filtros inválidos se ignoran (el caller ya los limpió con
     * `PortalFacturaService::limpiarFiltros`).
     *
     * @param int $clienteId
     * @param int $limit
     * @param int $offset
     * @param array $filtros
     * @return array
     */
    public function paginateByClienteId(int $clienteId, int $limit, int $offset, array $filtros = []): array
    {
        $sql = "SELECT f.id, f.servicio_id, f.periodo, f.monto, f.moneda, f.tasa_cambio,
                       f.fecha_emision, f.fecha_vencimiento, f.estado,
                       f.creado_en, f.actualizado_en,
                       s.alias AS servicio_alias,
                       (SELECT COUNT(*) FROM factura_lineas fl WHERE fl.factura_id = f.id) AS total_lineas
                FROM {$this->table} f
                INNER JOIN servicios s ON s.id = f.servicio_id
                WHERE s.cliente_id = ?";

        $params = [$clienteId];

        if (!empty($filtros['estado'])) {
            $sql .= " AND f.estado = ?";
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['periodo'])) {
            $sql .= " AND f.periodo = ?";
            $params[] = $filtros['periodo'];
        }

        $sql .= " ORDER BY f.fecha_emision DESC, f.id DESC LIMIT ? OFFSET ?";

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
     * Cuenta el total de facturas del cliente con los mismos filtros que
     * `paginateByClienteId`.
     *
     * A2-B.4.b — endpoint GET api_portal_facturas.php.
     *
     * @param int $clienteId
     * @param array $filtros
     * @return int
     */
    public function countByClienteId(int $clienteId, array $filtros = []): int
    {
        $sql = "SELECT COUNT(*)
                FROM {$this->table} f
                INNER JOIN servicios s ON s.id = f.servicio_id
                WHERE s.cliente_id = ?";
        $params = [$clienteId];

        if (!empty($filtros['estado'])) {
            $sql .= " AND f.estado = ?";
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['periodo'])) {
            $sql .= " AND f.periodo = ?";
            $params[] = $filtros['periodo'];
        }

        $stmt = $this->prepareAndExecute($sql, $params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Devuelve una factura del cliente (con datos públicos) o `null` si
     * no existe o no pertenece a ese cliente.
     *
     * Defensa BOLA/IDOR (OWASP API Security Top 10 2023, API1:2023):
     * filtra `AND s.cliente_id = ?`. Si la factura existe pero es de
     * otro cliente, retorna `null`. El controller lo convierte en 404
     * (no 403, para no filtrar existencia).
     *
     * A2-B.4.b — endpoint GET api_portal_facturas.php?id=N.
     *
     * @param int $id
     * @param int $clienteId
     * @return array|null
     */
    public function findByIdAndClienteId(int $id, int $clienteId): ?array
    {
        $sql = "SELECT f.id, f.servicio_id, f.periodo, f.monto, f.moneda, f.tasa_cambio,
                       f.impuesto_aplicado, f.fecha_emision, f.fecha_vencimiento, f.estado,
                       f.creado_en, f.actualizado_en,
                       s.alias AS servicio_alias,
                       (SELECT COUNT(*) FROM factura_lineas fl WHERE fl.factura_id = f.id) AS total_lineas
                FROM {$this->table} f
                INNER JOIN servicios s ON s.id = f.servicio_id
                WHERE f.id = ? AND s.cliente_id = ?
                LIMIT 1";

        $stmt = $this->prepareAndExecute($sql, [$id, $clienteId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}