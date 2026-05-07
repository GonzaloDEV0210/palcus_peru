<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
$v  = db()->fetchOne(
    "SELECT v.*, c.nombre AS cliente_nombre, c.ruc AS dni_ruc, c.direccion, c.telefono, u.nombre AS vendedor_nombre
     FROM ventas v
     LEFT JOIN clientes c ON c.id = v.cliente_id
     LEFT JOIN usuarios u ON u.id = v.usuario_id
     WHERE v.id = ?",
    [$id]
);
if (!$v) { header('Location: index.php'); exit; }

$detalles = db()->fetchAll(
    "SELECT d.*, p.nombre AS prod_nombre, p.sku AS prod_sku, va.talla, va.color
     FROM ventas_detalle d
     JOIN variaciones va ON va.id = d.variacion_id
     JOIN productos p ON p.id = va.producto_id
     WHERE d.venta_id = ?",
    [$id]
);

$activePage = 'ventas';
$pageTitle  = 'Detalle de Venta — ' . $v['codigo'];
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
    <main class="flex-1 p-6 space-y-6">

      <nav class="text-xs text-gray-400 flex items-center gap-1.5"><a href="index.php" class="hover:text-gray-700">Ventas</a><span>/</span><span class="text-gray-700"><?= $v['codigo'] ?></span></nav>

      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">Venta <?= e($v['codigo']) ?></h2>
          <p class="text-gray-400 text-sm"><?= date('d M Y, h:i A', strtotime($v['fecha'])) ?></p>
        </div>
        <div class="flex gap-2">
          <a href="imprimir.php?id=<?= $id ?>" target="_blank" class="bg-white border border-gray-200 text-gray-700 text-sm font-semibold px-4 py-2.5 rounded-xl hover:bg-gray-50 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Imprimir Comprobante
          </a>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Details Table -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 font-bold text-gray-900">Productos Vendidos</div>
          <table class="w-full text-sm">
            <thead class="bg-gray-50 text-[10px] text-gray-500 font-bold uppercase">
              <tr>
                <th class="px-5 py-3 text-left">Producto</th>
                <th class="px-5 py-3 text-center">Cant.</th>
                <th class="px-5 py-3 text-right">Precio Unit.</th>
                <th class="px-5 py-3 text-right">Subtotal</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php foreach ($detalles as $d): ?>
              <tr>
                <td class="px-5 py-4">
                  <p class="font-bold text-gray-900"><?= e($d['prod_nombre']) ?></p>
                  <p class="text-[10px] text-gray-400 uppercase font-bold"><?= e($d['talla']) ?> / <?= e($d['color']) ?> (<?= e($d['prod_sku']) ?>)</p>
                </td>
                <td class="px-5 py-4 text-center font-bold text-gray-700"><?= $d['cantidad'] ?></td>
                <td class="px-5 py-4 text-right text-gray-600"><?= money((float)$d['precio_unitario']) ?></td>
                <td class="px-5 py-4 text-right font-bold text-gray-900"><?= money((float)$d['subtotal']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-gray-50 border-t-2 border-gray-100">
              <tr>
                <td colspan="3" class="px-5 py-4 text-right text-gray-500 font-bold uppercase text-[10px]">Total Pagado</td>
                <td class="px-5 py-4 text-right text-xl font-extrabold text-gray-900"><?= money((float)$v['total']) ?></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Sidebar Info -->
        <div class="space-y-6">
          <div class="bg-white rounded-2xl border border-gray-200 p-5 space-y-4">
            <h3 class="font-bold text-gray-900 flex items-center gap-2">
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              Cliente
            </h3>
            <div class="space-y-1">
              <p class="text-sm font-bold text-gray-900"><?= e($v['cliente_nombre'] ?: 'Cliente Final') ?></p>
              <?php if ($v['dni_ruc']): ?><p class="text-xs text-gray-500 font-mono">DNI/RUC: <?= e($v['dni_ruc']) ?></p><?php endif; ?>
              <?php if ($v['telefono']): ?><p class="text-xs text-gray-500">Tel: <?= e($v['telefono']) ?></p><?php endif; ?>
              <?php if ($v['direccion']): ?><p class="text-xs text-gray-500"><?= e($v['direccion']) ?></p><?php endif; ?>
            </div>
            
            <hr class="border-gray-100"/>
            
            <h3 class="font-bold text-gray-900 flex items-center gap-2">
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
              Pago
            </h3>
            <div class="space-y-1">
              <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Método</p>
              <p class="text-sm font-bold text-gray-900"><?= e($v['metodo_pago']) ?></p>
            </div>
            
            <hr class="border-gray-100"/>
            
            <div class="space-y-1">
              <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Registrado por</p>
              <p class="text-sm text-gray-900"><?= e($v['vendedor_nombre'] ?: 'Sistema') ?></p>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>
</div>
<?php include $ADMIN . '/includes/foot.php'; ?>
</body>
</html>
