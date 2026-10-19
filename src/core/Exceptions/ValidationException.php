<?php
declare(strict_types=1);

namespace App\Exceptions;

/**
 * Se lanza cuando la validación de entrada falla. El array
 * `$errors` lleva detalle campo por campo; `Response::fromException()`
 * lo expone al cliente.
 */
final class ValidationException extends AppException
{
    /**
     * @var array Detalles de los errores de validación.
     */
    private array $errors;

    /**
     * Constructor de la excepción.
     *
     * @param array $errors Array de errores detallados.
     * @param string $message Mensaje de error general.
     * @param \Throwable|null $previous Excepción previa para encadenamiento.
     */
    public function __construct(array $errors = [], string $message = 'Datos inválidos.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 422, $previous);
        $this->errors = $errors;
    }

    /**
     * Obtiene los errores de validación detallados.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}