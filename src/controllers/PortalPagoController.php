<?php
// controllers/PortalPagoController.php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\PortalPagoService;
use App\Validators\PortalPagoValidator;
use App\Exceptions\ValidationException;

/**
 * Controlador del portal de cliente para pagos.
 *
 * A2-B.4.c — backend de los endpoints GET/POST api_portal_pagos.php y
 * GET api_portal_comprobante.php.
 *
 * Recibe `$clienteSession` desde `Auth::requireClientAuth($pdo)` en
 * el thin file. El `cliente_id` viene de la sesión, NUNCA de la
 * query string ni del body.
 *
 * Contrato:
 *   - GET  api_portal_pagos.php?page=1&per_page=20&estado=pendiente
 *       → 200 + {status, pagos:[...], total, page, pages, per_page}
 *   - GET  api_portal_pagos.php?id=N
 *       → 200 + {status, pago:{...}} | 404
 *   - POST api_portal_pagos.php (multipart/form-data)
 *       → 200 + {status, message, pago_id} | 422 | 404
 *   - GET  api_portal_comprobante.php?id=N
 *       → 200 + bytes (Content-Type del archivo) | 404
 *
 * Upload del comprobante (OWASP File Upload Cheat Sheet):
 *   - MIME real con `finfo_file()` sobre `tmp_name`, no `$_FILES['type']`.
 *   - Whitelist: JPG, PNG, WEBP, PDF. SVG prohibido (CWE-79 XSS).
 *   - Nombre generado con `bin2hex(random_bytes(16))` (CSPRNG).
 *   - Límite 2 MB.
 *   - Directorio con `.htaccess` que bloquea acceso HTTP directo.
 *   - Cleanup del archivo si la transacción falla.
 */
