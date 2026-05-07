<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin', 'almacenero');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
verifyCsrf();

$id = (int)($_POST['id'] ?? 0);
$p  = db()->fetchOne('SELECT id FROM proveedores WHERE id=?', [$id]);
if (!$p) { header('Location: index.php'); exit; }

// Soft delete
db()->execute('UPDATE proveedores SET activo=0 WHERE id=?', [$id]);

$_SESSION['flash'] = ['type'=>'success','msg'=>'Proveedor eliminado correctamente.'];
header('Location: index.php');
exit;
