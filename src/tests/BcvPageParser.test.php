<?php
// tests/BcvPageParser.test.php
declare(strict_types=1);

require_once __DIR__ . '/../core/Bootstrap.php';

use App\Services\BcvPageParser;
use App\Services\TasaBcvService;
use App\Validators\PortalPagoValidator;
use App\Exceptions\ValidationException;

$fechaValor = (new DateTimeImmutable('today', new DateTimeZone('America/Caracas')))
    ->modify('+1 day')
    ->format('Y-m-d');
$htmlBcv = static function (string $tasa, string $fecha) {
    return '<html><body>'
        . '<div id="dolar"><span> USD</span><strong class="strong-tb">' . $tasa . '</strong></div>'
        . '<div class="pull-right dinpro center">Fecha Valor: '
        . '<span class="date-display-single" property="dc:date" datatype="xsd:dateTime"'
        . ' content="' . $fecha . 'T00:00:00-04:00">Fecha Valor</span></div>'
        . '</body></html>';
};

return [
    'parsea tasa USD y Fecha Valor oficiales conservando ocho decimales' => function () use ($htmlBcv, $fechaValor) {
        $result = BcvPageParser::parse($htmlBcv('889,57540000', $fechaValor));
        assertSame($fechaValor, $result['fecha_valor']);
        assertSame('889.57540000', $result['tasa']);
    },
    'rechaza HTML sin el bloque oficial USD' => function () {
        assertThrows(
            static function () {
                BcvPageParser::parse('<html><body><strong>889,57540000</strong></body></html>');
            },
            RuntimeException::class
        );
    },
    'rechaza tasa cero' => function () use ($htmlBcv, $fechaValor) {
        assertThrows(
            static function () use ($htmlBcv, $fechaValor) {
                BcvPageParser::parse($htmlBcv('0,00000000', $fechaValor));
            },
            RuntimeException::class
        );
    },
    'rechaza formato decimal inesperado' => function () use ($htmlBcv, $fechaValor) {
        assertThrows(
            static function () use ($htmlBcv, $fechaValor) {
                BcvPageParser::parse($htmlBcv('889.5754', $fechaValor));
            },
            RuntimeException::class
        );
    },
    'rechaza Fecha Valor malformada' => function () use ($htmlBcv) {
        assertThrows(
            static function () use ($htmlBcv) {
                BcvPageParser::parse($htmlBcv('889,57540000', '2026-02-31'));
            },
            RuntimeException::class
        );
    },
    'rechaza contenido sobredimensionado' => function () {
        assertThrows(
            static function () {
                BcvPageParser::parse(str_repeat('x', 2097153));
            },
            RuntimeException::class
        );
    },
    'convierte el saldo USD a VES con redondeo de dos decimales' => function () {
        assertSame('44478.77', TasaBcvService::convertirUsdAVes(50.00, '889.57540000'));
    },
    'el validador del portal conserva solo factura, moneda, método, banco, fecha y referencia' => function () {
        $fechaPago = (new DateTimeImmutable('today', new DateTimeZone('America/Caracas')))->format('Y-m-d');
        $validated = PortalPagoValidator::validateCreate([
            'factura_id' => '1',
            'moneda_pago' => 'VES',
            'metodo_id' => '1',
            'banco_emisor_id' => '2',
            'fecha_pago' => $fechaPago,
            'referencia_bancaria' => 'ABC123',
            'cliente_id' => '99',
            'servicio_id' => '99',
            'monto' => '0.01',
            'tasa_cambio' => '1.00',
            'tasa_fecha_valor' => '2026-11-02',
        ]);
        assertSame('1', $validated['factura_id']);
        assertSame('VES', $validated['moneda_pago']);
        assertSame('1', $validated['metodo_id']);
        assertSame('2', $validated['banco_emisor_id']);
        assertSame($fechaPago, $validated['fecha_pago']);
        assertSame('ABC123', $validated['referencia_bancaria']);
        assertFalse(array_key_exists('cliente_id', $validated));
        assertFalse(array_key_exists('servicio_id', $validated));
        assertFalse(array_key_exists('monto', $validated));
        assertFalse(array_key_exists('tasa_cambio', $validated));
        assertFalse(array_key_exists('tasa_fecha_valor', $validated));
    },
    'el validador del portal rechaza fechas futuras' => function () {
        $manana = (new DateTimeImmutable('tomorrow', new DateTimeZone('America/Caracas')))->format('Y-m-d');
        assertThrows(
            static function () use ($manana) {
                PortalPagoValidator::validateCreate([
                    'factura_id' => '1',
                    'moneda_pago' => 'USD',
                    'metodo_id' => '1',
                    'banco_emisor_id' => '1',
                    'fecha_pago' => $manana,
                ]);
            },
            ValidationException::class
        );
    },
    'el validador del portal exige factura' => function () {
        assertThrows(
            static function () {
                PortalPagoValidator::validateCreate([
                    'moneda_pago' => 'USD',
                    'metodo_id' => '1',
                ]);
            },
            ValidationException::class
        );
    },
];
