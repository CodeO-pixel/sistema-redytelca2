<?php
header('Content-Type: application/json; charset=utf-8');
require 'conexion.php';
requirePageAccess($pdo, 'tareas');

$method = $_SERVER['REQUEST_METHOD'];
// Auto-reparación de esquema eliminada (Prompt 5): bd_redytelca.sql es la fuente de verdad desde Fase 0, este bloque siempre resolvía a no-op.

if ($method === 'GET') {
    // Incluir información relacionada de servicios y tickets
    $stmt = $pdo->query("SELECT t.*, c.nombres, c.apellidos, u.username AS creador,
        s.alias AS servicio_alias, s.direccion_texto AS servicio_direccion,
        tk.asunto AS ticket_asunto
        FROM tareas t
        LEFT JOIN clientes c ON c.id_cliente = t.id_cliente
        LEFT JOIN usuarios u ON u.id_usuario = t.id_usuario_creador
        LEFT JOIN servicios s ON s.id_servicio = t.id_servicio
        LEFT JOIN tickets tk ON tk.id_ticket = t.id_ticket
        ORDER BY t.creado_en DESC");
    echo json_encode(['status' => 'success', 'tareas' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

if ($method === 'POST') {
    $titulo = trim($input['titulo'] ?? '');
    $descripcion = trim($input['descripcion'] ?? '');
    $estado = trim($input['estado'] ?? 'Pendiente');
    $prioridad = trim($input['prioridad'] ?? 'Media');
    $id_cliente = (isset($input['id_cliente']) && $input['id_cliente'] !== '' && $input['id_cliente'] !== null) ? (int)$input['id_cliente'] : null;
    $id_usuario_creador = (isset($input['id_usuario_creador']) && $input['id_usuario_creador'] !== '' && $input['id_usuario_creador'] !== null) ? (int)$input['id_usuario_creador'] : null;
    $id_ticket = (isset($input['id_ticket']) && $input['id_ticket'] !== '' && $input['id_ticket'] !== null) ? (int)$input['id_ticket'] : null;
    $id_servicio = (isset($input['id_servicio']) && $input['id_servicio'] !== '' && $input['id_servicio'] !== null) ? (int)$input['id_servicio'] : null;

    if ($titulo === '') {
        echo json_encode(['status' => 'error', 'message' => 'El título es obligatorio']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO tareas (titulo, descripcion, estado, prioridad, id_cliente, id_usuario_creador, id_ticket, id_servicio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $ok = $stmt->execute([$titulo, $descripcion, $estado, $prioridad, $id_cliente, $id_usuario_creador, $id_ticket > 0 ? $id_ticket : null, $id_servicio > 0 ? $id_servicio : null]);

    echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Tarea creada correctamente' : 'No se pudo crear la tarea']);
    exit;
}

if ($method === 'PUT') {
    $id = (int)($input['id_tarea'] ?? 0);
    $id_ticket = (isset($input['id_ticket']) && $input['id_ticket'] !== '' && $input['id_ticket'] !== null) ? (int)$input['id_ticket'] : null;
    $id_servicio = (isset($input['id_servicio']) && $input['id_servicio'] !== '' && $input['id_servicio'] !== null) ? (int)$input['id_servicio'] : null;

    $stmt = $pdo->prepare("UPDATE tareas SET titulo = ?, descripcion = ?, estado = ?, prioridad = ?, id_cliente = ?, id_usuario_creador = ?, id_ticket = ?, id_servicio = ? WHERE id_tarea = ?");
    $ok = $stmt->execute([
        trim($input['titulo'] ?? ''),
        trim($input['descripcion'] ?? ''),
        trim($input['estado'] ?? 'Pendiente'),
        trim($input['prioridad'] ?? 'Media'),
        (isset($input['id_cliente']) && $input['id_cliente'] !== '' && $input['id_cliente'] !== null) ? (int)$input['id_cliente'] : null,
        (isset($input['id_usuario_creador']) && $input['id_usuario_creador'] !== '' && $input['id_usuario_creador'] !== null) ? (int)$input['id_usuario_creador'] : null,
        $id_ticket > 0 ? $id_ticket : null,
        $id_servicio > 0 ? $id_servicio : null,
        $id
    ]);
    echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Tarea actualizada' : 'No se pudo actualizar']);
    exit;
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM tareas WHERE id_tarea = ?");
    $ok = $stmt->execute([$id]);
    echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Tarea eliminada' : 'No se pudo eliminar']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Método no soportado']);
