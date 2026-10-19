<?php
declare(strict_types=1);

namespace App\Exceptions;

/**
 * Se lanza cuando falta autenticación o el token es inválido.
 */
final class AuthenticationException extends AppException
{
    /**
     * Constructor de la excepción.
     *
     * @param string $message Mensaje de error.
     * @param \Throwable|null $previous Excepción previa para encadenamiento.
     */
    public function __construct(string $message = 'No autenticado.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
}