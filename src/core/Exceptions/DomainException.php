<?php
declare(strict_types=1);

namespace App\Exceptions;

/**
 * Se lanza cuando una regla de negocio impide la operación.
 * Ejemplo: transición de estado no permitida, precondición no cumplida.
 */
final class DomainException extends AppException
{
    /**
     * Constructor de la excepción.
     *
     * @param string $message Mensaje de error.
     * @param \Throwable|null $previous Excepción previa para encadenamiento.
     */
    public function __construct(string $message = 'Operación no permitida por regla de negocio.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 422, $previous);
    }
}