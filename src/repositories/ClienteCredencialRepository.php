<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad ClienteCredencial.
 */
final class ClienteCredencialRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'clientes_credenciales';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Busca una credencial por su nombre de usuario.
     *
     * @param string $username
     * @return array|null
     */
    public function findByUsername(string $username): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE username = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$username]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Busca la credencial asociada a un cliente.
     *
     * @param int $clienteId
     * @return array|null
     */
    public function findByClienteId(int $clienteId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE cliente_id = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$clienteId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Actualiza la fecha de último acceso de una credencial.
     *
     * @param int $id
     * @return bool
     */
    public function updateLastAccess(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET ultimo_acceso = NOW() WHERE {$this->primaryKey} = ?";
        $stmt = $this->prepareAndExecute($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Reinicia el contador de intentos fallidos y desbloquea la cuenta.
     *
     * @param int $id
     * @return bool
     */
    public function resetFailedAttempts(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE {$this->primaryKey} = ?";
        $stmt = $this->prepareAndExecute($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Incrementa el contador de intentos fallidos y opcionalmente bloquea la cuenta.
     *
     * @param int $id
     * @param string|null $lockUntil Fecha y hora hasta la cual se bloquea la cuenta (formato ISO).
     * @return bool
     */
    public function incrementFailedAttempts(int $id, ?string $lockUntil = null): bool
    {
        if ($lockUntil !== null) {
            $sql = "UPDATE {$this->table} SET intentos_fallidos = intentos_fallidos + 1, bloqueado_hasta = ? WHERE {$this->primaryKey} = ?";
            $stmt = $this->prepareAndExecute($sql, [$lockUntil, $id]);
        } else {
            $sql = "UPDATE {$this->table} SET intentos_fallidos = intentos_fallidos + 1 WHERE {$this->primaryKey} = ?";
            $stmt = $this->prepareAndExecute($sql, [$id]);
        }

        return $stmt->rowCount() > 0;
    }
}