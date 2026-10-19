<?php
declare(strict_types=1);

namespace App\Exceptions;

/**
 * Se lanza ante duplicados (UNIQUE violated), condiciones de
 * carrera o conflictos de recurso. Ejemplo: cédula ya registrada.
 */
final class ConflictException extends AppException
{
    /**
     * Constructor de la excepción.
     *
     * @param string $message Mensaje de error.
     * @param \Throwable|null $previous Excepción previa para encadenamiento.
     */
    public function __construct(string $message = 'Conflicto con el estado actual.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 409, $previous);
    }
}