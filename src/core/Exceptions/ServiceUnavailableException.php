<?php
declare(strict_types=1);

namespace App\Exceptions;

final class ServiceUnavailableException extends AppException
{
    public function __construct(
        string $message = 'El servicio no está disponible temporalmente.',
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 503, $previous);
    }
}
