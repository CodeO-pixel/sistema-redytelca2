<?php
header('Content-Type: application/json; charset=utf-8');
require 'conexion.php';
requirePageAccess($pdo, 'tickets');

$method = $_SERVER['REQUEST_METHOD'];
// Auto-reparación de esquema eliminada (Prompt 5): bd_redytelca.sql es la fuente de verdad desde Fase 0, este bloque siempre resolvía a no-op.

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT t.*, c.nombres, c.apellidos, u.username AS creador FROM tickets t LEFT JOIN clientes c ON c.id_cliente = t.id_cliente LEFT JOIN usuarios u ON u.id_usuario = t.id_usuario_creador ORDER BY t.creado_en DESC");
    echo json_encode(['status' => 'success', 'tickets' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

if ($method === 'POST') {
    $asunto = trim($input['asunto'] ?? '');
    $descripcion = trim($input['descripcion'] ?? '');
    $estado = trim($input['estado'] ?? 'Abierto');
    $prioridad = trim($input['prioridad'] ?? 'Media');
    $id_cliente = (isset($input['id_cliente']) && $input['id_cliente'] !== '' && $input['id_cliente'] !== null) ? (int)$input['id_cliente'] : null;
    $id_usuario_creador = (isset($input['id_usuario_creador']) && $input['id_usuario_creador'] !== '' && $input['id_usuario_creador'] !== null) ? (int)$input['id_usuario_creador'] : null;
    $origen = trim($input['origen'] ?? 'backoffice');
    if ($origen !== 'portal_cliente' && $origen !== 'backoffice') {
        $origen = 'backoffice';
    }

    if ($asunto === '') {
        echo json_encode(['status' => 'error', 'message' => 'El asunto es obligatorio']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO tickets (asunto, descripcion, estado, prioridad, id_cliente, id_usuario_creador, origen) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $ok = $stmt->execute([$asunto, $descripcion, $estado, $prioridad, $id_cliente, $id_usuario_creador, $origen]);

    echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Ticket creado correctamente' : 'No se pudo crear el ticket']);
    exit;
}

if ($method === 'PUT') {
    $id = (int)($input['id_ticket'] ?? 0);
    $origen = trim($input['origen'] ?? 'backoffice');
    if ($origen !== 'portal_cliente' && $origen !== 'backoffice') {
        $origen = 'backoffice';
    }

    $stmt = $pdo->prepare("UPDATE tickets SET asunto = ?, descripcion = ?, estado = ?, prioridad = ?, id_cliente = ?, id_usuario_creador = ?, origen = ? WHERE id_ticket = ?");
    $ok = $stmt->execute([
        trim($input['asunto'] ?? ''),
        trim($input['descripcion'] ?? ''),
        trim($input['estado'] ?? 'Abierto'),
        trim($input['prioridad'] ?? 'Media'),
        (isset($input['id_cliente']) && $input['id_cliente'] !== '' && $input['id_cliente'] !== null) ? (int)$input['id_cliente'] : null,
        (isset($input['id_usuario_creador']) && $input['id_usuario_creador'] !== '' && $input['id_usuario_creador'] !== null) ? (int)$input['id_usuario_creador'] : null,
        $origen,
        $id
    ]);
    echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Ticket actualizado' : 'No se pudo actualizar']);
    exit;
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM tickets WHERE id_ticket = ?");
    $ok = $stmt->execute([$id]);
    echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Ticket eliminado' : 'No se pudo eliminar']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Método no soportado']);
