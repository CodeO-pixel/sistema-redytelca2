<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Clase Config
 *
 * Gestiona la carga de configuración desde variables de entorno o archivo .env.
 * Cumple con el principio de 12-Factor App (Configuración en el entorno).
 */
final class Config
{
    /**
     * Constructor privado para evitar instanciación.
     */
    private function __construct()
    {
    }

    /**
     * Carga la configuración de la base de datos.
     *
     * @return array Arreglo asociativo con las credenciales y configuración.
     */
    public static function load(): array
    {
        $envPath = __DIR__ . '/../../.env';
        if (is_file($envPath)) {
            self::loadEnvFile($envPath);
        }

        return [
            'host' => self::env('DB_HOST', 'localhost'),
            'dbname' => self::env('DB_NAME', 'bd_redytelca'),
            'user' => self::env('DB_USER', 'root'),
            'pass' => self::env('DB_PASS', ''),
            'charset' => self::env('DB_CHARSET', 'utf8mb4'),
        ];
    }

    /**
     * Parsea un archivo .env y carga las variables en el entorno si no existen.
     * No sobreescribe variables ya presentes en $_ENV ni en getenv().
     *
     * @param string $path Ruta al archivo .env.
     * @return void
     */
    private static function loadEnvFile(string $path): void
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Ignorar líneas vacías o comentarios
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);

            // Quitar comillas envolventes simples o dobles del valor
            if (strlen($value) > 1 && ($value[0] === '"' || $value[0] === "'") && $value[0] === $value[strlen($value) - 1]) {
                $value = substr($value, 1, -1);
            }

            // No sobreescribir variables ya presentes en $_ENV ni en getenv().
            // El chequeo doble cubre servidores con variables_order sin "E"
            // (que dejan $_ENV vacío aunque el SO sí tenga la variable).
            if (!array_key_exists($key, $_ENV) && getenv($key) === false) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

    /**
     * Obtiene el valor de una variable de entorno.
     *
     * @param string $key Nombre de la variable.
     * @param string|null $default Valor por defecto si no se encuentra.
     * @return string
     * @throws \RuntimeException Si la variable no existe y no hay valor por defecto.
     */
    private static function env(string $key, ?string $default = null): string
    {
        if (array_key_exists($key, $_ENV)) {
            return (string) $_ENV[$key];
        }

        $val = getenv($key);
        if ($val !== false) {
            return (string) $val;
        }

        if ($default !== null) {
            return $default;
        }

        throw new \RuntimeException("Variable de entorno faltante: $key");
    }
}