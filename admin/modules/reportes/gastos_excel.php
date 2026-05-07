<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
requireLogin();

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$filename = "gastos_" . $desde . "_a_" . $hasta . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, ['Fecha', 'Descripción', 'Categoría', 'Método Pago', 'Monto']);

$where = ['fecha >= ?', 'fecha <= ?'];
$params = [$desde, $hasta];
$sql = "SELECT * FROM gastos WHERE " . implode(' AND ', $where) . " ORDER BY fecha DESC";

$gastos = db()->fetchAll($sql, $params);

foreach ($gastos as $g) {
    fputcsv($output, [
        $g['fecha'],
        $g['descripcion'],
        $g['categoria'] ?: 'General',
        $g['metodo_pago'],
        number_format((float)$g['monto'], 2, '.', '')
    ]);
}

fclose($output);
exit;
