<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Usuario.
 */
final class UsuarioRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'usuarios';

    /** @var string */
    protected string $primaryKey = 'id';

    /**
     * Busca un usuario por su nombre de usuario.
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
     * Busca un usuario por su correo electrónico.
     *
     * @param string $email
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$email]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Obtiene todos los usuarios ordenados por nombre de usuario ascendente.
     *
     * @return array
     */
    public function findAllOrderedByUsername(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY username ASC";
        $stmt = $this->prepareAndExecute($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los usuarios con su última fecha de conexión
     * (MAX de sesiones.creado_en), ordenados por username ASC.
     * Excluye estado='Inactivo' (baja lógica invisible al listado).
     *
     * @return array
     */
    public function findAllWithUltimaConexion(): array
    {
        $sql = "SELECT u.id, u.username, u.email, u.must_change_password,
                u.email_verified, u.estado,
                (SELECT MAX(creado_en) FROM sesiones WHERE usuario_id = u.id) AS ultima_conexion
                FROM {$this->table} u
                WHERE u.estado <> 'Inactivo'
                ORDER BY u.username ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los usuarios filtrados por estado.
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
}