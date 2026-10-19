<?php
// tests/assert.php
// Harness de aserciones mínimo. Sin dependencias, sin Composer.

class TestFailure extends Exception {}

function assertSame($expected, $actual, $message = ''): void {
    if ($expected !== $actual) {
        throw new TestFailure(
            ($message ? "$message — " : '') .
            "Esperado: " . var_export($expected, true) .
            " | Recibido: " . var_export($actual, true)
        );
    }
}

function assertTrue($actual, $message = ''): void {
    assertSame(true, $actual, $message);
}

function assertFalse($actual, $message = ''): void {
    assertSame(false, $actual, $message);
}

function assertNull($actual, $message = ''): void {
    assertSame(null, $actual, $message);
}

function assertNotNull($actual, $message = ''): void {
    if ($actual === null) {
        throw new TestFailure(($message ? "$message — " : '') . "Se esperaba un valor no nulo");
    }
}

function assertContains($needle, $haystack, $message = ''): void {
    if (strpos((string) $haystack, (string) $needle) === false) {
        throw new TestFailure(
            ($message ? "$message — " : '') .
            "Se esperaba encontrar '" . $needle . "' en '" . $haystack . "'"
        );
    }
}

function assertThrows(callable $fn, string $exceptionClass = Exception::class, $message = ''): void {
    try {
        $fn();
    } catch (Throwable $e) {
        if ($e instanceof $exceptionClass) {
            return;
        }
        throw new TestFailure(
            ($message ? "$message — " : '') .
            "Se esperaba $exceptionClass, se capturó " . get_class($e)
        );
    }
    throw new TestFailure(
        ($message ? "$message — " : '') .
        "Se esperaba $exceptionClass, no se lanzó nada"
    );
}