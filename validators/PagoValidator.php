<?php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;
use App\Exceptions\ValidationException;

/**
 * Validador para la entidad Pago.
 */
final class PagoValidator
{
    /**
     * Valida los datos para crear un pago.
     *
     * @param array $datos
     * @return array
     * @throws ValidationException
     */
    public static function validateCreate(array $datos): array
    {
        $validated = Validator::make($datos)
            ->required('cliente_id', 'cliente')
            ->int('cliente_id', 1)
            ->required('servicio_id', 'servicio')
            ->int('servicio_id', 1)
            ->required('monto', 'monto')
            ->numeric('monto', 0.01)
            ->required('fecha_pago', 'fecha de pago')
            ->date('fecha_pago', 'Y-m-d H:i:s')
            ->enum('moneda_pago', ['USD', 'VES'])
            ->numeric('tasa_cambio', 0.01)
            ->int('metodo_id', 1)
            ->string('referencia_bancaria', null, 255)
            ->string('comprobante_path', null, 255)
            ->enum('origen', ['backoffice', 'portal_cliente'])
            ->enum('estado', ['pendiente', 'validado', 'rechazado'])
            ->validateOrFail();

        $moneda = $validated['moneda_pago'] ?? 'USD';
        if ($moneda === 'VES') {
            if (!isset($validated['tasa_cambio']) || (float) $validated['tasa_cambio'] <= 0) {
                throw new ValidationException(['tasa_cambio' => 'La tasa de cambio es obligatoria y debe ser mayor a 0 para moneda VES.']);
            }
        }

        return $validated;
    }

    /**
     * Valida los datos para actualizar un pago.
     *
     * La regla cruzada moneda/tasa NO se aplica en update. Si el
     * caller cambia moneda a VES sin enviar tasa_cambio, el service
     * usa la tasa actual del pago (PagoService::actualizar).
     *
     * @param array $datos
     * @return array
     * @throws ValidationException
     */
    public static function validateUpdate(array $datos): array
    {
        return Validator::make($datos)
            ->int('cliente_id', 1)
            ->int('servicio_id', 1)
            ->numeric('monto', 0.01)
            ->date('fecha_pago', 'Y-m-d H:i:s')
            ->enum('moneda_pago', ['USD', 'VES'])
            ->numeric('tasa_cambio', 0.01)
            ->int('metodo_id', 1)
            ->string('referencia_bancaria', null, 255)
            ->string('comprobante_path', null, 255)
            ->enum('origen', ['backoffice', 'portal_cliente'])
            ->enum('estado', ['pendiente', 'validado', 'rechazado'])
            ->validateOrFail();
    }
}