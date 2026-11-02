<?php
// validators/PortalPagoValidator.php
declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

/**
 * Validador para el POST api_portal_pagos.php (multipart).
 *
 * Solo valida forma del body. Las reglas de negocio (ownership de
 * servicio_id, saldo de factura, activo del método, obligatoriedad
 * condicional de tasa_cambio para VES) las valida `PagoService::crear`.
 *
 * NO valida:
 *   - cliente_id       → viene de la sesión (PortalPagoController lo fuerza).
 *   - origen           → forzado a 'portal_cliente' por el service.
 *   - estado           → forzado a 'pendiente' por PagoService.
 *   - comprobante_path → generado por el controller tras el upload.
 *   - comprobante      → es un archivo ($_FILES), no un campo del body.
 */
final class PortalPagoValidator
{
    /**
     * Valida los datos del POST del portal.
     *
     * @param array $datos
     * @return array
     * @throws \App\Exceptions\ValidationException
     */
    public static function validateCreate(array $datos): array
    {
        return Validator::make($datos)
            ->int('factura_id', 1)
            ->required('servicio_id', 'servicio')
            ->int('servicio_id', 1)
            ->required('monto', 'monto')
            ->numeric('monto', 0.01)
            ->required('fecha_pago', 'fecha de pago')
            ->date('fecha_pago', 'Y-m-d H:i:s')
            ->enum('moneda_pago', ['USD', 'VES'])
            ->numeric('tasa_cambio', 0.01)
            ->required('metodo_id', 'método de pago')
            ->int('metodo_id', 1)
            ->string('referencia_bancaria', null, 255)
            ->validateOrFail();
    }
}