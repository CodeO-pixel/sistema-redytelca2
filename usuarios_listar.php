<?php
header('Content-Type: application/json; charset=utf-8');
require 'conexion.php';
requireStaffAuth($pdo);

$query = "
    SELECT u.id_usuario, u.username, u.email, u.must_change_password, u.email_verified, u.id_rol, r.nombre_rol,
           u.estado,
           (SELECT MAX(creado_en) FROM sesiones WHERE id_usuario = u.id_usuario) AS ultima_conexion
    FROM usuarios u
    LEFT JOIN rol r ON r.id_rol = u.id_rol
    ORDER BY u.username ASC
";

$stmt = $pdo->query($query);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'status' => 'success',
    'usuarios' => $usuarios
]);
