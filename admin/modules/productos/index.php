<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin', 'almacenero');

$activePage = 'productos';
$pageTitle  = 'Productos';

// Flash message
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Filters
$search  = trim($_GET['q']     ?? '');
$catId   = (int)($_GET['cat']  ?? 0);
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

// Build query
$where  = ['p.activo = 1'];
$params = [];
if ($search) { $where[] = '(p.nombre LIKE ? OR p.sku LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($catId)  { $where[] = 'p.categoria_id = ?'; $params[] = $catId; }
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$total = (int)db()->fetchOne("SELECT COUNT(*) AS n FROM productos p $whereSQL", $params)['n'];
$pages = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$productos = db()->fetchAll(
  "SELECT p.*, c.nombre AS cat_nombre,
          COALESCE(SUM(v.stock),0) AS stock_total,
          COUNT(v.id) AS num_variaciones,
          (SELECT imagen_url FROM variaciones WHERE producto_id = p.id AND imagen_url != '' AND activo = 1 LIMIT 1) as first_var_img
   FROM productos p
   LEFT JOIN categorias c ON c.id = p.categoria_id
   LEFT JOIN variaciones v ON v.producto_id = p.id AND v.activo = 1
   $whereSQL
   GROUP BY p.id ORDER BY p.created_at DESC
   LIMIT $perPage OFFSET $offset",
  $params
);

$categorias = db()->fetchAll('SELECT id, nombre FROM categorias WHERE activo=1 ORDER BY nombre');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= $pageTitle ?> — PalCus Admin</title>
  <link rel="icon" href="https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo.png"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>* {font-family:'Inter',sans-serif;} ::-webkit-scrollbar{width:5px} ::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:9px}</style>
</head>
<body class="bg-gray-50 min-h-screen">
<div id="app-wrapper" class="flex min-h-screen">
  <?php include $ADMIN . '/includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col lg:ml-64 min-w-0">
    <?php include $ADMIN . '/includes/header.php'; ?>
    <main class="flex-1 p-6 space-y-5">


      <!-- Top bar -->
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-xl font-bold text-gray-900">Productos</h2>
          <p class="text-gray-400 text-xs mt-0.5"><?= $total ?> productos registrados</p>
        </div>
        <a href="crear.php" class="inline-flex items-center gap-2 bg-gray-900 hover:bg-gray-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Nuevo producto
        </a>
      </div>

      <!-- Search + Filters -->
      <form method="GET" class="flex flex-wrap gap-3">
        <div class="relative flex-1 min-w-52">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input name="q" value="<?= e($search) ?>" placeholder="Buscar por nombre o SKU…" class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl bg-white focus:outline-none focus:border-gray-400"/>
        </div>
        <select name="cat" class="text-sm border border-gray-200 rounded-xl px-3 py-2.5 bg-white focus:outline-none focus:border-gray-400">
          <option value="">Todas las categorías</option>
          <?php foreach ($categorias as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= $catId==$cat['id']?'selected':'' ?>><?= e($cat['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-xl transition-colors">Filtrar</button>
        <?php if ($search || $catId): ?>
        <a href="index.php" class="px-4 py-2.5 text-gray-500 hover:text-gray-800 text-sm transition-colors">Limpiar</a>
        <?php endif; ?>
      </form>

      <!-- Table -->
      <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 text-gray-500 text-xs font-semibold uppercase tracking-wide">
                <th class="px-5 py-3 text-left">Producto</th>
                <th class="px-5 py-3 text-left">SKU</th>
                <th class="px-5 py-3 text-left">Categoría</th>
                <th class="px-5 py-3 text-right">P. Compra</th>
                <th class="px-5 py-3 text-right">P. Venta</th>
                <th class="px-5 py-3 text-center">Stock</th>
                <th class="px-5 py-3 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php if (empty($productos)): ?>
              <tr><td colspan="7" class="text-center py-12 text-gray-400">No se encontraron productos.</td></tr>
              <?php else: ?>
              <?php foreach ($productos as $p): ?>
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-5 py-3">
                  <div class="flex items-center gap-3">
                    <?php 
                      $displayImg = $p['imagen_url'] ?: $p['first_var_img'];
                      if ($displayImg): 
                    ?>
                    <img src="<?= e($displayImg) ?>" class="w-10 h-10 rounded-lg object-cover border border-gray-100" alt=""/>
                    <?php else: ?>
                    <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-300">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <?php endif; ?>
                    <div>
                      <p class="font-medium text-gray-900"><?= e($p['nombre']) ?></p>
                      <p class="text-xs text-gray-400"><?= $p['num_variaciones'] ?> variación<?= $p['num_variaciones']!=1?'es':'' ?></p>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-3 font-mono text-xs text-gray-600"><?= e($p['sku'] ?? '—') ?></td>
                <td class="px-5 py-3 text-gray-600"><?= e($p['cat_nombre'] ?? '—') ?></td>
                <td class="px-5 py-3 text-right text-gray-600"><?= money((float)$p['precio_compra']) ?></td>
                <td class="px-5 py-3 text-right font-semibold text-gray-900"><?= money((float)$p['precio_venta']) ?></td>
                <td class="px-5 py-3 text-center">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                    <?= $p['stock_total'] > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600' ?>">
                    <?= $p['stock_total'] ?> uds
                  </span>
                </td>
                <td class="px-5 py-3">
                  <div class="flex items-center justify-center gap-1">
                    <a href="variaciones.php?id=<?= $p['id'] ?>" title="Variaciones"
                       class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </a>
                    <a href="editar.php?id=<?= $p['id'] ?>" title="Editar"
                       class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                    
                    <!-- Botón Desactivar (Baja) -->
                    <form method="POST" action="desactivar.php" data-confirm="¿Deseas dar de baja este producto? Dejará de verse en la web pero mantendrás el historial.">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
                      <input type="hidden" name="id" value="<?= $p['id'] ?>"/>
                      <button type="submit" title="Dar de baja"
                        class="p-1.5 text-gray-400 hover:text-amber-500 hover:bg-amber-50 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                      </button>
                    </form>

                    <!-- Botón Eliminar (Total) -->
                    <form method="POST" action="eliminar.php" data-confirm="⚠️ ¡ATENCIÓN! Esta acción eliminará el producto, todas sus variaciones y SUS FOTOS de Cloudinary de forma permanente. ¿Continuar?">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
                      <input type="hidden" name="id" value="<?= $p['id'] ?>"/>
                      <button type="submit" title="Eliminar Permanentemente"
                        class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <div class="flex items-center justify-between px-5 py-3 border-t border-gray-100 bg-gray-50">
          <p class="text-xs text-gray-500">Página <?= $page ?> de <?= $pages ?></p>
          <div class="flex gap-1">
            <?php for ($i=1; $i<=$pages; $i++): ?>
            <a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&cat=<?= $catId ?>"
               class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-medium transition-colors
                      <?= $i===$page ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">
              <?= $i ?>
            </a>
            <?php endfor; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

    </main>
  </div>
</div>
<?php include $ADMIN . '/includes/foot.php'; ?>
</body>
</html>
