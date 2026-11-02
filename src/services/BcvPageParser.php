<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Analiza únicamente el bloque USD y la Fecha Valor del sitio oficial BCV.
 */
final class BcvPageParser
{
    private const MAX_HTML_BYTES = 2097152;

    /**
     * @return array{fecha_valor: string, tasa: string}
     */
    public static function parse(string $html): array
    {
        if ($html === '' || strlen($html) > self::MAX_HTML_BYTES) {
            throw new \RuntimeException('La respuesta del BCV está vacía o excede el tamaño permitido.');
        }

        $previousErrorMode = libxml_use_internal_errors(true);
        try {
            $document = new \DOMDocument('1.0', 'UTF-8');
            $loaded = $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorMode);
        }

        if (!$loaded) {
            throw new \RuntimeException('No se pudo interpretar el HTML oficial del BCV.');
        }

        $xpath = new \DOMXPath($document);
        $usdBlocks = $xpath->query('//*[@id="dolar"]');
        if ($usdBlocks === false || $usdBlocks->length !== 1) {
            throw new \RuntimeException('El bloque USD del BCV no tiene la estructura esperada.');
        }

        $usdLabels = $xpath->query('.//span[normalize-space(.)="USD"]', $usdBlocks->item(0));
        $rateNodes = $xpath->query(
            './/strong[contains(concat(" ", normalize-space(@class), " "), " strong-tb ")]',
            $usdBlocks->item(0)
        );
        if ($usdLabels === false || $usdLabels->length !== 1 || $rateNodes === false || $rateNodes->length !== 1) {
            throw new \RuntimeException('El valor USD del BCV no tiene la estructura esperada.');
        }

        $dateNodes = $xpath->query(
            '//div[contains(concat(" ", normalize-space(@class), " "), " pull-right ")'
            . ' and contains(concat(" ", normalize-space(@class), " "), " dinpro ")'
            . ' and contains(concat(" ", normalize-space(@class), " "), " center ")'
            . ' and contains(normalize-space(text()), "Fecha Valor:")]'
            . '/span[contains(concat(" ", normalize-space(@class), " "), " date-display-single ")'
            . ' and @property="dc:date" and @datatype="xsd:dateTime" and @content]'
        );
        if ($dateNodes === false || $dateNodes->length !== 1) {
            throw new \RuntimeException('La Fecha Valor del BCV no tiene la estructura esperada.');
        }

        $rate = self::normalizeRate($rateNodes->item(0)->textContent);
        $fechaValor = self::normalizeDate($dateNodes->item(0)->getAttribute('content'));

        return [
            'fecha_valor' => $fechaValor,
            'tasa' => $rate,
        ];
    }

    private static function normalizeRate(string $rawRate): string
    {
        $rawRate = str_replace(["\xc2\xa0", ' '], '', trim($rawRate));
        if (!preg_match('/^(?:[0-9]{1,3}(?:\.[0-9]{3})+|[0-9]+)(?:,[0-9]{1,8})?$/D', $rawRate)) {
            throw new \RuntimeException('El valor USD del BCV no es un decimal válido.');
        }

        $parts = explode(',', str_replace('.', '', $rawRate), 2);
        $integer = ltrim($parts[0], '0');
        $integer = $integer === '' ? '0' : $integer;
        $fraction = $parts[1] ?? '';

        if (strlen($integer) > 8 || !preg_match('/[1-9]/', $integer . $fraction)) {
            throw new \RuntimeException('El valor USD del BCV debe ser mayor que cero y caber en el formato admitido.');
        }

        return $integer . '.' . str_pad($fraction, 8, '0');
    }

    private static function normalizeDate(string $rawDate): string
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/D', $rawDate)) {
            throw new \RuntimeException('La Fecha Valor del BCV no es un timestamp ISO válido.');
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:sP', $rawDate);
        $errors = \DateTimeImmutable::getLastErrors();
        if (
            $date === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d\TH:i:sP') !== $rawDate
        ) {
            throw new \RuntimeException('La Fecha Valor del BCV no es un timestamp ISO válido.');
        }

        $fechaValor = $date->format('Y-m-d');
        $hoyCaracas = new \DateTimeImmutable('today', new \DateTimeZone('America/Caracas'));
        $fechaMaxima = $hoyCaracas->modify('+7 days')->format('Y-m-d');
        if ($fechaValor < '2000-01-01' || $fechaValor > $fechaMaxima) {
            throw new \RuntimeException('La Fecha Valor del BCV está fuera del rango permitido.');
        }

        return $fechaValor;
    }
}
