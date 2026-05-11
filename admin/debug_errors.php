<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Debug Start<br>";

try {
    $ADMIN = __DIR__;
    echo "Including config.php...<br>";
    require_once $ADMIN . '/config/config.php';
    echo "Including database.php...<br>";
    require_once $ADMIN . '/config/database.php';
    echo "Including auth.php...<br>";
    require_once $ADMIN . '/includes/auth.php';
    echo "Including functions.php...<br>";
    require_once $ADMIN . '/includes/functions.php';
    
    echo "All includes successful.<br>";
    
    // Stats logic
    echo "Running stats queries...<br>";
    $hoy   = date('Y-m-d');
    $mes   = date('Y-m-01');
    
    echo "Ventas Hoy... ";
    $ventasHoy   = (float)db()->fetchOne("SELECT SUM(total) AS t FROM ventas WHERE DATE(fecha) = ?", [$hoy])['t'];
    echo "Done.<br>";
    
    echo "Ventas Mes... ";
    $ventasMes   = (float)db()->fetchOne("SELECT SUM(total) AS t FROM ventas WHERE fecha >= ?", [$mes . ' 00:00:00'])['t'];
    echo "Done.<br>";
    
    echo "Gastos Mes... ";
    $gastosMes   = (float)db()->fetchOne("SELECT SUM(monto) AS t FROM gastos WHERE fecha >= ?", [$mes])['t'];
    echo "Done.<br>";
    
    echo "Prod Criticos... ";
    $prodCriticos= (int)db()->fetchOne("SELECT COUNT(*) AS n FROM variaciones WHERE stock <= stock_minimo AND activo=1")['n'];
    echo "Done.<br>";

    // Sales Chart Data (last 7 days)
    echo "Running chart queries... ";
    $chartData = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $val  = (float)db()->fetchOne("SELECT SUM(total) AS t FROM ventas WHERE DATE(fecha) = ?", [$date])['t'];
        $chartData[] = ['label' => date('d M', strtotime($date)), 'value' => $val];
    }
    echo "Done.<br>";

    // Recent Sales
    echo "Running recent sales query... ";
    $recentVentas = db()->fetchAll(
        "SELECT v.*, c.nombre AS cliente_nombre FROM ventas v 
         LEFT JOIN clientes c ON c.id = v.cliente_id 
         ORDER BY v.fecha DESC LIMIT 5"
    );
    echo "Done.<br>";

} catch (Throwable $e) {
    echo "Caught exception: " . $e->getMessage() . "<br>";
    echo "In file: " . $e->getFile() . " on line " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
