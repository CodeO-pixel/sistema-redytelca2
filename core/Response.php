<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Clase Response
 *
 * Helper para emitir respuestas HTTP en formato JSON y convertir
 * excepciones de dominio o de sistema en respuestas estandarizadas.
 */
final class Response
{
    /**
     * Constructor privado para evitar instanciación.
     */
    private function __construct()
    {
    }

    /**
     * Emite una respuesta JSON y termina la ejecución.
     *
     * @param array $data Datos a serializar.
     * @param int $httpStatus Código de estado HTTP (por defecto 200).
     * @return void
     */
    public static function json(array $data, int $httpStatus = 200): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($httpStatus);
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            if (!headers_sent()) {
                http_response_code(500);
            }
            echo json_encode([
                'status' => 'error',
                'message' => 'Error interno al serializar la respuesta.'
            ]);
            exit;
        }

        echo $json;
        exit;
    }

    /**
     * Emite una respuesta JSON de error estandarizada.
     *
     * @param string $message Mensaje de error a mostrar al cliente.
     * @param int $httpStatus Código de estado HTTP (por defecto 400).
     * @param array $extra Datos adicionales a incluir en la respuesta.
     * @return void
     */
    public static function error(string $message, int $httpStatus = 400, array $extra = []): void
    {
        $payload = array_merge([
            'status' => 'error',
            'message' => $message
        ], $extra);

        self::json($payload, $httpStatus);
    }

    /**
     * Convierte una excepción en una respuesta HTTP JSON adecuada.
     *
     * @param \Throwable $e Excepción capturada.
     * @return void
     */
    public static function fromException(\Throwable $e): void
    {
        if ($e instanceof \App\Exceptions\ValidationException) {
            self::error($e->getMessage(), 422, ['errors' => $e->getErrors()]);
        } elseif ($e instanceof \App\Exceptions\AppException) {
            self::error($e->getMessage(), $e->getHttpStatus());
        } elseif ($e instanceof \PDOException) {
            $sqlState = (int) ($e->errorInfo[1] ?? 0);

            if ($sqlState === 1062) {
                self::error('Ya existe un registro con esos datos.', 409);
            } elseif ($sqlState === 1452) {
                self::error('La referencia indicada no existe o fue eliminada.', 409);
            } elseif ($sqlState === 1451) {
                self::error('No se puede eliminar: el registro tiene dependencias.', 409);
            } elseif ($sqlState === 1644) {
                // SIGNAL SQLSTATE '45000' de los triggers de transición.
                // errorInfo[2] trae el mensaje de negocio del trigger.
                self::error($e->errorInfo[2] ?: 'Transición no permitida.', 422);
            } else {
                self::error('Error de base de datos.', 500);
            }
        } elseif ($e instanceof \InvalidArgumentException) {
            self::error($e->getMessage(), 400);
        } else {
            self::error('Error interno del servidor.', 500);
        }
    }
}