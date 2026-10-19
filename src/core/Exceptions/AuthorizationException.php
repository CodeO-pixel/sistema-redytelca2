<?php
declare(strict_types=1);

namespace App\Exceptions;

/**
 * Se lanza cuando el usuario está autenticado pero su rol o
 * permiso no autoriza la operación.
 */
final class AuthorizationException extends AppException
{
    /**
     * Constructor de la excepción.
     *
     * @param string $message Mensaje de error.
     * @param \Throwable|null $previous Excepción previa para encadenamiento.
     */
    public function __construct(string $message = 'Acceso denegado.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 403, $previous);
    }
}