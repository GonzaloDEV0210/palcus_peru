<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
requireLogin();

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$filename = "ventas_" . $desde . "_a_" . $hasta . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');
// UTF-8 BOM for Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, ['ID Venta', 'Código', 'Fecha', 'Cliente', 'DNI/RUC', 'Vendedor', 'Método Pago', 'Total']);

$where = ['v.fecha >= ?', 'v.fecha <= ?'];
$params = [$desde . ' 00:00:00', $hasta . ' 23:59:59'];
$sql = "SELECT v.*, c.nombre AS cliente_nombre, c.dni_ruc, u.nombre AS vendedor_nombre
        FROM ventas v
        LEFT JOIN clientes c ON c.id = v.cliente_id
        LEFT JOIN usuarios u ON u.id = v.usuario_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY v.fecha DESC";

$ventas = db()->fetchAll($sql, $params);

foreach ($ventas as $v) {
    fputcsv($output, [
        $v['id'],
        $v['codigo_venta'],
        $v['fecha'],
        $v['cliente_nombre'] ?: 'Cliente Final',
        $v['dni_ruc'] ?: '—',
        $v['vendedor_nombre'] ?: 'Sistema',
        $v['metodo_pago'],
        number_format((float)$v['total'], 2, '.', '')
    ]);
}

fclose($output);
exit;
