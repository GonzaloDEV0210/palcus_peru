<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin', 'almacenero');

$activePage = 'inventario';
$pageTitle  = 'Movimientos de Inventario (Kardex)';

// Filters
$tipo   = $_GET['tipo'] ?? '';
$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;

$where = ['1=1'];
$params = [];
if ($tipo) { $where[] = 'tipo = ?'; $params[] = $tipo; }
if ($search) {
    $where[] = '(nombre_producto LIKE ? OR talla LIKE ? OR color LIKE ? OR motivo LIKE ?)';
    $term = "%$search%";
    $params = array_merge($params, array_fill(0, 4, $term));
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$total = (int)db()->fetchOne("SELECT COUNT(*) AS n FROM movimientos_inventario $whereSQL", $params)['n'];
$pages = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$movimientos = db()->fetchAll(
    "SELECT m.*, u.nombre AS user_nombre
     FROM movimientos_inventario m
     LEFT JOIN usuarios u ON u.id = m.usuario_id
     $whereSQL
     ORDER BY m.fecha DESC, m.id DESC
     LIMIT $perPage OFFSET $offset",
    $params
);

$tipoColors = [
    'entrada' => 'bg-emerald-100 text-emerald-700',
    'salida'  => 'bg-red-100 text-red-700',
    'ajuste'  => 'bg-amber-100 text-amber-700',
];
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

      <nav class="text-xs text-gray-400 mb-5 flex items-center gap-1.5">
        <a href="index.php" class="hover:text-gray-700">Inventario</a>
        <span>/</span><span class="text-gray-700">Movimientos</span>
      </nav>

      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-xl font-bold text-gray-900">Historial de Movimientos</h2>
          <p class="text-gray-400 text-xs mt-0.5">Kardex detallado de entradas y salidas</p>
        </div>
      </div>

      <form method="GET" class="flex flex-wrap gap-3">
        <select name="tipo" class="px-3 py-2.5 text-sm border border-gray-200 rounded-xl outline-none focus:border-gray-400">
          <option value="">Todos los tipos</option>
          <option value="entrada" <?= $tipo==='entrada'?'selected':'' ?>>Entradas (+)</option>
          <option value="salida" <?= $tipo==='salida'?'selected':'' ?>>Salidas (-)</option>
          <option value="ajuste" <?= $tipo==='ajuste'?'selected':'' ?>>Ajustes</option>
        </select>
        <div class="relative flex-1 min-w-[280px]">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input name="q" value="<?= e($search) ?>" placeholder="Buscar por producto, talla, color o motivo..." class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl outline-none focus:border-gray-400"/>
        </div>
        <button type="submit" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-xl transition-colors">Buscar</button>
      </form>

      <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 text-gray-500 text-[10px] font-bold uppercase tracking-wider">
                <th class="px-5 py-3 text-left">Fecha / Usuario</th>
                <th class="px-5 py-3 text-left">Tipo</th>
                <th class="px-5 py-3 text-left">Producto (Variación)</th>
                <th class="px-5 py-3 text-center">Cant.</th>
                <th class="px-5 py-3 text-center">Stock Antes</th>
                <th class="px-5 py-3 text-center">Stock Después</th>
                <th class="px-5 py-3 text-left">Motivo</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php if (empty($movimientos)): ?>
              <tr><td colspan="7" class="text-center py-12 text-gray-400">No hay movimientos registrados.</td></tr>
              <?php else: ?>
              <?php foreach ($movimientos as $m): ?>
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-5 py-3">
                  <p class="font-medium text-gray-900"><?= date('d/m/Y H:i', strtotime($m['fecha'])) ?></p>
                  <p class="text-[10px] text-gray-400 uppercase font-bold"><?= e($m['user_nombre'] ?: 'Sistema') ?></p>
                </td>
                <td class="px-5 py-3">
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase <?= $tipoColors[$m['tipo']] ?? 'bg-gray-100 text-gray-600' ?>">
                    <?= e($m['tipo']) ?>
                  </span>
                </td>
                <td class="px-5 py-3">
                  <p class="font-medium text-gray-800"><?= e($m['nombre_producto']) ?></p>
                  <p class="text-xs text-gray-500"><?= e($m['talla']) ?> / <?= e($m['color']) ?></p>
                </td>
                <td class="px-5 py-3 text-center">
                  <span class="font-bold <?= $m['tipo']==='entrada'?'text-emerald-600':($m['tipo']==='salida'?'text-red-600':'text-amber-600') ?>">
                    <?= ($m['tipo']==='entrada'?'+':($m['tipo']==='salida'?'-':'')) . abs($m['cantidad']) ?>
                  </span>
                </td>
                <td class="px-5 py-3 text-center text-gray-400"><?= $m['stock_antes'] ?></td>
                <td class="px-5 py-3 text-center font-bold text-gray-900"><?= $m['stock_despues'] ?></td>
                <td class="px-5 py-3 text-gray-600 text-xs italic"><?= e($m['motivo'] ?: '—') ?></td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($pages > 1): ?>
        <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-center gap-1">
          <?php for ($i=1; $i<=$pages; $i++): ?>
          <a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&tipo=<?= $tipo ?>" class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-medium transition-colors <?= $i===$page?'bg-gray-900 text-white':'bg-white text-gray-400 border border-gray-200 hover:bg-gray-50' ?>"><?= $i ?></a>
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
