<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

final class TasaBcvRepository extends BaseRepository
{
    protected string $table = 'tasas_bcv';

    protected string $primaryKey = 'id';

    public function obtenerUltima(bool $forUpdate = false): ?array
    {
        $sql = "SELECT id, fecha_valor, tasa, consultado_en, estado_consulta,
                       (consultado_en >= UTC_TIMESTAMP() - INTERVAL 24 HOUR) AS consulta_reciente
                FROM {$this->table}
                ORDER BY fecha_valor DESC
                LIMIT 1";
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'fecha_valor' => (string) $row['fecha_valor'],
            'tasa' => (string) $row['tasa'],
            'consultado_en' => (string) $row['consultado_en'],
            'estado_consulta' => (string) $row['estado_consulta'],
            'consulta_reciente' => (bool) $row['consulta_reciente'],
        ];
    }

    public function obtenerDisponible(bool $forUpdate = false): ?array
    {
        $row = $this->obtenerUltima($forUpdate);
        if (
            $row === null
            || $row['estado_consulta'] !== 'disponible'
            || !$row['consulta_reciente']
        ) {
            return null;
        }

        return [
            'fecha_valor' => $row['fecha_valor'],
            'tasa' => $row['tasa'],
        ];
    }

    public function guardar(string $fechaValor, string $tasa): void
    {
        $ultima = $this->obtenerUltima(true);
        if ($ultima !== null && $fechaValor < $ultima['fecha_valor']) {
            throw new \RuntimeException('El BCV devolvió una Fecha Valor anterior a la última guardada.');
        }

        $sql = "INSERT INTO {$this->table} (fecha_valor, tasa, consultado_en, estado_consulta)
                VALUES (?, ?, UTC_TIMESTAMP(), 'disponible')
                ON DUPLICATE KEY UPDATE
                    tasa = VALUES(tasa),
                    consultado_en = UTC_TIMESTAMP(),
                    estado_consulta = 'disponible'";
        $this->prepareAndExecute($sql, [$fechaValor, $tasa]);
    }

    public function marcarConsultaFallida(): void
    {
        $ultima = $this->obtenerUltima(true);
        if ($ultima === null) {
            return;
        }

        $sql = "UPDATE {$this->table}
                SET consultado_en = UTC_TIMESTAMP(), estado_consulta = 'indisponible'
                WHERE id = ?";
        $this->prepareAndExecute($sql, [$ultima['id']]);
    }
}
