<?php
// controllers/EmpresaController.php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\EmpresaService;
use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;

final class EmpresaController extends BaseController
{
    private EmpresaService $empresaService;

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->empresaService = new EmpresaService($pdo);
    }

    public function index(): void
    {
        $config = $this->empresaService->obtener();
        $this->render(['status' => 'success', 'config' => $config], 200);
    }

    public function store(): void
    {
        $input = $this->input();

        // Snapshot actual para poder borrar archivos reemplazados (cierra 139d-2)
        $configActual = $this->empresaService->obtener() ?: [];
        $logoLoginActual = !empty($configActual['logo_path']) ? (string) $configActual['logo_path'] : null;
        $logoSidebarActual = !empty($configActual['logo_path_sidebar']) ? (string) $configActual['logo_path_sidebar'] : null;

        $datos = [];

        // Uploads: logo_login (con back-compat a 'logo' del frontend viejo) + logo_sidebar
        $logoLoginPath = $this->procesarUpload('logo_login');
        if ($logoLoginPath === null) {
            $logoLoginPath = $this->procesarUpload('logo'); // back-compat B3.11
        }
        $logoSidebarPath = $this->procesarUpload('logo_sidebar');

        if ($logoLoginPath !== null) {
            $datos['logo_path'] = $logoLoginPath;
        }
        if ($logoSidebarPath !== null) {
            $datos['logo_path_sidebar'] = $logoSidebarPath;
        }

        // Campos de texto/numéricos
        foreach (['nombre_empresa', 'color_primario', 'impuesto_porcentaje', 'dia_facturacion', 'dias_gracia_mora', 'monto_recargo_mora', 'dias_corte_automatico'] as $campo) {
            if (isset($input[$campo]) && $input[$campo] !== '') {
                $datos[$campo] = is_string($input[$campo]) ? trim($input[$campo]) : $input[$campo];
            }
        }

        if (empty($datos)) {
            // Si subieron archivos pero todos los datos quedaron vacíos, limpiar
            $this->cleanupArchivos([$logoLoginPath, $logoSidebarPath]);
            $this->render(['status' => 'error', 'message' => 'No se enviaron datos para actualizar'], 400);
            return;
        }

        try {
            $this->empresaService->actualizar($datos);
        } catch (\Throwable $e) {
            // 139d-2: limpiar archivos nuevos si la validación falló después del upload
            $this->cleanupArchivos([$logoLoginPath, $logoSidebarPath]);
            throw $e;
        }

        // 139d-2: limpiar archivos viejos reemplazados (post-éxito, no post-fallo)
        $viejosAReemplazar = [];
        if ($logoLoginPath !== null && $logoLoginActual !== null && $logoLoginActual !== $logoLoginPath) {
            $viejosAReemplazar[] = $logoLoginActual;
        }
        if ($logoSidebarPath !== null && $logoSidebarActual !== null && $logoSidebarActual !== $logoSidebarPath) {
            $viejosAReemplazar[] = $logoSidebarActual;
        }
        $this->cleanupArchivos($viejosAReemplazar);

        $this->render(['status' => 'success', 'message' => 'Configuración actualizada'], 200);
    }

    public function publico(): void
    {
        $config = $this->empresaService->obtenerBranding();
        $this->render(['status' => 'success', 'config' => $config], 200);
    }

    /**
     * Procesa un upload de imagen. Devuelve la ruta relativa ("uploads/empresa/logo_login.png")
     * o null si no vino archivo. Valida MIME. Lanza ValidationException si el MIME no es imagen.
     */
    private function procesarUpload(string $fieldName): ?string
    {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES[$fieldName]['tmp_name']);
        finfo_close($finfo);

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            throw new ValidationException([$fieldName => 'El archivo subido no es una imagen válida.']);
        }

        $baseDir = dirname(__DIR__, 2);
        $uploadDir = $baseDir . '/public/uploads/empresa/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Nombre canónico: siempre {fieldName}.{ext} para que reemplazar el logo sobreescriba el viejo.
        $ext = pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION);
        $ext = $ext !== '' ? strtolower($ext) : 'png';
        // Sanity check de la extensión (evita ../ o caracteres raros)
        if (!preg_match('/^[a-z0-9]{1,5}$/', $ext)) {
            $ext = 'png';
        }
        $filename = $fieldName . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetPath)) {
            throw new DomainException('No se pudo guardar la imagen.');
        }

        // Si el usuario sube el mismo logo con otra extensión (ej. .png → .jpg),
        // borrar la versión anterior para evitar acumulación de archivos.
        foreach (glob($uploadDir . $fieldName . '.*') as $archivoViejo) {
            if (is_file($archivoViejo) && $archivoViejo !== $targetPath) {
                @unlink($archivoViejo);
            }
        }

        return 'uploads/empresa/' . $filename;
    }

    /**
     * Borra archivos relativos (dentro de public/). Ignora silenciosamente los que
     * no existan o estén fuera del directorio permitido.
     */
    private function cleanupArchivos(array $rutasRelativas): void
    {
        $basePublic = dirname(__DIR__, 2) . '/public/';
        foreach ($rutasRelativas as $ruta) {
            if (!is_string($ruta) || $ruta === '') {
                continue;
            }
            $abs = $basePublic . ltrim($ruta, '/');
            // Sanity: no salir del directorio public/
            if (strpos(realpath($abs) ?: '', realpath($basePublic) ?: '') !== 0) {
                continue;
            }
            if (is_file($abs)) {
                @unlink($abs);
            }
        }
    }
}