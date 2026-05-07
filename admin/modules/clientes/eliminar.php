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
$c  = db()->fetchOne('SELECT id FROM clientes WHERE id=?', [$id]);
if (!$c) { header('Location: index.php'); exit; }

// Soft delete
db()->execute('UPDATE clientes SET activo=0 WHERE id=?', [$id]);

$_SESSION['flash'] = ['type'=>'success','msg'=>'Cliente eliminado correctamente.'];
header('Location: index.php');
exit;
