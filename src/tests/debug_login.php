<?php
require_once __DIR__ . '/../core/Bootstrap.php';
$pdo = \App\Core\Bootstrap::init();
try {
    $r = (new \App\Services\AuthService($pdo))->login('admin', 'admin123', '127.0.0.1', 'cli');
    echo 'OK: ' . json_encode($r) . PHP_EOL;
} catch (\Throwable $e) {
    echo get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
    if ($e instanceof \PDOException) {
        echo 'errorInfo: ' . var_export($e->errorInfo, true) . PHP_EOL;
    }
    echo $e->getTraceAsString() . PHP_EOL;
}
