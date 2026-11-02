<?php
// branding-logo.php
// Sirve el logo del login o del sidebar desde configuracion_empresa.
// Render-blocking: se usa como <img src="branding-logo.php?slot=login"> en el HTML.
// El navegador lo pide antes de ejecutar main.js, así que el logo correcto
// aparece en el primer frame (resuelve el flash de branding pre-login).
//
// Slots válidos: "login" (logo_path) y "sidebar" (logo_path_sidebar).
// Si el slot no existe, el archivo no existe, o la BD falla, se sirve el
// fallback hardcodeado (Logos/logo_transparent.png o el SVG blanco).
//
// Cache: ETag + no-cache, must-revalidate. Si el logo cambia, el ETag cambia
// y el navegador re-fetchea. Si no, 304 rápido.

require_once __DIR__ . '/../src/core/Bootstrap.php';

use App\Core\Http;
use App\Repositories\ConfiguracionEmpresaRepository;

$slot = strtolower((string) ($_GET['slot'] ?? 'login'));
if ($slot !== 'sidebar') {
    $slot = 'login';
}

// Defaults
if ($slot === 'sidebar') {
    $file = __DIR__ . '/Logos/redytelca-identiadad-tipo-c-blanco.svg';
    $mime = 'image/svg+xml';
    $campo = 'logo_path_sidebar';
} else {
    $file = __DIR__ . '/Logos/logo_transparent.png';
    $mime = 'image/png';
    $campo = 'logo_path';
}

// Intentar resolver desde la BD
try {
    $pdo = Http::init();
    $repo = new ConfiguracionEmpresaRepository($pdo);
    $branding = $repo->findPublicBranding();
    if (is_array($branding) && array_key_exists($campo, $branding)) {
        $ruta = $branding[$campo];
        if (is_string($ruta) && $ruta !== '') {
            $abs = realpath(__DIR__ . '/' . ltrim($ruta, '/'));
            if ($abs !== false && is_file($abs)) {
                $file = $abs;
                $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
                $mimes = [
                    'png'  => 'image/png',
                    'jpg'  => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'webp' => 'image/webp',
                    'gif'  => 'image/gif',
                    'svg'  => 'image/svg+xml',
                ];
                $mime = $mimes[$ext] ?? 'application/octet-stream';
            }
        }
    }
} catch (\Throwable $e) {
    // Silencioso: fallback al default.
}

// ETag basado en el contenido del archivo + slot.
$etag = '"' . md5_file($file) . '-' . $slot . '"';
$ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';

if (!headers_sent()) {
    header('Content-Type: ' . $mime);
    header('Cache-Control: no-cache, must-revalidate');
    header('ETag: ' . $etag);
}

if ($ifNoneMatch === $etag) {
    http_response_code(304);
    exit;
}

if (!headers_sent()) {
    header('Content-Length: ' . filesize($file));
}

readfile($file);
exit;