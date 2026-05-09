<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
requireLogin();

$filename = "inventario_actual_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, ['Producto', 'SKU', 'Categoría', 'Talla', 'Color', 'Diseño', 'Stock Actual', 'Stock Mínimo', 'Precio Compra', 'Precio Venta']);

$sql = "SELECT v.*, p.nombre AS prod_nombre, p.sku AS prod_sku, p.precio_compra, p.precio_venta, c.nombre AS cat_nombre
        FROM variaciones v
        JOIN productos p ON p.id = v.producto_id
        LEFT JOIN categorias c ON c.id = p.categoria_id
        WHERE v.activo = 1 AND p.activo = 1
        ORDER BY p.nombre ASC, v.talla ASC";

$items = db()->fetchAll($sql);

foreach ($items as $s) {
    fputcsv($output, [
        $s['prod_nombre'],
        $s['prod_sku'] ?: '—',
        $s['cat_nombre'] ?: '—',
        $s['talla'],
        $s['color'],
        $s['diseno'] ?: 'Estándar',
        $s['stock'],
        $s['stock_minimo'],
        number_format((float)$s['precio_compra'], 2, '.', ''),
        number_format((float)$s['precio_venta'], 2, '.', '')
    ]);
}

fclose($output);
exit;
