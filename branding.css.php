<?php
// branding.css.php
// CSS render-blocking que inyecta --accent y --accent-rgb desde la BD
// para eliminar el FOUC pre-login (ADR 009).
header('Content-Type: text/css; charset=utf-8');

require_once __DIR__ . '/core/Bootstrap.php';

use App\Core\Http;
use App\Repositories\ConfiguracionEmpresaRepository;

$color = '#104aa8';

try {
    $pdo = Http::init();
    $repo = new ConfiguracionEmpresaRepository($pdo);
    $branding = $repo->findPublicBranding();
    if ($branding && !empty($branding['color_primario'])) {
        $dbColor = trim($branding['color_primario']);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $dbColor)) {
            $color = $dbColor;
        }
    }
} catch (\Throwable $e) {
    // Silencioso: si falla la conexión, query, o validación, usamos fallback.
}

$hex = str_replace('#', '', $color);
$r = hexdec(substr($hex, 0, 2));
$g = hexdec(substr($hex, 2, 2));
$b = hexdec(substr($hex, 4, 2));

echo ":root { --accent: {$color}; --accent-rgb: {$r}, {$g}, {$b}; }\n";