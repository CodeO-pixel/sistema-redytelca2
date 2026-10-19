<?php
declare(strict_types=1);

namespace App\Core;

/**
* Clase Database
*
* Wrapper para la creación y configuración de la conexión PDO a MariaDB.
*/
final class Database
{
/**
* Constructor privado para evitar instanciación.
*/
private function __construct()
{
}

/**
* Crea y retorna una conexión PDO configurada.
*
* @param array $config Arreglo con las claves: host, dbname, user, pass, charset.
* @return \PDO
* @throws \InvalidArgumentException Si falta alguna clave en la configuración.
* @throws \RuntimeException Si ocurre un error al conectar a la base de datos.
*/
public static function connect(array $config): \PDO
{
$requiredKeys = ['host', 'dbname', 'user', 'pass', 'charset'];

foreach ($requiredKeys as $key) {
if (!array_key_exists($key, $config)) {
throw new \InvalidArgumentException("Configuración de BD incompleta: falta '$key'.");
}
}

$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";

try {
$pdo = new \PDO($dsn, $config['user'], $config['pass']);

$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
$pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

return $pdo;
} catch (\PDOException $e) {
// Se lanza una excepción genérica para no exponer credenciales o detalles de la infraestructura.
// La excepción original se pasa como $previous para que quede registrada en los logs internos.
throw new \RuntimeException('Error de conexión a la base de datos.', 0, $e);
}
}
}