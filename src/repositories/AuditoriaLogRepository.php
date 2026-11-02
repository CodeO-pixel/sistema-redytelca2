<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;
use PDO;

/**
 * Repositorio para la entidad de solo lectura AuditoriaLog.
 */
final class AuditoriaLogRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'auditoria_logs';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Obtiene una lista paginada de logs de auditoría aplicando filtros opcionales.
     *
     * Incluye `actor_username` (JOIN con usuarios) para mostrar el nombre
     * legible del staff que ejecutó la acción. Si `tipo_actor='cliente'`,
     * el nombre se resuelve por `credencial_id` (tabla no joineada en esta
     * versión; se extiende cuando exista el portal, Fase 5). Si
     * `tipo_actor='sistema'`, no hay usuario ni credencial.
     *
     * @param array $filtros Filtros a aplicar (tabla, accion, desde, hasta).
     * @param int $page Número de página actual.
     * @param int $perPage Cantidad de registros por página.
     * @return array Arreglo con 'items' y 'total'.
     */
    public function paginarConFiltros(array $filtros, int $page, int $perPage): array
    {
        $where = [];
        $params = [];

        if (!empty($filtros['tabla'])) {
            $where[] = "al.tabla_afectada = ?";
            $params[] = $filtros['tabla'];
        }

        if (!empty($filtros['accion'])) {
            $where[] = "al.accion = ?";
            $params[] = $filtros['accion'];
        }

        if (!empty($filtros['desde'])) {
            $where[] = "al.creado_en >= ?";
            $params[] = $filtros['desde'] . ' 00:00:00';
        }

        if (!empty($filtros['hasta'])) {
            $where[] = "al.creado_en <= ?";
            $params[] = $filtros['hasta'] . ' 23:59:59';
        }

        $whereClause = '';
        if (count($where) > 0) {
            $whereClause = 'WHERE ' . implode(' AND ', $where);
        }

        // Consulta para contar el total de registros que coinciden con los filtros.
        $countSql = "SELECT COUNT(*) FROM {$this->table} al $whereClause";
        $stmtCount = $this->pdo->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int) $stmtCount->fetchColumn();

        // Consulta para obtener los registros paginados con el username del actor.
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT al.*, u.username AS actor_username
                FROM {$this->table} al
                LEFT JOIN usuarios u ON u.id = al.usuario_id
                $whereClause
                ORDER BY al.creado_en DESC, al.id DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->pdo->prepare($sql);

        $paramIndex = 1;
        foreach ($params as $param) {
            $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
        }

        $stmt->bindValue($paramIndex++, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex, $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'items' => $items,
            'total' => $total
        ];
    }
}