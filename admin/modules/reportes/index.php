<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin');

$activePage = 'reportes';
$pageTitle  = 'Reportes y Exportación';

$mesActual = date('Y-m');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= $pageTitle ?> — PalCus Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>* {font-family:'Inter',sans-serif;}</style>
</head>
<body class="bg-gray-50 min-h-screen">
<div id="app-wrapper" class="flex min-h-screen">
  <?php include $ADMIN . '/includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col lg:ml-64 min-w-0">
    <?php include $ADMIN . '/includes/header.php'; ?>
    <main class="flex-1 p-6 space-y-8">

      <div>
        <h2 class="text-2xl font-bold text-gray-900">Centro de Reportes</h2>
        <p class="text-gray-400 text-sm">Genera y descarga información clave de tu negocio</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <!-- Reporte de Ventas -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6 space-y-4 hover:shadow-md transition-shadow">
          <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          </div>
          <div>
            <h3 class="font-bold text-gray-900 text-lg">Reporte de Ventas</h3>
            <p class="text-gray-400 text-xs">Detalle de ingresos por periodo, cliente y método de pago.</p>
          </div>
          <form action="ventas_excel.php" method="GET" class="space-y-3 pt-2">
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="text-[10px] font-bold text-gray-400 uppercase">Desde</label>
                <input type="date" name="desde" value="<?= $mesActual ?>-01" class="w-full text-xs p-2 border border-gray-100 rounded-lg outline-none"/>
              </div>
              <div>
                <label class="text-[10px] font-bold text-gray-400 uppercase">Hasta</label>
                <input type="date" name="hasta" value="<?= date('Y-m-d') ?>" class="w-full text-xs p-2 border border-gray-100 rounded-lg outline-none"/>
              </div>
            </div>
            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2.5 rounded-xl transition-colors flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
              Descargar Excel (.csv)
            </button>
          </form>
        </div>

        <!-- Reporte de Inventario -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6 space-y-4 hover:shadow-md transition-shadow">
          <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
          </div>
          <div>
            <h3 class="font-bold text-gray-900 text-lg">Reporte de Inventario</h3>
            <p class="text-gray-400 text-xs">Stock actual, valores de compra/venta y alertas de stock bajo.</p>
          </div>
          <div class="pt-2">
            <a href="inventario_excel.php" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2.5 rounded-xl transition-colors flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
              Descargar Stock Completo
            </a>
          </div>
        </div>

        <!-- Reporte de Gastos -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6 space-y-4 hover:shadow-md transition-shadow">
          <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <h3 class="font-bold text-gray-900 text-lg">Reporte de Gastos</h3>
            <p class="text-gray-400 text-xs">Resumen de salidas de dinero por categoría y periodo.</p>
          </div>
          <form action="gastos_excel.php" method="GET" class="space-y-3 pt-2">
            <div class="grid grid-cols-2 gap-2">
              <div>
                <input type="date" name="desde" value="<?= $mesActual ?>-01" class="w-full text-xs p-2 border border-gray-100 rounded-lg outline-none"/>
              </div>
              <div>
                <input type="date" name="hasta" value="<?= date('Y-m-d') ?>" class="w-full text-xs p-2 border border-gray-100 rounded-lg outline-none"/>
              </div>
            </div>
            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2.5 rounded-xl transition-colors flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
              Exportar Gastos
            </button>
          </form>
        </div>

      </div>

    </main>
  </div>
</div>
<?php include $ADMIN . '/includes/foot.php'; ?>
</body>
</html>
