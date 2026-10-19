<?php
declare(strict_types=1);

namespace App\Exceptions;

/**
 * Se lanza cuando un recurso solicitado no existe (por ID,
 * código o clave de negocio).
 */
final class NotFoundException extends AppException
{
    /**
     * Constructor de la excepción.
     *
     * @param string $message Mensaje de error.
     * @param \Throwable|null $previous Excepción previa para encadenamiento.
     */
    public function __construct(string $message = 'Recurso no encontrado.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 404, $previous);
    }
}