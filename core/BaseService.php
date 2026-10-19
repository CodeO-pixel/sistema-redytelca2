<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Clase base para los servicios de lógica de negocio.
 */
abstract class BaseService
{
    /**
     * @var \PDO Conexión a la base de datos.
     */
    protected \PDO $pdo;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Ejecuta una función dentro de una transacción de base de datos.
     *
     * @param callable $fn Función a ejecutar.
     * @return mixed Resultado de la función.
     * @throws \Throwable
     */
    protected function transaction(callable $fn): mixed
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

    /**
     * Obtiene la instancia de la conexión PDO.
     *
     * @return \PDO
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }
}