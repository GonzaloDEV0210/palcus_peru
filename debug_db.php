<?php
$ADMIN = __DIR__ . '/admin';
require_once $ADMIN . '/config/database.php';
$p = db()->fetchAll("SELECT id, nombre, activo FROM productos");
$v = db()->fetchAll("SELECT id, producto_id, stock, activo FROM variaciones");
echo json_encode(['productos' => $p, 'variaciones' => $v], JSON_PRETTY_PRINT);
