<?php
header('Content-Type: application/json; charset=utf-8');
require 'conexion.php';
requireStaffAuth($pdo);

$id_rol = isset($_GET['id_rol']) ? intval($_GET['id_rol']) : 0;

// Páginas ya asignadas a este rol (para marcar los checkbox como asignado)
$asignadas = [];
if ($id_rol > 0) {
    $stmtAsig = $pdo->prepare("SELECT id_pagina FROM rol_modulo_pagina WHERE id_rol = ?");
    $stmtAsig->execute([$id_rol]);
    $asignadas = array_map('intval', $stmtAsig->fetchAll(PDO::FETCH_COLUMN, 0));
}

// Universo completo de módulos/páginas del sistema, para poder asignar
// vistas nuevas a un rol aunque todavía no tenga ninguna asignada.
$stmt = $pdo->query("SELECT m.id_modulo, m.nombre_modulo, p.id_pagina, p.nombre_pagina, p.url_pagina
    FROM modulos m
    INNER JOIN paginas p ON p.id_modulo = m.id_modulo
    ORDER BY m.id_modulo, p.id_pagina");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$modulos = [];
foreach ($rows as $row) {
    $idModulo = (int) $row['id_modulo'];
    if (!isset($modulos[$idModulo])) {
        $modulos[$idModulo] = [
            'id_modulo' => $idModulo,
            'nombre_modulo' => $row['nombre_modulo'],
            'vistas' => []
        ];
    }
    $idPagina = (int) $row['id_pagina'];
    $modulos[$idModulo]['vistas'][] = [
        'id_pagina' => $idPagina,
        'nombre_pagina' => $row['nombre_pagina'],
        'url_pagina' => $row['url_pagina'],
        'asignado' => in_array($idPagina, $asignadas, true)
    ];
}

echo json_encode(['status' => 'success', 'modulos' => array_values($modulos)]);