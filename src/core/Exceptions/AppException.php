<?php
declare(strict_types=1);

namespace App\Exceptions;

/**
 * Clase base para la jerarquía de excepciones del proyecto.
 *
 * Response::fromException() lee getHttpStatus() para determinar
 * el código HTTP a devolver al cliente.
 */
abstract class AppException extends \RuntimeException
{
    /**
     * @var int Código de estado HTTP asociado a la excepción.
     */
    protected int $httpStatus;

    /**
     * Constructor de la excepción.
     *
     * @param string $message Mensaje de error.
     * @param int $httpStatus Código HTTP a retornar.
     * @param \Throwable|null $previous Excepción previa para encadenamiento.
     */
    public function __construct(string $message = '', int $httpStatus = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->httpStatus = $httpStatus;
    }

    /**
     * Obtiene el código de estado HTTP.
     *
     * @return int
     */
    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}