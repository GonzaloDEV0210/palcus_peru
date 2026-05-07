<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin', 'almacenero');

$activePage = 'inventario';
$pageTitle  = 'Control de Inventario';

// Filters
$search = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? ''; // 'low_stock'
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;

$where = ['v.activo = 1', 'p.activo = 1'];
$params = [];
if ($search) {
    $where[] = '(p.nombre LIKE ? OR p.sku LIKE ? OR v.talla LIKE ? OR v.color LIKE ?)';
    $term = "%$search%";
    $params = array_fill(0, 4, $term);
}
if ($filter === 'low_stock') {
    $where[] = 'v.stock <= v.stock_minimo';
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$total = (int)db()->fetchOne("SELECT COUNT(*) AS n FROM variaciones v JOIN productos p ON p.id = v.producto_id $whereSQL", $params)['n'];
$pages = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$stock = db()->fetchAll(
    "SELECT v.*, p.nombre AS prod_nombre, p.sku AS prod_sku, p.imagen_url, c.nombre AS cat_nombre
     FROM variaciones v
     JOIN productos p ON p.id = v.producto_id
     LEFT JOIN categorias c ON c.id = p.categoria_id
     $whereSQL
     ORDER BY p.nombre ASC, v.talla ASC, v.color ASC
     LIMIT $perPage OFFSET $offset",
    $params
);

$lowStockCount = (int)db()->fetchOne("SELECT COUNT(*) AS n FROM variaciones v JOIN productos p ON p.id = v.producto_id WHERE v.activo=1 AND p.activo=1 AND v.stock <= v.stock_minimo")['n'];
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
    <main class="flex-1 p-6 space-y-5">

      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-xl font-bold text-gray-900">Inventario Actual</h2>
          <p class="text-gray-400 text-xs mt-0.5">Stock por variaciones (talla y color)</p>
        </div>
        <div class="flex gap-2">
          <a href="movimientos.php" class="bg-white border border-gray-200 text-gray-700 text-sm font-semibold px-4 py-2.5 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Ver Movimientos (Kardex)
          </a>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="?filter=low_stock" class="bg-white p-4 rounded-2xl border <?= $filter==='low_stock'?'border-amber-500 ring-2 ring-amber-100':'border-gray-200' ?> hover:border-amber-300 transition-all">
          <p class="text-[10px] font-bold text-amber-500 uppercase mb-1">Stock Bajo</p>
          <div class="flex items-center justify-between">
            <p class="text-2xl font-bold text-gray-900"><?= $lowStockCount ?></p>
            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
          </div>
        </a>
      </div>

      <form method="GET" class="flex flex-wrap gap-3">
        <div class="relative flex-1 min-w-[280px]">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input name="q" value="<?= e($search) ?>" placeholder="Buscar por producto, SKU, talla o color..." class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl outline-none focus:border-gray-400"/>
        </div>
        <button type="submit" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-xl transition-colors">Filtrar</button>
        <?php if ($search || $filter): ?>
        <a href="index.php" class="px-4 py-2.5 text-gray-500 hover:text-gray-800 text-sm">Limpiar</a>
        <?php endif; ?>
      </form>

      <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 text-gray-500 text-[10px] font-bold uppercase tracking-wider">
                <th class="px-5 py-3 text-left">Producto</th>
                <th class="px-5 py-3 text-left">Categoría</th>
                <th class="px-5 py-3 text-center">Talla</th>
                <th class="px-5 py-3 text-center">Color</th>
                <th class="px-5 py-3 text-center">Stock Actual</th>
                <th class="px-5 py-3 text-center">Mínimo</th>
                <th class="px-5 py-3 text-center">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php if (empty($stock)): ?>
              <tr><td colspan="7" class="text-center py-12 text-gray-400">No se encontraron registros.</td></tr>
              <?php else: ?>
              <?php foreach ($stock as $s): ?>
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-5 py-3">
                  <div class="flex items-center gap-3">
                    <?php if ($s['imagen_url']): ?>
                    <img src="<?= e($s['imagen_url']) ?>" class="w-10 h-10 rounded-lg object-cover border border-gray-100" alt=""/>
                    <?php else: ?>
                    <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-300"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
                    <?php endif; ?>
                    <div>
                      <p class="font-semibold text-gray-900"><?= e($s['prod_nombre']) ?></p>
                      <p class="text-[10px] text-gray-400 font-mono">SKU: <?= e($s['prod_sku'] ?? '—') ?></p>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-3 text-gray-500"><?= e($s['cat_nombre'] ?? '—') ?></td>
                <td class="px-5 py-3 text-center font-bold text-gray-700"><?= e($s['talla']) ?></td>
                <td class="px-5 py-3 text-center text-gray-600"><?= e($s['color']) ?></td>
                <td class="px-5 py-3 text-center">
                  <span class="text-lg font-bold <?= $s['stock'] <= $s['stock_minimo'] ? 'text-red-500' : 'text-gray-900' ?>">
                    <?= $s['stock'] ?>
                  </span>
                </td>
                <td class="px-5 py-3 text-center text-gray-400 text-xs"><?= $s['stock_minimo'] ?></td>
                <td class="px-5 py-3 text-center">
                  <?php if ($s['stock'] <= 0): ?>
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-red-100 text-red-600 uppercase">Agotado</span>
                  <?php elseif ($s['stock'] <= $s['stock_minimo']): ?>
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 text-amber-600 uppercase">Crítico</span>
                  <?php else: ?>
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-600 uppercase">Suficiente</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-center gap-1">
          <?php for ($i=1; $i<=$pages; $i++): ?>
          <a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&filter=<?= $filter ?>" class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-medium transition-colors <?= $i===$page?'bg-gray-900 text-white':'bg-white text-gray-400 border border-gray-200 hover:bg-gray-50' ?>"><?= $i ?></a>
          <?php endfor; ?>
        </div>
        <?php endif; ?>
      </div>

    </main>
  </div>
</div>
<?php include $ADMIN . '/includes/foot.php'; ?>
</body>
</html>
