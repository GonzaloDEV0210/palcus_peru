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
db()->execute('DELETE FROM gastos WHERE id=?', [$id]);

$_SESSION['flash'] = ['type'=>'success','msg'=>'Gasto eliminado.'];
header('Location: index.php'); exit;
