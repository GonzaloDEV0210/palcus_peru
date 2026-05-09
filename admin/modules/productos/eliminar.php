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
$p  = db()->fetchOne('SELECT * FROM productos WHERE id=?', [$id]);
if (!$p) { header('Location: index.php'); exit; }

// 1. Obtener todas las imágenes de las variaciones vinculadas
$vars = db()->fetchAll('SELECT imagen_url FROM producto_imagenes WHERE producto_id=?', [$id]);

// 2. Borrar imágenes de Cloudinary (Variaciones)
foreach ($vars as $v) {
    if (!empty($v['imagen_url'])) {
        cloudinaryDestroy($v['imagen_url']);
    }
}

// 3. Borrar imagen principal del producto
if (!empty($p['imagen_url'])) {
    cloudinaryDestroy($p['imagen_url']);
}

// 4. Hard Delete (Eliminación total y física de la base de datos)
try {
    // Primero borramos variaciones y movimientos asociados para evitar dejar "basura"
    db()->execute('DELETE FROM variaciones WHERE producto_id=?', [$id]);
    db()->execute('DELETE FROM movimientos_inventario WHERE producto_id=?', [$id]);
    
    // Finalmente el producto
    db()->execute('DELETE FROM productos WHERE id=?', [$id]);

    $_SESSION['flash'] = ['type'=>'success', 'msg'=>'Producto, variaciones e imágenes eliminados permanentemente.'];
} catch (Exception $e) {
    $_SESSION['flash'] = ['type'=>'error', 'msg'=>'Error al eliminar físicamente: ' . $e->getMessage()];
}

header('Location: index.php');
exit;
