<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Clase Bootstrap
 *
 * Punto de arranque y autoloader PSR-4 del proyecto REDYTELCA.
 */
final class Bootstrap
{
    /**
     * @var bool Bandera para evitar registrar el autoloader múltiples veces.
     */
    private static bool $autoloaderRegistered = false;

    /**
     * Constructor privado para evitar instanciación.
     */
    private function __construct()
    {
    }

    /**
     * Registra el autoloader PSR-4 del proyecto.
     * Es idempotente: si ya está registrado, no hace nada.
     *
     * @return void
     */
    public static function registerAutoloader(): void
    {
        if (self::$autoloaderRegistered) {
            return;
        }

        spl_autoload_register(function (string $class) {
            $file = self::resolveNamespace($class);
            if ($file !== null && is_file($file)) {
                require $file;
            }
        });

        self::$autoloaderRegistered = true;
    }

    /**
     * Inicializa el kernel de la aplicación.
     * Fija la zona horaria, registra el autoloader, carga la configuración
     * y establece la conexión a la base de datos.
     *
     * @return PDO
     * @see \App\Core\Config::load()
     */
    public static function init(): PDO
    {
        // 1. Fijar zona horaria UTC
        date_default_timezone_set('UTC');

        // 2. Registrar el autoloader
        self::registerAutoloader();

        // 3. Cargar configuración
        $config = \App\Core\Config::load();

        // 4. Conectar a la base de datos
        $pdo = \App\Core\Database::connect($config);

        // 5. Sincronizar la zona horaria de la sesión de base de datos
        $pdo->exec("SET time_zone = '+00:00'");

        // 6. Retornar la instancia PDO
        return $pdo;
    }

    /**
     * Traduce un Fully Qualified Class Name (FQCN) a su ruta física en el sistema.
     *
     * @param string $class El nombre completo de la clase con su namespace.
     * @return string|null La ruta absoluta al archivo, o null si no pertenece a los prefijos mapeados.
     */
    private static function resolveNamespace(string $class): ?string
    {
        // Mapa PSR-4 de prefijos de namespace a directorios base
        $prefixes = [
            'App\\Core\\' => 'core/',
            'App\\Exceptions\\' => 'core/Exceptions/',
            'App\\Models\\' => 'models/',
            'App\\Repositories\\' => 'repositories/',
            'App\\Services\\' => 'services/',
            'App\\Controllers\\' => 'controllers/',
            'App\\Validators\\' => 'validators/',
        ];

        foreach ($prefixes as $prefix => $baseDir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) === 0) {
                $relativeClass = substr($class, $len);
                // __DIR__ es 'core', por lo que __DIR__ . '/../' apunta a la raíz del proyecto
                return __DIR__ . '/../' . $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            }
        }

        return null;
    }
}

\App\Core\Bootstrap::registerAutoloader();