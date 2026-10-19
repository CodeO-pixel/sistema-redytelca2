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
        $logoPath = null;

        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $_FILES['logo']['tmp_name']);
            finfo_close($finfo);

            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
            if (!in_array($mimeType, $allowedMimeTypes, true)) {
                throw new ValidationException(['logo' => 'El archivo subido no es una imagen válida.']);
            }

            $uploadDir = 'uploads/empresa/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $filename = 'logo.' . ($ext ?: 'png');
            $targetPath = $uploadDir . $filename;

            if (!move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
                throw new DomainException('No se pudo guardar el logo.');
            }

            $logoPath = $targetPath;
        }

        $datos = [];
        foreach (['nombre_empresa', 'color_primario', 'impuesto_porcentaje', 'dia_facturacion', 'dias_gracia_mora', 'monto_recargo_mora', 'dias_corte_automatico'] as $campo) {
            if (isset($input[$campo]) && $input[$campo] !== '') {
                $datos[$campo] = is_string($input[$campo]) ? trim($input[$campo]) : $input[$campo];
            }
        }

        if ($logoPath !== null) {
            $datos['logo_path'] = $logoPath;
        }

        if (empty($datos)) {
            $this->render(['status' => 'error', 'message' => 'No se enviaron datos para actualizar'], 400);
            return;
        }

        try {
            $this->empresaService->actualizar($datos);
        } catch (\Throwable $e) {
            if ($logoPath !== null && is_file($logoPath)) {
                @unlink($logoPath);
            }
            throw $e;
        }

        $this->render(['status' => 'success', 'message' => 'Configuración actualizada'], 200);
    }

    public function publico(): void
    {
        $config = $this->empresaService->obtenerBranding();
        $this->render(['status' => 'success', 'config' => $config], 200);
    }
}