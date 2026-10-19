<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Clase Http
 *
 * Gestiona la inicialización del entorno HTTP, incluyendo CORS y zona horaria.
 */
final class Http
{
    /**
     * Constructor privado para evitar instanciación.
     */
    private function __construct()
    {
    }

    /**
     * Inicializa el entorno HTTP y retorna la conexión PDO.
     *
     * @return \PDO
     */
    public static function init(): \PDO
    {
        $allowedOriginsEnv = getenv('REDYTELCA_ALLOWED_ORIGINS');
        $allowedOrigins = $allowedOriginsEnv !== false && trim($allowedOriginsEnv) !== ''
            ? array_map('trim', explode(',', $allowedOriginsEnv))
            : ['http://localhost', 'http://localhost:80', 'http://127.0.0.1', 'http://127.0.0.1:80'];

        if (php_sapi_name() !== 'cli') {
            $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

            if ($requestOrigin !== '' && in_array($requestOrigin, $allowedOrigins, true)) {
                header('Access-Control-Allow-Origin: ' . $requestOrigin);
            } elseif (empty($allowedOriginsEnv) && $requestOrigin === '') {
                header('Access-Control-Allow-Origin: ' . $allowedOrigins[0]);
            }

            header('Access-Control-Allow-Headers: Content-Type, X-Session-Token');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Vary: Origin');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');

            if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                http_response_code(204);
                exit;
            }
        }

        date_default_timezone_set('UTC');

        return Bootstrap::init();
    }
}