<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
requireLogin();

header('Content-Type: application/json');

$catId = (int)($_GET['categoria_id'] ?? 0);
if (!$catId) { echo json_encode(['sku' => '']); exit; }

$cat = db()->fetchOne("SELECT prefijo FROM categorias WHERE id = ?", [$catId]);
$prefijo = ($cat && $cat['prefijo']) ? $cat['prefijo'] : 'SKU';

$last = db()->fetchOne("SELECT sku FROM productos WHERE categoria_id = ? AND sku LIKE ? ORDER BY id DESC LIMIT 1", [$catId, "$prefijo-%"]);
if ($last) {
    $parts = explode('-', $last['sku']);
    $n = (int)end($parts) + 1;
} else {
    $n = 1;
}

echo json_encode(['sku' => $prefijo . '-' . str_pad($n, 5, '0', STR_PAD_LEFT)]);
