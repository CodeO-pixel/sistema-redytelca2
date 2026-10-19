<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Sesion.
 */
final class SesionRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'sesiones';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Busca una sesión por su token.
     *
     * @param string $token
     * @return array|null
     */
    public function findByToken(string $token): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE token = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$token]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Busca una sesión vigente por su token.
     *
     * Filtra por expira_en > NOW() y revocado_en IS NULL.
     *
     * NOTA: este filtro es más estricto que el de requireStaffAuth /
     * requireClientAuth (core/Auth.php), que solo validan
     * expira_en > NOW() sin mirar revocado_en. El service de auth
     * (spec 137) debe decidir explícitamente si alinearse con este
     * repo o con los endpoints viejos. En el flujo actual no hay
     * divergencia porque logout setea ambos campos a la vez.
     *
     * @param string $token
     * @return array|null
     */
    public function findVigenteByToken(string $token): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE token = ? AND expira_en > NOW() AND revocado_en IS NULL LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$token]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Obtiene las últimas 20 sesiones de un usuario staff.
     *
     * @param int $usuarioId
     * @return array
     */
    public function findByUsuarioId(int $usuarioId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE usuario_id = ? ORDER BY creado_en DESC LIMIT 20";
        $stmt = $this->prepareAndExecute($sql, [$usuarioId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las sesiones de una credencial de cliente.
     *
     * @param int $credencialId
     * @return array
     */
    public function findByCredencialId(int $credencialId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE credencial_id = ?";
        $stmt = $this->prepareAndExecute($sql, [$credencialId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Revoca una sesión por su token.
     *
     * IMPORTANTE: verifica existencia por token (no rowCount).
     * rowCount() devuelve 0 cuando la sesión ya estaba revocada con
     * los mismos valores (mismo bug de docs/VERIFICACION.md §2.7).
     * Un doble logout produciría un falso negativo.
     *
     * GREATEST evita violar chk_sesion_expira (CHECK expira_en > creado_en)
     * cuando login y logout caen en el mismo segundo. En el caso normal
     * (>=1s de diferencia) sigue siendo invalidación inmediata; en el
     * caso mismo-segundo, el token queda inerte al siguiente segundo.
     *
     * @param string $token
     * @param string $motivo
     * @return bool True si el token existe en BD (post-UPDATE).
     */
    public function revocarByToken(string $token, string $motivo): bool
    {
        $sql = "UPDATE {$this->table} SET expira_en = GREATEST(UTC_TIMESTAMP(), DATE_ADD(creado_en, INTERVAL 1 SECOND)), revocado_en = UTC_TIMESTAMP(), motivo_cierre = ? WHERE token = ?";
        $this->prepareAndExecute($sql, [$motivo, $token]);

        return $this->findByToken($token) !== null;
    }

    /**
     * Elimina las sesiones expiradas de un usuario.
     *
     * @param int $usuarioId
     * @param int $dias
     * @return int
     */
    public function deleteExpiradasByUsuarioId(int $usuarioId, int $dias): int
    {
        $sql = "DELETE FROM {$this->table} WHERE usuario_id = ? AND expira_en < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $this->prepareAndExecute($sql, [$usuarioId, $dias]);
        return $stmt->rowCount();
    }

    /**
     * Revoca TODAS las sesiones no revocadas de un usuario.
     *
     * Se usa en la desactivación de un usuario (baja lógica). A
     * diferencia de findByUsuarioId, no limita a 20 filas.
     *
     * GREATEST evita violar chk_sesion_expira (CHECK expira_en > creado_en)
     * cuando la sesión fue creada en el mismo segundo que la
     * desactivación. Ver revocarByToken para el trade-off conocido.
     *
     * @param int $usuarioId
     * @return int Número de sesiones revocadas.
     */
    public function revocarTodasByUsuarioId(int $usuarioId): int
    {
        $sql = "UPDATE {$this->table} SET expira_en = GREATEST(UTC_TIMESTAMP(), DATE_ADD(creado_en, INTERVAL 1 SECOND)), revocado_en = UTC_TIMESTAMP(), motivo_cierre = 'revocada_por_admin' WHERE usuario_id = ? AND revocado_en IS NULL";
        $stmt = $this->prepareAndExecute($sql, [$usuarioId]);
        return $stmt->rowCount();
    }
}