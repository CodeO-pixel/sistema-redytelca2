<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Clase base para los controladores de la aplicación.
 */
abstract class BaseController
{
    /**
     * @var \PDO Conexión a la base de datos.
     */
    protected \PDO $pdo;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Maneja la petición HTTP y la enruta al método correspondiente.
     *
     * @param string|null $method Método HTTP (opcional, por defecto lee de $_SERVER).
     * @return void
     * @throws \App\Exceptions\DomainException
     */
    public function handle(?string $method = null): void
    {
        $method = strtoupper($method ?? $_SERVER['REQUEST_METHOD'] ?? 'GET');

        $actionMap = [
            'GET' => 'index',
            'POST' => 'store',
            'PUT' => 'update',
            'DELETE' => 'destroy',
        ];

        if (!array_key_exists($method, $actionMap)) {
            throw new \App\Exceptions\DomainException("Método HTTP no soportado: $method");
        }

        $action = $actionMap[$method];

        if (!method_exists($this, $action)) {
            throw new \App\Exceptions\DomainException("Acción no implementada: $action");
        }

        $this->$action();
    }

    /**
     * Obtiene los datos de entrada de la petición (JSON o form-data).
     *
     * @return array
     */
    public function input(): array
    {
        $input = file_get_contents('php://input');
        $json = json_decode($input !== false ? $input : '', true);

        if (!is_array($json) || empty($json)) {
            return $_POST ?: [];
        }

        return $json;
    }

    /**
     * Emite una respuesta JSON y termina la ejecución.
     *
     * @param array $data Datos a enviar.
     * @param int $status Código de estado HTTP.
     * @return void
     */
    protected function render(array $data, int $status = 200): void
    {
        \App\Core\Response::json($data, $status);
    }

    /**
     * Maneja una excepción y emite la respuesta HTTP correspondiente.
     *
     * @param \Throwable $e Excepción capturada.
     * @return void
     */
    public function handleException(\Throwable $e): void
    {
        \App\Core\Response::fromException($e);
    }
}