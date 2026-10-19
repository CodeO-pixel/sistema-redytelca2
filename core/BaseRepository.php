<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Clase base para los repositorios de acceso a datos.
 */
abstract class BaseRepository
{
    /**
     * @var \PDO Conexión a la base de datos.
     */
    protected \PDO $pdo;

    /**
     * @var string Nombre de la tabla asociada al repositorio.
     */
    protected string $table = '';

    /**
     * @var string Nombre de la clave primaria de la tabla.
     */
    protected string $primaryKey = 'id';

    /**
     * Constructor del repositorio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Prepara y ejecuta una consulta SQL.
     *
     * @param string $sql Consulta SQL con placeholders.
     * @param array $params Parámetros para la consulta.
     * @return \PDOStatement
     */
    protected function prepareAndExecute(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Busca un registro por su clave primaria.
     *
     * @param int $id Identificador del registro.
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Obtiene todos los registros de la tabla.
     *
     * @return array
     */
    public function findAll(): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Inserta un nuevo registro en la tabla.
     *
     * @param array $data Arreglo asociativo con los datos a insertar.
     * @return int ID del registro insertado.
     * @throws \InvalidArgumentException Si el arreglo de datos está vacío.
     */
    public function create(array $data): int
    {
        if (empty($data)) {
            throw new \InvalidArgumentException("No hay datos para insertar.");
        }

        $columns = array_keys($data);
        $colsString = implode(', ', array_map(fn($col) => "`$col`", $columns));
        $placesString = implode(', ', array_fill(0, count($columns), '?'));

        $sql = "INSERT INTO {$this->table} ($colsString) VALUES ($placesString)";
        $this->prepareAndExecute($sql, array_values($data));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza un registro existente por su clave primaria.
     *
     * IMPORTANTE: la verificación final usa exists() y no rowCount().
     * En MySQL, rowCount() devuelve 0 cuando el UPDATE se ejecuta pero
     * los valores son idénticos a los que ya tenía el registro. Eso daría
     * un falso negativo cuando el cliente guarda sin cambios.
     *
     * @param int $id Identificador del registro.
     * @param array $data Arreglo asociativo con los datos a actualizar.
     * @return bool True si el registro existe (post-update).
     * @throws \InvalidArgumentException Si el arreglo de datos está vacío.
     */
    public function update(int $id, array $data): bool
    {
        if (empty($data)) {
            throw new \InvalidArgumentException("No hay datos para actualizar.");
        }

        $columns = array_keys($data);
        $setString = implode(', ', array_map(fn($col) => "`$col` = ?", $columns));

        $sql = "UPDATE {$this->table} SET $setString WHERE {$this->primaryKey} = ?";

        $params = array_values($data);
        $params[] = $id;

        $this->prepareAndExecute($sql, $params);

        return $this->exists($id);
    }

    /**
     * Elimina un registro por su clave primaria.
     *
     * @param int $id Identificador del registro.
     * @return bool True si se eliminó al menos un registro.
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->prepareAndExecute($sql, [$id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Verifica si existe un registro con la clave primaria indicada.
     *
     * @param int $id Identificador del registro.
     * @return bool
     */
    public function exists(int $id): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1";
        $stmt = $this->prepareAndExecute($sql, [$id]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Cuenta el total de registros en la tabla.
     *
     * @return int
     */
    public function countAll(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $stmt = $this->pdo->query($sql);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Obtiene el nombre de la tabla.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Obtiene el nombre de la clave primaria.
     *
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * Ejecuta una función dentro de una transacción de base de datos.
     *
     * @param callable $fn Función a ejecutar.
     * @return mixed Resultado de la función.
     * @throws \Throwable
     */
    public function transaction(callable $fn): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $fn();
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}