<?php
// scripts/update_bcv_rate.php
declare(strict_types=1);

require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Core\Bootstrap;
use App\Services\TasaBcvService;

function registrarLogBcv(string $mensaje): void
{
    $directorio = __DIR__ . '/../storage/logs';
    if (!is_dir($directorio) && !mkdir($directorio, 0750, true) && !is_dir($directorio)) {
        fwrite(STDERR, '[BCV] No se pudo crear el directorio privado de logs.' . PHP_EOL);
        return;
    }

    $ruta = $directorio . '/bcv_update.log';
    $registro = '[' . date(DATE_ATOM) . '] ' . $mensaje . PHP_EOL;
    if (file_put_contents($ruta, $registro, FILE_APPEND | LOCK_EX) === false) {
        fwrite(STDERR, '[BCV] No se pudo escribir el log privado de actualización.' . PHP_EOL);
    }
}

$service = null;

try {
    $pdo = Bootstrap::init();
    $service = new TasaBcvService($pdo);
    $tasa = $service->actualizarDesdeFuenteOficial();

    $message = sprintf(
        '[BCV] Actualización correcta: Fecha Valor %s; USD/Bs %s.',
        $tasa['fecha_valor'],
        $tasa['tasa']
    );
    fwrite(STDOUT, $message . PHP_EOL);
    exit(0);
} catch (\Throwable $e) {
    if ($service instanceof TasaBcvService) {
        try {
            $service->registrarFalloDeConsulta();
        } catch (\Throwable $estadoError) {
            registrarLogBcv('[BCV] No se pudo guardar el estado de consulta fallida (' . get_class($estadoError) . ').');
        }
    }

    if ($e instanceof \PDOException) {
        $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());
        $reason = 'Error de base de datos (SQLSTATE ' . $sqlState . ').';
    } elseif ($e instanceof \RuntimeException) {
        $reason = $e->getMessage();
    } else {
        $reason = 'Error inesperado (' . get_class($e) . ').';
    }

    $message = '[BCV] Falló la actualización: ' . $reason;
    registrarLogBcv($message);
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}
