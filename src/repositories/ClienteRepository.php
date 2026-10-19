<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Cliente.
 */
final class ClienteRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'clientes';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Busca un cliente por su cédula.
     *
     * @param string $cedula
     * @return array|null
     */
    public function findByCedula(string $cedula): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE cedula = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$cedula]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Busca un cliente por su correo electrónico.
     *
     * @param string $correo
     * @return array|null
     */
    public function findByCorreo(string $correo): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE correo = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$correo]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Obtiene una lista paginada de clientes con soporte para búsqueda.
     *
     * @param string $filter Texto de búsqueda.
     * @param int $limit Límite de resultados.
     * @param int $offset Desplazamiento.
     * @return array
     */
    public function paginateWithSearch(string $filter, int $limit, int $offset): array
    {
        $sql = "SELECT c.*,
                       COALESCE(ds.calle, '') AS direccion,
                       s.id AS servicio_id_principal,
                       s.estado_comercial,
                       na.codigo AS nap_codigo,
                       o.codigo AS olt_codigo,
                       n.nombre AS nodo_nombre,
                       (SELECT COUNT(*) FROM servicios sc WHERE sc.cliente_id = c.id) AS total_servicios
                FROM clientes c
                LEFT JOIN servicios s ON s.id = (
                    SELECT s2.id FROM servicios s2
                    WHERE s2.cliente_id = c.id
                    ORDER BY s2.id ASC LIMIT 1
                )
                LEFT JOIN direccion_servicio ds ON ds.id = s.direccion_id
                LEFT JOIN naps na ON na.id = s.nap_id
                LEFT JOIN olts o ON o.id = na.olt_id
                LEFT JOIN nodos n ON n.id = o.nodo_id";

        $params = [];
        if ($filter !== '') {
            $sql .= " WHERE c.nombre LIKE ? OR c.apellido LIKE ? OR c.cedula LIKE ? OR c.correo LIKE ?
                      OR EXISTS (
                          SELECT 1 FROM servicios sx
                          LEFT JOIN direccion_servicio dsx ON dsx.id = sx.direccion_id
                          LEFT JOIN naps nax ON nax.id = sx.nap_id
                          LEFT JOIN olts ox ON ox.id = nax.olt_id
                          WHERE sx.cliente_id = c.id
                            AND (dsx.calle LIKE ? OR COALESCE(nax.codigo, '') LIKE ? OR COALESCE(ox.codigo, '') LIKE ?)
                      )";
            $like = "%$filter%";
            $params = array_fill(0, 7, $like);
        }

        $sql .= " ORDER BY c.fecha_registro DESC LIMIT ? OFFSET ?";

        $stmt = $this->pdo->prepare($sql);
        $paramIndex = 1;
        foreach ($params as $param) {
            $stmt->bindValue($paramIndex++, $param, \PDO::PARAM_STR);
        }
        $stmt->bindValue($paramIndex++, $limit, \PDO::PARAM_INT);
        $stmt->bindValue($paramIndex, $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta el total de clientes que coinciden con el filtro de búsqueda.
     *
     * @param string $filter Texto de búsqueda.
     * @return int
     */
    public function countWithSearch(string $filter): int
    {
        $sql = "SELECT COUNT(*) FROM clientes c";
        $params = [];

        if ($filter !== '') {
            $sql .= " WHERE c.nombre LIKE ? OR c.apellido LIKE ? OR c.cedula LIKE ? OR c.correo LIKE ?
                      OR EXISTS (
                          SELECT 1 FROM servicios sx
                          LEFT JOIN direccion_servicio dsx ON dsx.id = sx.direccion_id
                          LEFT JOIN naps nax ON nax.id = sx.nap_id
                          LEFT JOIN olts ox ON ox.id = nax.olt_id
                          WHERE sx.cliente_id = c.id
                            AND (dsx.calle LIKE ? OR COALESCE(nax.codigo, '') LIKE ? OR COALESCE(ox.codigo, '') LIKE ?)
                      )";
            $like = "%$filter%";
            $params = array_fill(0, 7, $like);
        }

        $stmt = $this->prepareAndExecute($sql, $params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Asocia un teléfono a un cliente.
     *
     * @param int $clienteId
     * @param int $telefonoId
     * @return bool
     */
    public function attachTelefono(int $clienteId, int $telefonoId): bool
    {
        $sql = "INSERT IGNORE INTO r_cliente_telefono (cliente_id, telefono_id) VALUES (?, ?)";
        $stmt = $this->prepareAndExecute($sql, [$clienteId, $telefonoId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Desvincula un teléfono de un cliente.
     *
     * @param int $clienteId
     * @param int $telefonoId
     * @return bool
     */
    public function detachTelefono(int $clienteId, int $telefonoId): bool
    {
        $sql = "DELETE FROM r_cliente_telefono WHERE cliente_id = ? AND telefono_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$clienteId, $telefonoId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Obtiene los teléfonos asociados a un cliente.
     *
     * @param int $clienteId
     * @return array
     */
    public function getTelefonos(int $clienteId): array
    {
        $sql = "SELECT t.id, t.numero
                FROM telefono t
                INNER JOIN r_cliente_telefono rct ON rct.telefono_id = t.id
                WHERE rct.cliente_id = ?
                ORDER BY t.id";
        $stmt = $this->prepareAndExecute($sql, [$clienteId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}