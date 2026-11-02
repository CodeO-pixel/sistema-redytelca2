<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\TasaBcvRepository;

final class TasaBcvService extends BaseService
{
    private const URL_OFICIAL = 'https://www.bcv.org.ve/';
    private const MAX_HTML_BYTES = 2097152;

    private TasaBcvRepository $tasaRepo;

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->tasaRepo = new TasaBcvRepository($pdo);
    }

    public function obtenerDisponible(bool $forUpdate = false): ?array
    {
        return $this->tasaRepo->obtenerDisponible($forUpdate);
    }

    public static function convertirUsdAVes(float $montoUsd, string $tasa): string
    {
        if (
            !is_finite($montoUsd)
            || $montoUsd < 0
            || !preg_match('/^\d+(?:\.\d{1,8})?$/D', $tasa)
            || (float) $tasa <= 0
        ) {
            throw new \InvalidArgumentException('El monto USD o la tasa BCV no son válidos.');
        }

        return number_format(
            round($montoUsd * (float) $tasa, 2, PHP_ROUND_HALF_UP),
            2,
            '.',
            ''
        );
    }

    /**
     * Consulta solo el sitio oficial, valida la respuesta y guarda la tasa
     * con su Fecha Valor en una transacción.
     *
     * @return array{fecha_valor: string, tasa: string}
     */
    public function actualizarDesdeFuenteOficial(): array
    {
        $datos = BcvPageParser::parse($this->consultarSitioOficial());
        $this->transaction(function () use ($datos): void {
            $this->tasaRepo->guardar($datos['fecha_valor'], $datos['tasa']);
        });

        return $datos;
    }

    public function registrarFalloDeConsulta(): void
    {
        $this->transaction(function (): void {
            $this->tasaRepo->marcarConsultaFallida();
        });
    }

    private function consultarSitioOficial(): string
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('La extensión cURL de PHP no está disponible.');
        }

        $curl = curl_init(self::URL_OFICIAL);
        if ($curl === false) {
            throw new \RuntimeException('No se pudo iniciar la consulta HTTPS al BCV.');
        }

        $html = '';
        $respuestaExcesiva = false;
        $usarCurlWindows = false;
        $options = [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'REDYTELCA/1.0 (consulta de tasa BCV)',
            CURLOPT_HTTPHEADER => ['Accept: text/html', 'Cache-Control: no-cache'],
            CURLOPT_ENCODING => '',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$html, &$respuestaExcesiva): int {
                if (strlen($html) + strlen($chunk) > self::MAX_HTML_BYTES) {
                    $respuestaExcesiva = true;
                    return 0;
                }
                $html .= $chunk;
                return strlen($chunk);
            },
        ];

        try {
            if (!curl_setopt_array($curl, $options)) {
                throw new \RuntimeException('No se pudo configurar la consulta HTTPS al BCV.');
            }

            $ok = curl_exec($curl);
            $httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $contentType = (string) curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
            $effectiveUrl = (string) curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
            if ($ok === false) {
                if ($respuestaExcesiva) {
                    throw new \RuntimeException('La respuesta del BCV excede el tamaño permitido.');
                }
                if (curl_errno($curl) === 60 && PHP_OS_FAMILY === 'Windows') {
                    $usarCurlWindows = true;
                } else {
                    throw new \RuntimeException('No se pudo consultar la fuente oficial BCV por HTTPS.');
                }
            } else {
                $this->validarRespuesta($httpStatus, $contentType, $effectiveUrl);
            }
        } finally {
            curl_close($curl);
        }

        if ($usarCurlWindows) {
            return $this->consultarConCurlWindows();
        }

        return $html;
    }

    private function consultarConCurlWindows(): string
    {
        if (!function_exists('proc_open')) {
            throw new \RuntimeException('cURL no pudo verificar la CA y proc_open no está habilitado para usar la CA de Windows.');
        }

        $systemRoot = getenv('SystemRoot');
        $curlExecutable = rtrim($systemRoot !== false ? $systemRoot : 'C:\\Windows', '\\/')
            . '\\System32\\curl.exe';
        if (!is_file($curlExecutable)) {
            throw new \RuntimeException('cURL no pudo verificar la CA y no está disponible el cliente HTTPS de Windows.');
        }

        $command = [
            $curlExecutable,
            '--silent',
            '--show-error',
            '--fail',
            '--location',
            '--max-redirs',
            '3',
            '--connect-timeout',
            '8',
            '--max-time',
            '20',
            '--max-filesize',
            (string) self::MAX_HTML_BYTES,
            '--proto',
            'https',
            '--proto-redir',
            'https',
            '--user-agent',
            'REDYTELCA/1.0 (consulta de tasa BCV)',
            '--header',
            'Accept: text/html',
            '--header',
            'Cache-Control: no-cache',
            '--write-out',
            '\n__BCV_META__%{http_code}|%{content_type}|%{url_effective}',
            self::URL_OFICIAL,
        ];
        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            null,
            null,
            ['bypass_shell' => true]
        );
        if (!is_resource($process)) {
            throw new \RuntimeException('No se pudo iniciar el cliente HTTPS de Windows.');
        }

        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $errorOutput = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        unset($errorOutput);

        $marker = "\n__BCV_META__";
        $markerPosition = is_string($output) ? strrpos($output, $marker) : false;
        if ($exitCode !== 0 || $markerPosition === false) {
            throw new \RuntimeException('El cliente HTTPS de Windows no pudo consultar la fuente oficial BCV.');
        }

        $html = substr($output, 0, $markerPosition);
        $metadata = explode('|', substr($output, $markerPosition + strlen($marker)), 3);
        if (count($metadata) !== 3 || strlen($html) > self::MAX_HTML_BYTES) {
            throw new \RuntimeException('El cliente HTTPS de Windows devolvió una respuesta inválida.');
        }

        $this->validarRespuesta((int) $metadata[0], $metadata[1], $metadata[2]);

        return $html;
    }

    private function validarRespuesta(int $httpStatus, string $contentType, string $effectiveUrl): void
    {
        $url = parse_url($effectiveUrl);
        if (
            $httpStatus !== 200
            || stripos(trim($contentType), 'text/html') !== 0
            || !is_array($url)
            || strtolower((string) ($url['scheme'] ?? '')) !== 'https'
            || !in_array(strtolower((string) ($url['host'] ?? '')), ['bcv.org.ve', 'www.bcv.org.ve'], true)
        ) {
            throw new \RuntimeException('La fuente oficial BCV respondió con un estado o formato no válido.');
        }
    }
}
