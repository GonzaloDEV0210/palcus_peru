<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin'); // Expenses only for admin

$activePage = 'gastos';
$pageTitle  = 'Gastos';

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

// Filters
$search = trim($_GET['q'] ?? '');
$desde  = $_GET['desde'] ?? date('Y-m-01');
$hasta  = $_GET['hasta'] ?? date('Y-m-d');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where = ['1=1'];
$params = [];
if ($search) { $where[] = 'descripcion LIKE ?'; $params[] = "%$search%"; }
if ($desde)  { $where[] = 'fecha >= ?'; $params[] = $desde; }
if ($hasta)  { $where[] = 'fecha <= ?'; $params[] = $hasta; }
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$total = (int)db()->fetchOne("SELECT COUNT(*) AS n FROM gastos $whereSQL", $params)['n'];
$pages = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$gastos = db()->fetchAll(
    "SELECT * FROM gastos $whereSQL ORDER BY fecha DESC, id DESC LIMIT $perPage OFFSET $offset",
    $params
);

$totalMonto = (float)db()->fetchOne("SELECT SUM(monto) AS total FROM gastos $whereSQL", $params)['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= $pageTitle ?> — PalCus Admin</title>
  <link rel="icon" href="https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo.png"/>
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
          <h2 class="text-xl font-bold text-gray-900">Gastos</h2>
          <p class="text-gray-400 text-xs mt-0.5">Total en periodo: <span class="font-bold text-gray-700"><?= money($totalMonto) ?></span></p>
        </div>
        <a href="crear.php" class="bg-gray-900 hover:bg-gray-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition-colors">+ Registrar Gasto</a>
      </div>

      <form method="GET" class="bg-white p-4 rounded-2xl border border-gray-200 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
          <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Descripción</label>
          <input name="q" value="<?= e($search) ?>" placeholder="Buscar..." class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg outline-none focus:border-gray-400"/>
        </div>
        <div>
          <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Desde</label>
          <input type="date" name="desde" value="<?= e($desde) ?>" class="px-3 py-2 text-sm border border-gray-200 rounded-lg outline-none focus:border-gray-400"/>
        </div>
        <div>
          <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Hasta</label>
          <input type="date" name="hasta" value="<?= e($hasta) ?>" class="px-3 py-2 text-sm border border-gray-200 rounded-lg outline-none focus:border-gray-400"/>
        </div>
        <button type="submit" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg">Filtrar</button>
      </form>

      <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 text-gray-500 text-xs font-semibold uppercase tracking-wide">
              <th class="px-5 py-3 text-left">Fecha</th>
              <th class="px-5 py-3 text-left">Descripción / Categoría</th>
              <th class="px-5 py-3 text-right">Monto</th>
              <th class="px-5 py-3 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php if (empty($gastos)): ?>
            <tr><td colspan="4" class="text-center py-12 text-gray-400">No hay gastos registrados.</td></tr>
            <?php else: ?>
            <?php foreach ($gastos as $g): ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3 text-gray-600"><?= dateEs($g['fecha']) ?></td>
              <td class="px-5 py-3">
                <p class="font-medium text-gray-900"><?= e($g['descripcion']) ?></p>
                <p class="text-[10px] text-gray-400 uppercase font-bold"><?= e($g['categoria'] ?: 'General') ?></p>
              </td>
              <td class="px-5 py-3 text-right font-bold text-red-600">- <?= money((float)$g['monto']) ?></td>
              <td class="px-5 py-3 text-center">
                <div class="flex justify-center gap-1">
                  <a href="editar.php?id=<?= $g['id'] ?>" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>
                  <form method="POST" action="eliminar.php" data-confirm="¿Deseas eliminar este registro de gasto?">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/><input type="hidden" name="id" value="<?= $g['id'] ?>"/>
                    <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</div>
<?php include $ADMIN . '/includes/foot.php'; ?>
</body>
</html>
