<?php
header('Content-Type: application/json; charset=utf-8');
require 'conexion.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT id_config, nombre_empresa, logo_path, color_primario, impuesto_porcentaje, dia_facturacion, dias_gracia_mora, monto_recargo_mora, dias_corte_automatico FROM configuracion_empresa WHERE id_config = 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config) {
        $pdo->exec("INSERT INTO configuracion_empresa (id_config, nombre_empresa, logo_path, color_primario, impuesto_porcentaje, dia_facturacion, dias_gracia_mora, monto_recargo_mora, dias_corte_automatico) VALUES (1, 'REDYTELCA', 'Logos/logo_transparent.png', '#2563eb', 0.00, 1, 5, 0.00, 10)");
        $config = [
            'id_config' => 1,
            'nombre_empresa' => 'REDYTELCA',
            'logo_path' => 'Logos/logo_transparent.png',
            'color_primario' => '#2563eb',
            'impuesto_porcentaje' => 0.00,
            'dia_facturacion' => 1,
            'dias_gracia_mora' => 5,
            'monto_recargo_mora' => 0.00,
            'dias_corte_automatico' => 10
        ];
    }

    echo json_encode(['status' => 'success', 'config' => $config]);
    exit;
}

if ($method === 'POST') {
    $staffSession = requireStaffAuth($pdo, 1); // CORRECCIÓN: Solo los administradores pueden modificar la empresa
    $nombre = isset($_POST['nombre_empresa']) ? trim($_POST['nombre_empresa']) : '';
    $color = isset($_POST['color_primario']) ? trim($_POST['color_primario']) : '#2563eb';
    $impuesto_porcentaje = isset($_POST['impuesto_porcentaje']) && $_POST['impuesto_porcentaje'] !== '' ? (float)$_POST['impuesto_porcentaje'] : null;
    $dia_facturacion = isset($_POST['dia_facturacion']) && $_POST['dia_facturacion'] !== '' ? (int)$_POST['dia_facturacion'] : null;
    $dias_gracia_mora = isset($_POST['dias_gracia_mora']) && $_POST['dias_gracia_mora'] !== '' ? (int)$_POST['dias_gracia_mora'] : null;
    $monto_recargo_mora = isset($_POST['monto_recargo_mora']) && $_POST['monto_recargo_mora'] !== '' ? (float)$_POST['monto_recargo_mora'] : null;
    $dias_corte_automatico = isset($_POST['dias_corte_automatico']) && $_POST['dias_corte_automatico'] !== '' ? (int)$_POST['dias_corte_automatico'] : null;

    $logoPath = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['logo']['tmp_name']);
        finfo_close($finfo);

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            echo json_encode(['status' => 'error', 'message' => 'El archivo subido no es una imagen válida.']);
            exit;
        }

        $uploadDir = 'uploads/empresa/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $filename = 'logo.' . ($ext ?: 'png');
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
            $logoPath = $targetPath;
        }
    }

    $fields = [];
    $params = [];

    if ($nombre !== '') {
        $fields[] = 'nombre_empresa = ?';
        $params[] = $nombre;
    }

    if ($color !== '') {
        $fields[] = 'color_primario = ?';
        $params[] = $color;
    }

    if ($logoPath !== null) {
        $fields[] = 'logo_path = ?';
        $params[] = $logoPath;
    }

    if ($impuesto_porcentaje !== null) {
        $fields[] = 'impuesto_porcentaje = ?';
        $params[] = $impuesto_porcentaje;
    }

    if ($dia_facturacion !== null) {
        $fields[] = 'dia_facturacion = ?';
        $params[] = $dia_facturacion;
    }

    if ($dias_gracia_mora !== null) {
        $fields[] = 'dias_gracia_mora = ?';
        $params[] = $dias_gracia_mora;
    }

    if ($monto_recargo_mora !== null) {
        $fields[] = 'monto_recargo_mora = ?';
        $params[] = $monto_recargo_mora;
    }

    if ($dias_corte_automatico !== null) {
        $fields[] = 'dias_corte_automatico = ?';
        $params[] = $dias_corte_automatico;
    }

    if (empty($fields)) {
        echo json_encode(['status' => 'error', 'message' => 'No se enviaron datos para actualizar']);
        exit;
    }

    $sql = 'UPDATE configuracion_empresa SET ' . implode(', ', $fields) . ' WHERE id_config = 1';
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute($params);

    echo json_encode([
        'status' => $ok ? 'success' : 'error',
        'message' => $ok ? 'Configuración actualizada' : 'No se pudo actualizar'
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Método no soportado']);
