<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
verifyCsrf();

$id = (int)($_POST['id'] ?? 0);
$v  = db()->fetchOne("SELECT * FROM ventas WHERE id = ?", [$id]);

if (!$v) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Venta no encontrada.'];
    header('Location: index.php');
    exit;
}

// Opcional: Si la venta estaba completada, podrías devolver el stock.
// Pero por ahora, eliminación directa según pidió el usuario.

db()->execute("DELETE FROM ventas WHERE id = ?", [$id]);
// ventas_detalle se borrará por ON DELETE CASCADE si está configurado, 
// si no, lo borramos manualmente:
db()->execute("DELETE FROM ventas_detalle WHERE venta_id = ?", [$id]);

$_SESSION['flash'] = ['type' => 'success', 'msg' => "Venta {$v['codigo']} eliminada correctamente."];
header('Location: index.php');
exit;
