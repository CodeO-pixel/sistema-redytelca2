<?php
// tests/smoke.test.php
// Test de humo: verifica que el harness funciona.
// Devuelve un array [nombre_del_test => callable].

return [
    'assertSame compara valores estrictos' => function () {
        assertSame(2, 1 + 1);
    },
    'assertTrue acepta true' => function () {
        assertTrue(true);
    },
    'assertSame detecta diferencias' => function () {
        assertThrows(function () {
            assertSame(1, 2, 'Debe fallar');
        }, TestFailure::class);
    },
    'assertThrows captura excepciones' => function () {
        assertThrows(function () {
            throw new RuntimeException('boom');
        }, RuntimeException::class);
    },
];