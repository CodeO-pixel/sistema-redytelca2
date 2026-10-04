<?php
header('Content-Type: application/json; charset=utf-8');
require 'conexion.php';
requirePageAccess($pdo, 'naps');

$method = $_SERVER['REQUEST_METHOD'];
// Auto-reparación de esquema eliminada (Prompt 5): bd_redytelca.sql es la fuente de verdad desde Fase 0, este bloque siempre resolvía a no-op.

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT na.*, o.marca_modelo AS olt_nombre, o.codigo AS olt_codigo, n.nombre AS nodo_nombre,
                                (SELECT COUNT(sv.id_servicio)
                                   FROM servicios sv
                                  WHERE sv.id_naps = na.id_nap
                                    AND sv.estado_comercial != 'retirado') AS total_clientes,
                                (SELECT GROUP_CONCAT(DISTINCT CONCAT(c.nombres, ' ', c.apellidos, ' (', COALESCE(sv.alias, sv.direccion_texto), ')') SEPARATOR ', ')
                                   FROM servicios sv
                                   INNER JOIN clientes c ON c.id_cliente = sv.id_cliente
                                  WHERE sv.id_naps = na.id_nap
                                    AND sv.estado_comercial != 'retirado') AS clientes_conectados
                         FROM naps na
                         LEFT JOIN olts o ON o.id_olt = na.id_olts
                         LEFT JOIN nodos n ON n.id_nodo = o.id_nodos
                         ORDER BY na.codigo ASC");
    echo json_encode(['status' => 'success', 'naps' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

if ($method === 'POST') {
    $codigo = trim($input['codigo'] ?? '');
    $cantidad_puertos_max = isset($input['cantidad_puertos_max']) && $input['cantidad_puertos_max'] !== '' ? (int)$input['cantidad_puertos_max'] : 16;
    $ubicacion_fisica = trim($input['ubicacion_fisica'] ?? '');
    $latitudRaw = isset($input['latitud']) && $input['latitud'] !== '' ? $input['latitud'] : null;
    $longitudRaw = isset($input['longitud']) && $input['longitud'] !== '' ? $input['longitud'] : null;
    $latitud = $latitudRaw !== null ? str_replace(',', '.', (string) $latitudRaw) : null;
    $longitud = $longitudRaw !== null ? str_replace(',', '.', (string) $longitudRaw) : null;
    $id_olts = (int)($input['id_olts'] ?? 0);

    if ($codigo === '' || $latitud === null || $longitud === null || $id_olts <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Código, latitud, longitud y OLT son obligatorios']);
        exit;
    }

    if (!is_numeric($latitud) || !is_numeric($longitud)) {
        echo json_encode(['status' => 'error', 'message' => 'La latitud/longitud debe ser un número válido (usa punto como separador decimal, ej. 10.4806).']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO naps (codigo, cantidad_puertos_max, ubicacion_fisica, latitud, longitud, id_olts) VALUES (?, ?, ?, ?, ?, ?)");
        $ok = $stmt->execute([$codigo, $cantidad_puertos_max, $ubicacion_fisica, $latitud, $longitud, $id_olts]);
        echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'NAP creada correctamente' : 'No se pudo crear la NAP']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error creando la NAP: el código ya existe o la OLT seleccionada no es válida']);
    }
    exit;
}

if ($method === 'PUT') {
    $id = (int)($input['id_nap'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'NAP inválida']);
        exit;
    }
    $latitudRaw = isset($input['latitud']) && $input['latitud'] !== '' ? $input['latitud'] : null;
    $longitudRaw = isset($input['longitud']) && $input['longitud'] !== '' ? $input['longitud'] : null;
    $latitud = $latitudRaw !== null ? str_replace(',', '.', (string) $latitudRaw) : null;
    $longitud = $longitudRaw !== null ? str_replace(',', '.', (string) $longitudRaw) : null;
    if ($latitudRaw !== null && !is_numeric($latitud)) {
        echo json_encode(['status' => 'error', 'message' => 'La latitud/longitud debe ser un número válido (usa punto como separador decimal, ej. 10.4806).']);
        exit;
    }
    if ($longitudRaw !== null && !is_numeric($longitud)) {
        echo json_encode(['status' => 'error', 'message' => 'La latitud/longitud debe ser un número válido (usa punto como separador decimal, ej. 10.4806).']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("UPDATE naps SET codigo = ?, cantidad_puertos_max = ?, ubicacion_fisica = ?, latitud = ?, longitud = ?, id_olts = ? WHERE id_nap = ?");
        $ok = $stmt->execute([
            trim($input['codigo'] ?? ''),
            isset($input['cantidad_puertos_max']) && $input['cantidad_puertos_max'] !== '' ? (int)$input['cantidad_puertos_max'] : 16,
            trim($input['ubicacion_fisica'] ?? ''),
            $latitud,
            $longitud,
            (int)($input['id_olts'] ?? 0),
            $id
        ]);
        echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'NAP actualizada' : 'No se pudo actualizar']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error actualizando la NAP: verifica el código o la OLT']);
    }
    exit;
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    try {
        $stmt = $pdo->prepare("DELETE FROM naps WHERE id_nap = ?");
        $ok = $stmt->execute([$id]);
        echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'NAP eliminada' : 'No se pudo eliminar']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar: esta NAP tiene clientes o equipos asociados. Reasígnalos primero.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Método no soportado']);