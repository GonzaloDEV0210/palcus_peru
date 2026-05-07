<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin', 'vendedor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
verifyCsrf();

$id = (int)($_POST['id'] ?? 0);
$v  = db()->fetchOne("SELECT * FROM ventas WHERE id = ? AND estado = 'pendiente'", [$id]);

if (!$v) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Venta no encontrada o ya confirmada.'];
    header('Location: index.php');
    exit;
}

$pdo = db()->getConnection();
try {
    $pdo->beginTransaction();

    // 1. Update Venta status
    $stmt = $pdo->prepare("UPDATE ventas SET estado = 'completada', usuario_id = ? WHERE id = ?");
    $stmt->execute([currentUser()['id'], $id]);

    // 2. Get details to update stock
    $detalles = db()->fetchAll("SELECT * FROM ventas_detalle WHERE venta_id = ?", [$id]);

    foreach ($detalles as $det) {
        $varId = (int)$det['variacion_id'];
        $cant  = (int)$det['cantidad'];

        // Get current stock and info for Kardex
        $var = db()->fetchOne("SELECT v.*, p.nombre FROM variaciones v JOIN productos p ON p.id = v.producto_id WHERE v.id = ?", [$varId]);
        if (!$var) continue;

        $stockAntes = (int)$var['stock'];
        $stockDesp  = $stockAntes - $cant;

        // Update Stock
        $stmtStock = $pdo->prepare("UPDATE variaciones SET stock = stock - ? WHERE id = ?");
        $stmtStock->execute([$cant, $varId]);

        // Kardex
        $stmtKardex = $pdo->prepare(
            "INSERT INTO movimientos_inventario (tipo, variacion_id, producto_id, nombre_producto, talla, color, cantidad, stock_antes, stock_despues, motivo, usuario_id)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmtKardex->execute([
            'salida', $varId, $var['producto_id'], $var['nombre'], $var['talla'], $var['color'],
            $cant, $stockAntes, $stockDesp, "Confirmación Venta {$v['codigo']}", currentUser()['id']
        ]);
    }

    $pdo->commit();
    $_SESSION['flash'] = ['type' => 'success', 'msg' => "Venta {$v['codigo']} confirmada y stock actualizado."];
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash'] = ['type' => 'error', 'msg' => "Error al confirmar: " . $e->getMessage()];
}

header('Location: index.php');
exit;
