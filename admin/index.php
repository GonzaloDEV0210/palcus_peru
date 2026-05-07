<?php
$ADMIN = __DIR__;
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();

$activePage = 'dashboard';
$pageTitle  = 'Dashboard';

// Stats logic
$hoy   = date('Y-m-d');
$mes   = date('Y-m-01');

$ventasHoy   = (float)db()->fetchOne("SELECT SUM(total) AS t FROM ventas WHERE DATE(fecha) = ?", [$hoy])['t'];
$ventasMes   = (float)db()->fetchOne("SELECT SUM(total) AS t FROM ventas WHERE fecha >= ?", [$mes . ' 00:00:00'])['t'];
$gastosMes   = (float)db()->fetchOne("SELECT SUM(monto) AS t FROM gastos WHERE fecha >= ?", [$mes])['t'];
$prodCriticos= (int)db()->fetchOne("SELECT COUNT(*) AS n FROM variaciones WHERE stock <= stock_minimo AND activo=1")['n'];

// Sales Chart Data (last 7 days)
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $val  = (float)db()->fetchOne("SELECT SUM(total) AS t FROM ventas WHERE DATE(fecha) = ?", [$date])['t'];
    $chartData[] = ['label' => date('d M', strtotime($date)), 'value' => $val];
}

// Recent Sales
$recentVentas = db()->fetchAll(
    "SELECT v.*, c.nombre AS cliente_nombre FROM ventas v 
     LEFT JOIN clientes c ON c.id = v.cliente_id 
     ORDER BY v.fecha DESC LIMIT 5"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= $pageTitle ?> — PalCus Admin</title>
  <link rel="icon" href="https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo.png"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>* {font-family:'Inter',sans-serif;}</style>
</head>
<body class="bg-gray-50 min-h-screen">
<div id="app-wrapper" class="flex min-h-screen">
  <?php include $ADMIN . '/includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col lg:ml-64 min-w-0">
    <?php include $ADMIN . '/includes/header.php'; ?>
    
    <main class="flex-1 p-6 space-y-6">
      
      <!-- Welcome -->
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Bienvenido, <?= e(currentUser()['nombre']) ?></h1>
          <p class="text-gray-400 text-sm">Aquí tienes el resumen de hoy, <?= fechaLarga() ?></p>
        </div>
        <div class="flex gap-2">
          <a href="modules/ventas/crear.php" class="bg-gray-900 hover:bg-gray-700 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition-all shadow-sm">+ Nueva Venta</a>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Ventas Hoy</p>
          <div class="flex items-center justify-between">
            <h3 class="text-2xl font-black text-gray-900"><?= money($ventasHoy) ?></h3>
            <div class="w-10 h-10 bg-gray-50 rounded-lg flex items-center justify-center text-gray-800">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
          </div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Ventas Mes</p>
          <h3 class="text-2xl font-black text-gray-900"><?= money($ventasMes) ?></h3>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Gastos Mes</p>
          <h3 class="text-2xl font-black text-red-600"><?= money($gastosMes) ?></h3>
        </div>
        <a href="modules/inventario/index.php?filter=low_stock" class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:border-amber-400 transition-colors">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Stock Bajo</p>
          <div class="flex items-center justify-between">
            <h3 class="text-2xl font-black text-amber-500"><?= $prodCriticos ?></h3>
            <span class="text-[10px] bg-amber-50 text-amber-600 px-2 py-0.5 rounded-full font-bold uppercase">Acción requerida</span>
          </div>
        </a>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        
        <!-- Chart -->
        <div class="xl:col-span-2 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
          <div class="flex items-center justify-between mb-6">
            <h3 class="font-bold text-gray-900">Ventas de la Semana</h3>
            <span class="text-xs text-gray-400">Total ingresos diarios</span>
          </div>
          <div class="h-[300px]">
            <canvas id="salesChart"></canvas>
          </div>
        </div>

        <!-- Recent Sales -->
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
          <h3 class="font-bold text-gray-900 mb-4">Ventas Recientes</h3>
          <div class="space-y-4">
            <?php foreach ($recentVentas as $v): ?>
            <div class="flex items-center justify-between border-b border-gray-50 pb-3 last:border-0 last:pb-0">
              <div>
                <p class="text-sm font-bold text-gray-900"><?= e($v['codigo_venta']) ?></p>
                <p class="text-[10px] text-gray-400 uppercase"><?= e($v['cliente_nombre'] ?: 'Cliente Final') ?></p>
              </div>
              <p class="text-sm font-black text-gray-900"><?= money((float)$v['total']) ?></p>
            </div>
            <?php endforeach; ?>
            <a href="modules/ventas/index.php" class="block text-center text-xs text-gray-400 hover:text-gray-900 pt-2 transition-colors">Ver todas las ventas →</a>
          </div>
        </div>

      </div>

    </main>
  </div>
</div>

<?php include $ADMIN . '/includes/foot.php'; ?>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($chartData, 'label')) ?>,
        datasets: [{
            label: 'Ventas (S/)',
            data: <?= json_encode(array_column($chartData, 'value')) ?>,
            borderColor: '#111827',
            backgroundColor: 'rgba(17, 24, 39, 0.05)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#111827',
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#f3f4f6' } },
            x: { grid: { display: false } }
        }
    }
});
</script>
</body>
</html>
