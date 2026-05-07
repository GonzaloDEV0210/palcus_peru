<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
requireLogin();

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (!$q) { echo json_encode([]); exit; }

$term = "%$q%";
$productos = db()->fetchAll(
    "SELECT v.id AS variacion_id, v.talla, v.color, v.stock,
            p.id AS producto_id, p.nombre, p.sku, p.precio_venta, p.imagen_url
     FROM variaciones v
     JOIN productos p ON p.id = v.producto_id
     WHERE v.activo = 1 AND p.activo = 1
       AND (p.nombre LIKE ? OR p.sku LIKE ?)
     LIMIT 10",
    [$term, $term]
);

echo json_encode($productos);
