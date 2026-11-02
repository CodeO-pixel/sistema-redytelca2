<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Pago.
 */
final class PagoRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'pagos';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene todos los pagos.
     * Incluye nombres, apellidos y cédula del cliente, el alias del
     * servicio (servicio_alias), el periodo de la factura
     * (factura_periodo) y el nombre del método de pago (metodo_nombre).
     *
     * @return array
     */
    public function findAll(): array
    {
        $sql = "SELECT p.*, 
                       c.nombre AS nombres, 
                       c.apellido AS apellidos, 
                       c.cedula AS cedula,
                       s.alias AS servicio_alias,
                       f.periodo AS factura_periodo, 
                       mp.nombre AS metodo_nombre
                FROM {$this->table} p
                INNER JOIN clientes c ON c.id = p.cliente_id
                LEFT JOIN servicios s ON s.id = p.servicio_id
                LEFT JOIN facturas f ON f.id = p.factura_id
                LEFT JOIN metodos_pago mp ON mp.id = p.metodo_id";
        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los pagos cuya fecha_pago cae en el rango [desde, hasta].
     * Mismo SELECT enriquecido que findAll() + filtro de rango.
     *
     * B3.15 — Reportes con periodo (filter server-side).
     *
     * IMPORTANTE: `pagos.fecha_pago` es DATETIME, no DATE. El filtro
     * compara contra el rango completo del día:
     *   - desde: 'YYYY-MM-DD 00:00:00' (inclusive)
     *   - hasta: 'YYYY-MM-DD 23:59:59' (inclusive)
     * Así un pago a las 15:30 del día `hasta` entra en el reporte, y
     * un pago a las 00:00 del día `desde` también. Sin esto, comparar
     * strings de fecha contra un DATETIME pierde los pagos del mismo
     * día cuando la hora no es 00:00:00.
     *
     * @param string $desde YYYY-MM-DD (inclusive).
     * @param string $hasta YYYY-MM-DD (inclusive).
     * @return array
     */
    public function findAllByFechaPagoRange(string $desde, string $hasta): array
    {
        $sql = "SELECT p.*, 
                       c.nombre AS nombres, 
                       c.apellido AS apellidos, 
                       c.cedula AS cedula,
                       s.alias AS servicio_alias,
                       f.periodo AS factura_periodo, 
                       mp.nombre AS metodo_nombre
                FROM {$this->table} p
                INNER JOIN clientes c ON c.id = p.cliente_id
                LEFT JOIN servicios s ON s.id = p.servicio_id
                LEFT JOIN facturas f ON f.id = p.factura_id
                LEFT JOIN metodos_pago mp ON mp.id = p.metodo_id
                WHERE p.fecha_pago >= ? AND p.fecha_pago <= ?
                ORDER BY p.fecha_pago DESC, p.id DESC";
        $stmt = $this->prepareAndExecute($sql, [$desde . ' 00:00:00', $hasta . ' 23:59:59']);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los pagos asociados a una factura.
     *
     * @param int $facturaId
     * @return array
     */
    public function findByFacturaId(int $facturaId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE factura_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$facturaId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los pagos realizados por un cliente.
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
     * Obtiene los pagos filtrados por estado.
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
     * Calcula la suma de los montos de los pagos validados para una factura.
     *
     * @param int $facturaId
     * @return float
     */
    public function sumValidadosByFacturaId(int $facturaId): float
    {
        $sql = "SELECT COALESCE(SUM(monto), 0) FROM {$this->table} WHERE factura_id = ? AND estado = 'validado'";
        $stmt = $this->prepareAndExecute($sql, [$facturaId]);
        return (float) $stmt->fetchColumn();
    }

    /**
     * Calcula la suma de los montos de los pagos validados para un conjunto
     * de facturas, en UNA SOLA QUERY (batch). Cierra el N+1 de
     * ReporteService::calcularKPIs.
     *
     * Devuelve un array asociativo [factura_id => total_validado].
     * Las facturas sin pagos validados NO aparecen en el array (el caller
     * debe usar `?? 0.0`).
     *
     * Si el array viene vacío, devuelve [] sin ejecutar query.
     *
     * OWASP ASVS 5.0 §V1.4.3 / §V11.1.4: las queries dentro de loops
     * están prohibidas (CWE-400 Uncontrolled Resource Consumption).
     *
     * @param array<int> $facturaIds
     * @return array<int, float>
     */
    public function sumValidadosByFacturaIds(array $facturaIds): array
    {
        if (empty($facturaIds)) {
            return [];
        }

        $ids = array_values(array_unique(array_map('intval', $facturaIds)));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "SELECT factura_id, COALESCE(SUM(monto), 0) AS total
                FROM {$this->table}
                WHERE factura_id IN ($placeholders) AND estado = 'validado'
                GROUP BY factura_id";

        $stmt = $this->prepareAndExecute($sql, $ids);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $resultado = [];
        foreach ($rows as $row) {
            $resultado[(int) $row['factura_id']] = (float) $row['total'];
        }
        return $resultado;
    }

    /**
     * Obtiene los pagos asociados a un cliente y una factura específicos.
     *
     * @param int $clienteId
     * @param int $facturaId
     * @return array
     */
    public function findByClienteIdAndFacturaId(int $clienteId, int $facturaId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE cliente_id = ? AND factura_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$clienteId, $facturaId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lista paginada de pagos del cliente para el portal, con datos
     * públicos y enriquecidos (factura_periodo, servicio_alias,
     * metodo_nombre).
     *
     * NO incluye nombres/apellidos/cedula (el cliente ya se conoce a
     * sí mismo).
     *
     * A2-B.4.c — endpoint GET api_portal_pagos.php.
     *
     * Filtros opcionales:
     *   - estado: uno de pendiente|validado|rechazado.
     * Los filtros inválidos se ignoran (el caller ya los limpió con
     * `PortalPagoService::limpiarFiltros`).
     *
     * @param int $clienteId
     * @param int $limit
     * @param int $offset
     * @param array $filtros
     * @return array
     */
    public function paginateEnrichedByClienteId(int $clienteId, int $limit, int $offset, array $filtros = []): array
    {
        $sql = "SELECT p.id, p.factura_id, p.servicio_id, p.monto, p.moneda_pago,
                       p.tasa_cambio, p.fecha_pago, p.metodo_id, p.referencia_bancaria,
                       p.comprobante_path, p.estado, p.motivo_rechazo,
                       p.fecha_validacion, p.origen, p.creado_en, p.actualizado_en,
                       f.periodo AS factura_periodo,
                       s.alias AS servicio_alias,
                       mp.nombre AS metodo_nombre
                FROM {$this->table} p
                LEFT JOIN facturas f ON f.id = p.factura_id
                LEFT JOIN servicios s ON s.id = p.servicio_id
                LEFT JOIN metodos_pago mp ON mp.id = p.metodo_id
                WHERE p.cliente_id = ?";

        $params = [$clienteId];

        if (!empty($filtros['estado'])) {
            $sql .= " AND p.estado = ?";
            $params[] = $filtros['estado'];
        }

        $sql .= " ORDER BY p.fecha_pago DESC, p.id DESC LIMIT ? OFFSET ?";

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
     * Cuenta el total de pagos del cliente con los mismos filtros que
     * `paginateEnrichedByClienteId`.
     *
     * A2-B.4.c — endpoint GET api_portal_pagos.php.
     *
     * @param int $clienteId
     * @param array $filtros
     * @return int
     */
    public function countByClienteId(int $clienteId, array $filtros = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE cliente_id = ?";
        $params = [$clienteId];

        if (!empty($filtros['estado'])) {
            $sql .= " AND estado = ?";
            $params[] = $filtros['estado'];
        }

        $stmt = $this->prepareAndExecute($sql, $params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Devuelve un pago del cliente (con datos públicos) o `null` si no
     * existe o no pertenece a ese cliente.
     *
     * Defensa BOLA/IDOR (OWASP API Security Top 10 2023, API1:2023).
     * Si el pago existe pero es de otro cliente, retorna `null`. El
     * controller lo convierte en 404 (no 403, para no filtrar existencia).
     *
     * A2-B.4.c — endpoint GET api_portal_pagos.php?id=N.
     *
     * @param int $id
     * @param int $clienteId
     * @return array|null
     */
    public function findEnrichedByIdAndClienteId(int $id, int $clienteId): ?array
    {
        $sql = "SELECT p.id, p.factura_id, p.servicio_id, p.monto, p.moneda_pago,
                       p.tasa_cambio, p.fecha_pago, p.metodo_id, p.referencia_bancaria,
                       p.comprobante_path, p.estado, p.motivo_rechazo,
                       p.fecha_validacion, p.origen, p.creado_en, p.actualizado_en,
                       f.periodo AS factura_periodo,
                       s.alias AS servicio_alias,
                       mp.nombre AS metodo_nombre
                FROM {$this->table} p
                LEFT JOIN facturas f ON f.id = p.factura_id
                LEFT JOIN servicios s ON s.id = p.servicio_id
                LEFT JOIN metodos_pago mp ON mp.id = p.metodo_id
                WHERE p.id = ? AND p.cliente_id = ?
                LIMIT 1";

        $stmt = $this->prepareAndExecute($sql, [$id, $clienteId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}