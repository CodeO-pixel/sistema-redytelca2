<?php
header('Content-Type: application/json; charset=utf-8');
require 'conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// El token de sesión NUNCA se lee desde la URL. Solo se acepta el header X-Session-Token.
$token = '';
if (isset($_SERVER['HTTP_X_SESSION_TOKEN']) && trim($_SERVER['HTTP_X_SESSION_TOKEN']) !== '') {
    $token = trim($_SERVER['HTTP_X_SESSION_TOKEN']);
}

try {
    // Auto-reparación de esquema eliminada (Prompt 9-bis): sesiones ya es
    // nativa en bd_redytelca.sql desde Fase 0; este CREATE TABLE se
    // ejecutaba en cada logout sin necesidad real.
    if ($token !== '') {
        // Invalida el token fijando `expira_en = NOW()` en lugar de borrar
        // la fila. Esto mantiene el historial de sesiones para auditoría.
        $stmt = $pdo->prepare('UPDATE sesiones SET expira_en = NOW() WHERE token = ?');
        $stmt->execute([$token]);
    }
} catch (Exception $e) {
    // No se bloquea el logout local si falla el borrado en servidor.
}

session_unset();
session_destroy();

echo json_encode(['status' => 'success']);