final class PortalPagoController extends BaseController
{
    /** @var PortalPagoService */
    private PortalPagoService $portalPagoService;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->portalPagoService = new PortalPagoService($pdo);
    }

    /**
     * GET api_portal_pagos.php
     *
     * Modo lista (default): pagina los pagos del cliente.
     * Modo detalle (`?id=N`): devuelve un pago del cliente o 404.
     *
     * @param array $clienteSession {id_credencial, id_cliente}
     * @return void
     */
    public function index(array $clienteSession): void
    {
        $clienteId = (int) ($clienteSession['id_cliente'] ?? 0);
        if ($clienteId <= 0) {
            $this->render(['status' => 'error', 'message' => 'Sesión inválida.'], 401);
            return;
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id > 0) {
            $pago = $this->portalPagoService->detalle($id, $clienteId);
            if ($pago === null) {
                $this->render(['status' => 'error', 'message' => 'Pago no encontrado.'], 404);
                return;
            }
            $this->render(['status' => 'success', 'pago' => $pago], 200);
            return;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;
        if ($perPage < 1) {
            $perPage = 20;
        } elseif ($perPage > 100) {
            $perPage = 100;
        }

        $filtros = [
            'estado' => isset($_GET['estado']) ? trim((string) $_GET['estado']) : '',
        ];

        $r = $this->portalPagoService->listar($clienteId, $page, $perPage, $filtros);

        $this->render([
            'status' => 'success',
            'pagos' => $r['items'],
            'total' => $r['total'],
            'page' => $r['page'],
            'pages' => $r['pages'],
            'per_page' => $r['per_page'],
        ], 200);
    }

    /**
     * POST api_portal_pagos.php (multipart/form-data)
     *
     * Procesa el upload del comprobante, valida el body, fuerza
     * `cliente_id` de la sesión y delega en `PortalPagoService::crear`.
     *
     * Si la creación falla después del upload, borra el archivo
     * huérfano (patrón `EmpresaController::store`).
     *
     * @param array $clienteSession
     * @return void
     */
    public function store(array $clienteSession): void
    {
        $clienteId = (int) ($clienteSession['id_cliente'] ?? 0);
        if ($clienteId <= 0) {
            $this->render(['status' => 'error', 'message' => 'Sesión inválida.'], 401);
            return;
        }

        $input = $this->input();
        $validated = PortalPagoValidator::validateCreate($input);

        $comprobantePath = null;

        try {
            $comprobantePath = $this->procesarUpload($clienteId);
            $pagoId = $this->portalPagoService->crear($clienteId, $validated, $comprobantePath);

            $this->render([
                'status' => 'success',
                'message' => 'Pago registrado. Pendiente de validación.',
                'pago_id' => $pagoId,
            ], 200);
        } catch (\Throwable $e) {
            $this->cleanupUpload($comprobantePath);
            throw $e;
        }
    }

    /**
     * GET api_portal_comprobante.php?id=N
     *
     * Sirve el comprobante de un pago del cliente. Valida ownership
     * antes de servir (BOLA/IDOR). Valida que el path real esté dentro
     * de `public/uploads/pagos/` (path traversal, CWE-22). Sirve con
     * `Content-Type` real y `Content-Disposition: attachment` (no
     * inline, para evitar XSS residual).
     *
     * @param array $clienteSession
     * @return void
     */
    public function comprobante(array $clienteSession): void
    {
        $clienteId = (int) ($clienteSession['id_cliente'] ?? 0);
        if ($clienteId <= 0) {
            $this->render(['status' => 'error', 'message' => 'Sesión inválida.'], 401);
            return;
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'El parámetro id es obligatorio.'], 400);
            return;
        }

        $pago = $this->portalPagoService->detalle($id, $clienteId);
        if ($pago === null || empty($pago['comprobante_path'])) {
            $this->render(['status' => 'error', 'message' => 'Comprobante no encontrado.'], 404);
            return;
        }

        $pathRelativo = (string) $pago['comprobante_path'];
        $basePublic = dirname(__DIR__, 2) . '/public/';
        $realBase = realpath($basePublic . 'uploads/pagos/');
        $realAbs = realpath($basePublic . ltrim($pathRelativo, '/'));

        if ($realBase === false || $realAbs === false || strpos($realAbs, $realBase) !== 0) {
            $this->render(['status' => 'error', 'message' => 'Comprobante no encontrado.'], 404);
            return;
        }

        $mime = $this->detectarMime($realAbs);
        if ($mime === null) {
            $this->render(['status' => 'error', 'message' => 'Comprobante no válido.'], 500);
            return;
        }

        $ext = strtolower(pathinfo($realAbs, PATHINFO_EXTENSION));
        $filename = 'comprobante-' . $id . '.' . $ext;

        if (!headers_sent()) {
            header('Content-Type: ' . $mime);
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($realAbs));
        }

        readfile($realAbs);
        exit;
    }

    /**
     * Procesa el upload del comprobante. Devuelve la ruta relativa a
     * `public/` (ej. `uploads/pagos/49/abc123.png`).
     *
     * Reglas (OWASP File Upload Cheat Sheet):
     *   - MIME real con `finfo_file()`, no `$_FILES['type']`.
     *   - Whitelist JPG, PNG, WEBP, PDF. SVG prohibido.
     *   - Nombre CSPRNG (`bin2hex(random_bytes(16))`).
     *   - Límite 2 MB.
     *
     * @param int $clienteId
     * @return string Ruta relativa (`uploads/pagos/{clienteId}/{nombre}.{ext}`).
     * @throws ValidationException
     * @throws \RuntimeException
     */
    private function procesarUpload(int $clienteId): string
    {
        if (!isset($_FILES['comprobante']) || $_FILES['comprobante']['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException(['comprobante' => 'El comprobante es obligatorio.']);
        }

        $maxSize = 2 * 1024 * 1024;
        if ((int) $_FILES['comprobante']['size'] > $maxSize) {
            throw new ValidationException(['comprobante' => 'El archivo excede el límite de 2 MB.']);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['comprobante']['tmp_name']);
        finfo_close($finfo);

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];

        if (!is_string($mimeType) || !isset($allowed[$mimeType])) {
            throw new ValidationException([
                'comprobante' => 'Formato no soportado. Usa JPG, PNG, WEBP o PDF.'
            ]);
        }

        $ext = $allowed[$mimeType];

        $baseDir = dirname(__DIR__, 2) . '/public/uploads/pagos/' . $clienteId . '/';
        if (!is_dir($baseDir)) {
            if (!mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
                throw new \RuntimeException('No se pudo crear el directorio de comprobantes.');
            }
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $baseDir . $filename;

        if (!move_uploaded_file($_FILES['comprobante']['tmp_name'], $targetPath)) {
            throw new \RuntimeException('No se pudo guardar el comprobante.');
        }

        return 'uploads/pagos/' . $clienteId . '/' . $filename;
    }

    /**
     * Borra el archivo subido si la creación del pago falló. Silencioso:
     * si el archivo no existe o está fuera de `public/`, no hace nada.
     *
     * @param string|null $pathRelativo
     * @return void
     */
    private function cleanupUpload(?string $pathRelativo): void
    {
        if ($pathRelativo === null || $pathRelativo === '') {
            return;
        }

        $basePublic = dirname(__DIR__, 2) . '/public/';
        $realBase = realpath($basePublic);
        $realAbs = realpath($basePublic . ltrim($pathRelativo, '/'));

        if ($realBase === false || $realAbs === false) {
            return;
        }
        if (strpos($realAbs, $realBase) !== 0) {
            return;
        }
        if (is_file($realAbs)) {
            @unlink($realAbs);
        }
    }

    /**
     * Detecta el MIME real de un archivo. Retorna `null` si no está en
     * la whitelist (archivo corrupto o manipulado).
     *
     * @param string $pathAbs
     * @return string|null
     */
    private function detectarMime(string $pathAbs): ?string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $pathAbs);
        finfo_close($finfo);

        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        if (!is_string($mime) || !in_array($mime, $allowed, true)) {
            return null;
        }
        return $mime;
    }
}