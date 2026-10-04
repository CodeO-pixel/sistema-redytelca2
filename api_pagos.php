<?php
header('Content-Type: application/json; charset=utf-8');
require 'conexion.php';
$staffSession = requirePageAccess($pdo, 'pagos');

$method = $_SERVER['REQUEST_METHOD'];

/**
 * Reconciliación de estado de factura (Parte 4.5 del documento maestro):
 * suma de pagos validados vs. monto de la factura ->
 *   >= monto  => 'pagada'
 *   > 0       => 'parcial'
 *   = 0       => vuelve a 'pendiente' (nunca revierte una 'anulada' manual)
 * Se ejecuta en PHP, no como trigger de BD, consistente con el patrón de
 * lógica de negocio en capa de aplicación que ya usa el resto del proyecto.
 */
function reconciliarFactura(PDO $pdo, ?int $idFactura): void {
    if (!$idFactura) {
        return;
    }

    $stmtFactura = $pdo->prepare("SELECT monto, estado FROM facturas WHERE id_factura = ?");
    $stmtFactura->execute([$idFactura]);
    $factura = $stmtFactura->fetch(PDO::FETCH_ASSOC);
    if (!$factura || $factura['estado'] === 'anulada') {
        return;
    }

    $stmtSuma = $pdo->prepare("SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE id_factura = ? AND estado = 'validado'");
    $stmtSuma->execute([$idFactura]);
    $totalPagado = (float) $stmtSuma->fetchColumn();
    $monto = (float) $factura['monto'];

    if ($totalPagado >= $monto && $monto > 0) {
        $nuevoEstado = 'pagada';
    } elseif ($totalPagado > 0) {
        $nuevoEstado = 'parcial';
    } else {
        // No revertir 'vencida' automáticamente aquí; el recalculo de
        // vencimiento vive en api_facturas.php y corre en cada GET de
        // facturas. Solo evitamos dejarla en 'pagada'/'parcial' huérfana.
        $nuevoEstado = ($factura['estado'] === 'vencida') ? 'vencida' : 'pendiente';
    }

    $stmtUpdate = $pdo->prepare("UPDATE facturas SET estado = ? WHERE id_factura = ?");
    $stmtUpdate->execute([$nuevoEstado, $idFactura]);
}

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT pg.*,
                                c.nombres, c.apellidos, c.cedula,
                                s.direccion_texto,
                                f.periodo AS factura_periodo, f.monto AS factura_monto, f.estado AS factura_estado,
                                u.username AS validado_por
                         FROM pagos pg
                         INNER JOIN clientes c ON c.id_cliente = pg.id_cliente
                         INNER JOIN servicios s ON s.id_servicio = pg.id_servicio
                         LEFT JOIN facturas f ON f.id_factura = pg.id_factura
                         LEFT JOIN usuarios u ON u.id_usuario = pg.id_usuario_valido
                         ORDER BY pg.fecha_pago DESC");
    echo json_encode(['status' => 'success', 'pagos' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

if ($method === 'POST') {
    $id_factura = isset($input['id_factura']) && $input['id_factura'] !== '' ? (int)$input['id_factura'] : null;
    $id_cliente = (int)($input['id_cliente'] ?? 0);
    $id_servicio = (int)($input['id_servicio'] ?? 0);
    $monto = isset($input['monto']) && $input['monto'] !== '' ? (float)$input['monto'] : null;
    $fecha_pago = trim($input['fecha_pago'] ?? '');
    $metodo_pago = trim($input['metodo_pago'] ?? '');
    $referencia_bancaria = trim($input['referencia_bancaria'] ?? '');
    $moneda_pago = trim($input['moneda_pago'] ?? 'USD');
    if (!in_array($moneda_pago, ['USD', 'VES'], true)) {
        $moneda_pago = 'USD';
    }
    $tasa_cambio = isset($input['tasa_cambio']) ? ($input['tasa_cambio'] !== '' ? (float)$input['tasa_cambio'] : null) : null;
    $comprobante_path = trim($input['comprobante_path'] ?? '');
    if ($comprobante_path === '') {
        $comprobante_path = null;
    }
    $motivo_rechazo = trim($input['motivo_rechazo'] ?? '');
    if ($motivo_rechazo === '') {
        $motivo_rechazo = null;
    }
    // Todo pago creado desde el backoffice nace 'pendiente' por defecto,
    // igual que uno creado desde el futuro portal de cliente (Fase 5):
    // la validación es siempre un acto explícito y posterior de Admin.
    $estado = 'pendiente';
    $origen = trim($input['origen'] ?? 'backoffice');
    if (!in_array($origen, ['portal_cliente', 'backoffice'], true)) {
        $origen = 'backoffice';
    }

    if ($id_cliente <= 0 || $id_servicio <= 0 || $monto === null || $monto <= 0 || $fecha_pago === '') {
        echo json_encode(['status' => 'error', 'message' => 'Cliente, servicio, monto y fecha de pago son obligatorios']);
        exit;
    }

    $metodosValidos = ['transferencia', 'pago_movil', 'zelle', 'efectivo'];
    if ($metodo_pago !== '' && !in_array($metodo_pago, $metodosValidos, true)) {
        $metodo_pago = '';
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO pagos (id_factura, id_cliente, id_servicio, monto, fecha_pago, metodo_pago, referencia_bancaria, moneda_pago, tasa_cambio, comprobante_path, motivo_rechazo, estado, origen)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ok = $stmt->execute([
            $id_factura,
            $id_cliente,
            $id_servicio,
            $monto,
            $fecha_pago,
            $metodo_pago ?: null,
            $referencia_bancaria ?: null,
            $moneda_pago,
            $tasa_cambio,
            $comprobante_path,
            $motivo_rechazo,
            $estado,
            $origen
        ]);
        echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Pago registrado correctamente, pendiente de validación' : 'No se pudo registrar el pago']);
    } catch (PDOException $e) {
        $sqlCode = (int) ($e->errorInfo[1] ?? 0);
        if ($sqlCode === 1452) {
            echo json_encode(['status' => 'error', 'message' => 'El cliente, servicio o factura seleccionados no son válidos.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error registrando el pago: ' . $e->getMessage()]);
        }
    }
    exit;
}

if ($method === 'PUT') {
    $id = (int)($input['id_pago'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Pago inválido']);
        exit;
    }

    $stmtActual = $pdo->prepare("SELECT * FROM pagos WHERE id_pago = ?");
    $stmtActual->execute([$id]);
    $pagoActual = $stmtActual->fetch(PDO::FETCH_ASSOC);
    if (!$pagoActual) {
        echo json_encode(['status' => 'error', 'message' => 'El pago ya no existe.']);
        exit;
    }

    $nuevoEstado = trim($input['estado'] ?? $pagoActual['estado']);
    $estadosValidos = ['pendiente', 'validado', 'rechazado'];
    if (!in_array($nuevoEstado, $estadosValidos, true)) {
        $nuevoEstado = $pagoActual['estado'];
    }

    $cambioDeEstadoSensible = $nuevoEstado !== $pagoActual['estado'] && in_array($nuevoEstado, ['validado', 'rechazado'], true);

    // PROMPT 14: la visibilidad del módulo Pagos ya es configurable por el
    // Admin vía rol_modulo_pagina (ver requirePageAccess() en el gate de
    // entrada), pero validar/rechazar un pago sigue siendo una acción de
    // negocio reservada exclusivamente al Administrador (Parte 4.7 del
    // documento maestro: "finanzas.validar" no se modela como página, se
    // resuelve verificando roleId===1 explícitamente). Un Operador con la
    // página Pagos habilitada puede listar/crear/editar campos no
    // sensibles, pero nunca puede mover el estado a validado/rechazado.
    if ($cambioDeEstadoSensible && (int) $staffSession['id_rol'] !== 1) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Solo un Administrador puede validar o rechazar un pago.']);
        exit;
    }

    $id_factura = isset($input['id_factura']) && $input['id_factura'] !== '' ? (int)$input['id_factura'] : null;
    $id_cliente = (int)($input['id_cliente'] ?? $pagoActual['id_cliente']);
    $id_servicio = (int)($input['id_servicio'] ?? $pagoActual['id_servicio']);
    $monto = isset($input['monto']) && $input['monto'] !== '' ? (float)$input['monto'] : (float)$pagoActual['monto'];
    $fecha_pago = trim($input['fecha_pago'] ?? $pagoActual['fecha_pago']);
    $metodo_pago = trim($input['metodo_pago'] ?? ($pagoActual['metodo_pago'] ?? ''));
    $referencia_bancaria = trim($input['referencia_bancaria'] ?? ($pagoActual['referencia_bancaria'] ?? ''));
    $moneda_pago = trim($input['moneda_pago'] ?? ($pagoActual['moneda_pago'] ?? 'USD'));
    if (!in_array($moneda_pago, ['USD', 'VES'], true)) {
        $moneda_pago = 'USD';
    }
    if (array_key_exists('tasa_cambio', $input)) {
        $tasa_cambio = $input['tasa_cambio'] !== '' ? (float)$input['tasa_cambio'] : null;
    } else {
        $tasa_cambio = $pagoActual['tasa_cambio'] !== null ? (float)$pagoActual['tasa_cambio'] : null;
    }
    $comprobante_path = trim($input['comprobante_path'] ?? ($pagoActual['comprobante_path'] ?? ''));
    if ($comprobante_path === '') {
        $comprobante_path = null;
    }
    $motivo_rechazo = trim($input['motivo_rechazo'] ?? ($pagoActual['motivo_rechazo'] ?? ''));
    if ($motivo_rechazo === '') {
        $motivo_rechazo = null;
    }

    $metodosValidos = ['transferencia', 'pago_movil', 'zelle', 'efectivo'];
    if ($metodo_pago !== '' && !in_array($metodo_pago, $metodosValidos, true)) {
        $metodo_pago = '';
    }

    $pdo->beginTransaction();
    try {
        if ($cambioDeEstadoSensible) {
            $idUsuarioValido = $staffSession['id_usuario'];
            $stmt = $pdo->prepare("UPDATE pagos SET id_factura = ?, id_cliente = ?, id_servicio = ?, monto = ?, fecha_pago = ?, metodo_pago = ?, referencia_bancaria = ?, moneda_pago = ?, tasa_cambio = ?, comprobante_path = ?, motivo_rechazo = ?, estado = ?, fecha_validacion = NOW(), id_usuario_valido = ? WHERE id_pago = ?");
            $stmt->execute([$id_factura, $id_cliente, $id_servicio, $monto, $fecha_pago, $metodo_pago ?: null, $referencia_bancaria ?: null, $moneda_pago, $tasa_cambio, $comprobante_path, $motivo_rechazo, $nuevoEstado, $idUsuarioValido, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE pagos SET id_factura = ?, id_cliente = ?, id_servicio = ?, monto = ?, fecha_pago = ?, metodo_pago = ?, referencia_bancaria = ?, moneda_pago = ?, tasa_cambio = ?, comprobante_path = ?, motivo_rechazo = ?, estado = ? WHERE id_pago = ?");
            $stmt->execute([$id_factura, $id_cliente, $id_servicio, $monto, $fecha_pago, $metodo_pago ?: null, $referencia_bancaria ?: null, $moneda_pago, $tasa_cambio, $comprobante_path, $motivo_rechazo, $nuevoEstado, $id]);
        }

        // Reconciliar tanto la factura anterior (si el pago se reasignó a
        // otra factura) como la nueva, para no dejar la vieja con un total
        // desfasado.
        if ($pagoActual['id_factura'] && $pagoActual['id_factura'] != $id_factura) {
            reconciliarFactura($pdo, (int) $pagoActual['id_factura']);
        }
        reconciliarFactura($pdo, $id_factura);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Pago actualizado correctamente']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        $sqlCode = (int) ($e->errorInfo[1] ?? 0);
        if ($sqlCode === 1452) {
            echo json_encode(['status' => 'error', 'message' => 'El cliente, servicio o factura seleccionados no son válidos.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error actualizando el pago: ' . $e->getMessage()]);
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Error actualizando el pago: ' . $e->getMessage()]);
    }
    exit;
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Pago inválido']);
        exit;
    }

    $stmtActual = $pdo->prepare("SELECT id_factura, estado FROM pagos WHERE id_pago = ?");
    $stmtActual->execute([$id]);
    $pagoActual = $stmtActual->fetch(PDO::FETCH_ASSOC);
    if (!$pagoActual) {
        echo json_encode(['status' => 'error', 'message' => 'El pago ya no existe.']);
        exit;
    }

    // Un pago ya validado no se elimina físicamente: rompería la
    // trazabilidad contable frente a la factura conciliada. La vía
    // correcta es rechazarlo (PUT estado='rechazado') si fue un error.
    if ($pagoActual['estado'] === 'validado') {
        echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar un pago ya validado. Márcalo como rechazado si fue un error, para preservar la trazabilidad contable.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM pagos WHERE id_pago = ?");
        $ok = $stmt->execute([$id]);
        if ($ok && $pagoActual['id_factura']) {
            reconciliarFactura($pdo, (int) $pagoActual['id_factura']);
        }
        echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Pago eliminado' : 'No se pudo eliminar']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar el pago: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Método no soportado']);
// Corrección: `id_usuario_valido` ahora se toma de la sesión autenticada
// del administrador que ya pasó `requireStaffAuth($pdo, 1)`, evitando que
// el body HTTP manipule la auditoría del pago en la columna financiera.
