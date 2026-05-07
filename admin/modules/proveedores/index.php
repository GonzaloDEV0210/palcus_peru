<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin', 'almacenero');

$activePage = 'proveedores';
$pageTitle  = 'Proveedores';

// Flash message
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Filters
$search  = trim($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

// Build query
$where  = ['activo = 1'];
$params = [];
if ($search) {
    $where[] = '(nombre LIKE ? OR contacto LIKE ? OR ruc LIKE ? OR email LIKE ?)';
    $term = "%$search%";
    $params = array_fill(0, 4, $term);
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$total = (int)db()->fetchOne("SELECT COUNT(*) AS n FROM proveedores $whereSQL", $params)['n'];
$pages = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$proveedores = db()->fetchAll(
    "SELECT * FROM proveedores $whereSQL ORDER BY nombre ASC LIMIT $perPage OFFSET $offset",
    $params
);
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
          <h2 class="text-xl font-bold text-gray-900">Proveedores</h2>
          <p class="text-gray-400 text-xs mt-0.5"><?= $total ?> proveedores registrados</p>
        </div>
        <a href="crear.php" class="inline-flex items-center gap-2 bg-gray-900 hover:bg-gray-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Nuevo proveedor
        </a>
      </div>

      <!-- Search -->
      <form method="GET" class="flex flex-wrap gap-3">
        <div class="relative flex-1 min-w-52">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input name="q" value="<?= e($search) ?>" placeholder="Buscar por empresa, contacto, RUC o email…" class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl bg-white focus:outline-none focus:border-gray-400"/>
        </div>
        <button type="submit" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-xl transition-colors">Buscar</button>
        <?php if ($search): ?>
        <a href="index.php" class="px-4 py-2.5 text-gray-500 hover:text-gray-800 text-sm transition-colors">Limpiar</a>
        <?php endif; ?>
      </form>

      <!-- Table -->
      <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 text-gray-500 text-xs font-semibold uppercase tracking-wide">
                <th class="px-5 py-3 text-left">Proveedor / Empresa</th>
                <th class="px-5 py-3 text-left">RUC</th>
                <th class="px-5 py-3 text-left">Contacto</th>
                <th class="px-5 py-3 text-left">Categoría / Rubro</th>
                <th class="px-5 py-3 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php if (empty($proveedores)): ?>
              <tr><td colspan="5" class="text-center py-12 text-gray-400">No se encontraron proveedores.</td></tr>
              <?php else: ?>
              <?php foreach ($proveedores as $p): ?>
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-5 py-3">
                  <p class="font-medium text-gray-900"><?= e($p['nombre']) ?></p>
                  <p class="text-xs text-gray-400"><?= e($p['email'] ?: 'Sin correo') ?></p>
                </td>
                <td class="px-5 py-3 font-mono text-xs text-gray-600"><?= e($p['ruc'] ?: '—') ?></td>
                <td class="px-5 py-3 text-gray-600">
                  <p class="font-medium"><?= e($p['contacto'] ?: '—') ?></p>
                  <p class="text-xs"><?= e($p['telefono'] ?: '—') ?></p>
                </td>
                <td class="px-5 py-3 text-gray-600">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-600 uppercase tracking-tight">
                    Proveedor
                  </span>
                </td>
                <td class="px-5 py-3">
                  <div class="flex items-center justify-center gap-1">
                    <a href="editar.php?id=<?= $p['id'] ?>" title="Editar"
                       class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                    <form method="POST" action="eliminar.php" data-confirm="¿Estás seguro de que deseas eliminar este proveedor? Esta acción no se puede deshacer.">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
                      <input type="hidden" name="id" value="<?= $p['id'] ?>"/>
                      <button type="submit" title="Eliminar"
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
            <a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>"
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
