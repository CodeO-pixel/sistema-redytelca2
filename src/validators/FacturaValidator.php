<?php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;
use App\Exceptions\ValidationException;

/**
 * Validador para la entidad Factura.
 *
 * No exige estado en create: el service fuerza 'pendiente'.
 * Regla cruzada: si moneda=VES, tasa_cambio es obligatoria y >0.
 * La regla cruzada solo aplica en create. En update, el service
 * resuelve la tasa actual de la BD si el caller no la envía.
 */
final class FacturaValidator
{
    /**
     * Valida los datos para crear una factura.
     *
     * @param array $datos
     * @return array
     * @throws ValidationException
     */
    public static function validateCreate(array $datos): array
    {
        $validated = Validator::make($datos)
            ->required('servicio_id', 'servicio')
            ->int('servicio_id', 1)
            ->required('periodo', 'periodo')
            ->regex('periodo', '/^[0-9]{4}-[0-9]{2}$/', 'El periodo debe tener formato AAAA-MM (ej. 2026-07).')
            ->required('monto', 'monto')
            ->numeric('monto', 0.01)
            ->required('fecha_emision', 'fecha de emisión')
            ->date('fecha_emision')
            ->required('fecha_vencimiento', 'fecha de vencimiento')
            ->date('fecha_vencimiento')
            ->enum('moneda', ['USD', 'VES'])
            ->numeric('tasa_cambio', 0.01)
            ->numeric('impuesto_aplicado', 0, 100)
            ->enum('estado', ['pendiente', 'pagada', 'parcial', 'vencida', 'anulada'])
            ->validateOrFail();

        $moneda = $validated['moneda'] ?? 'USD';
        if ($moneda === 'VES') {
            if (!isset($validated['tasa_cambio']) || (float) $validated['tasa_cambio'] <= 0) {
                throw new ValidationException(['tasa_cambio' => 'La tasa de cambio es obligatoria y debe ser mayor a 0 para moneda VES.']);
            }
        }

        return $validated;
    }

    /**
     * Valida los datos para actualizar una factura.
     *
     * La regla cruzada moneda/tasa NO se aplica en update. Si el
     * caller cambia moneda a VES sin enviar tasa_cambio, el service
     * usa la tasa actual de la factura (FacturaService::actualizar).
     * Si el caller envía tasa_cambio explícitamente, numeric() ya
     * valida que sea >0.
     *
     * @param array $datos
     * @return array
     * @throws ValidationException
     */
    public static function validateUpdate(array $datos): array
    {
        return Validator::make($datos)
            ->int('servicio_id', 1)
            ->regex('periodo', '/^[0-9]{4}-[0-9]{2}$/', 'El periodo debe tener formato AAAA-MM (ej. 2026-07).')
            ->numeric('monto', 0.01)
            ->date('fecha_emision')
            ->date('fecha_vencimiento')
            ->enum('moneda', ['USD', 'VES'])
            ->numeric('tasa_cambio', 0.01)
            ->numeric('impuesto_aplicado', 0, 100)
            ->enum('estado', ['pendiente', 'pagada', 'parcial', 'vencida', 'anulada'])
            ->validateOrFail();
    }
}