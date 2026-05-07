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
$u  = db()->fetchOne("SELECT id FROM usuarios WHERE id = ?", [$id]);

if (!$u || $id === currentUser()['id']) {
    header('Location: index.php');
    exit;
}

// Soft delete
db()->execute("UPDATE usuarios SET activo = 0 WHERE id = ?", [$id]);

$_SESSION['flash'] = ['type' => 'success', 'msg' => 'Usuario eliminado correctamente.'];
header('Location: index.php');
exit;
