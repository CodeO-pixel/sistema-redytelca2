<?php
// tests/run.php
// Ejecuta todos los archivos *.test.php en tests/. Uso: php tests/run.php

require __DIR__ . '/assert.php';

$testFiles = glob(__DIR__ . '/*.test.php');
sort($testFiles);

if (empty($testFiles)) {
    echo "No hay tests (*.test.php) en tests/\n";
    exit(0);
}

$totalPassed = 0;
$totalFailed = 0;

foreach ($testFiles as $file) {
    $basename = basename($file);
    echo "\n▶ $basename\n";

    $tests = require $file;
    if (!is_array($tests)) {
        echo "  ⚠ El archivo no devolvió un array de tests. Saltando.\n";
        continue;
    }

    foreach ($tests as $name => $fn) {
        try {
            $fn();
            echo "  ✓ $name\n";
            $totalPassed++;
        } catch (TestFailure $e) {
            echo "  ✗ $name\n    " . $e->getMessage() . "\n";
            $totalFailed++;
        } catch (Throwable $e) {
            echo "  ✗ $name (error inesperado: " . get_class($e) . ")\n    " . $e->getMessage() . "\n";
            $totalFailed++;
        }
    }
}

echo "\n────────────────────────\n";
echo "Resultado: $totalPassed pasaron, $totalFailed fallaron\n";

exit($totalFailed === 0 ? 0 : 1);