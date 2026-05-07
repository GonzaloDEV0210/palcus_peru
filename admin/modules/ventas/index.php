<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin', 'vendedor');

$activePage = 'ventas';
$pageTitle  = 'Registro de Ventas';

$search = trim($_GET['q'] ?? '');
$desde  = $_GET['desde'] ?? date('Y-m-01');
$hasta  = $_GET['hasta'] ?? date('Y-m-d');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where = ['1=1'];
$params = [];
if ($search) {
    $where[] = '(v.codigo_venta LIKE ? OR c.nombre LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%";
}
if ($desde) { $where[] = 'v.fecha >= ?'; $params[] = $desde . ' 00:00:00'; }
if ($hasta) { $where[] = 'v.fecha <= ?'; $params[] = $hasta . ' 23:59:59'; }
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$total = (int)db()->fetchOne("SELECT COUNT(*) AS n FROM ventas v LEFT JOIN clientes c ON c.id = v.cliente_id $whereSQL", $params)['n'];
$pages = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$ventas = db()->fetchAll(
    "SELECT v.*, c.nombre AS cliente_nombre, u.nombre AS vendedor_nombre
     FROM ventas v
     LEFT JOIN clientes c ON c.id = v.cliente_id
     LEFT JOIN usuarios u ON u.id = v.usuario_id
     $whereSQL
     ORDER BY v.fecha DESC, v.id DESC
     LIMIT $perPage OFFSET $offset",
    $params
);

$metodoColors = [
    'efectivo'       => 'bg-gray-100 text-gray-700',
    'transferencia'  => 'bg-blue-50 text-blue-700',
    'tarjeta'        => 'bg-indigo-50 text-indigo-700',
    'yape'           => 'bg-purple-50 text-purple-700',
    'plin'           => 'bg-cyan-50 text-cyan-700',
    'whatsapp'       => 'bg-emerald-50 text-emerald-700',
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

      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-xl font-bold text-gray-900">Ventas Realizadas</h2>
          <p class="text-gray-400 text-xs mt-0.5">Historial de pedidos y facturación</p>
        </div>
        <a href="crear.php" class="bg-gray-900 hover:bg-gray-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Nueva Venta
        </a>
      </div>

      <form method="GET" class="bg-white p-4 rounded-2xl border border-gray-200 flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
          <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Buscar</label>
          <input name="q" value="<?= e($search) ?>" placeholder="Código o Cliente..." class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg outline-none focus:border-gray-400"/>
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
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 text-gray-500 text-[10px] font-bold uppercase tracking-wider">
                <th class="px-5 py-3 text-left">Código / Fecha</th>
                <th class="px-5 py-3 text-left">Cliente</th>
                <th class="px-5 py-3 text-left">Método Pago</th>
                <th class="px-5 py-3 text-right">Total</th>
                <th class="px-5 py-3 text-center">Estado</th>
                <th class="px-5 py-3 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php if (empty($ventas)): ?>
              <tr><td colspan="6" class="text-center py-12 text-gray-400">No hay ventas registradas.</td></tr>
              <?php else: ?>
              <?php foreach ($ventas as $v): ?>
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-5 py-3">
                  <p class="font-mono text-gray-900 font-bold"><?= e($v['codigo']) ?></p>
                  <p class="text-[10px] text-gray-400 uppercase font-bold"><?= date('d/m/Y H:i', strtotime($v['created_at'])) ?></p>
                </td>
                <td class="px-5 py-3">
                  <p class="text-gray-900 font-medium"><?= e($v['cliente_nombre'] ?: 'Cliente Final') ?></p>
                  <p class="text-[10px] text-gray-400 uppercase font-bold">Vendedor: <?= e($v['vendedor_nombre'] ?: '—') ?></p>
                </td>
                <td class="px-5 py-3">
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase <?= $metodoColors[$v['metodo_pago']] ?? 'bg-gray-100' ?>">
                    <?= e($v['metodo_pago']) ?>
                  </span>
                </td>
                <td class="px-5 py-3 text-right font-bold text-gray-900 text-base"><?= money((float)$v['total']) ?></td>
                <td class="px-5 py-3 text-center">
                  <?php if ($v['estado'] === 'completada'): ?>
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-700 uppercase">Completada</span>
                  <?php elseif ($v['estado'] === 'pendiente'): ?>
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 text-amber-700 uppercase">Pendiente</span>
                  <?php else: ?>
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-red-100 text-red-700 uppercase">Cancelada</span>
                  <?php endif; ?>
                </td>
                <td class="px-5 py-3 text-center">
                  <div class="flex justify-center gap-1">
                    <?php if ($v['estado'] === 'pendiente'): ?>
                    <form method="POST" action="confirmar.php" data-confirm="¿Deseas confirmar este pedido de WhatsApp y descontar stock?">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
                      <input type="hidden" name="id" value="<?= $v['id'] ?>"/>
                      <button type="submit" title="Confirmar Venta" class="p-1.5 text-emerald-500 hover:bg-emerald-50 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                      </button>
                    </form>
                    <?php endif; ?>
                    <a href="detalle.php?id=<?= $v['id'] ?>" title="Ver Detalle" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </a>
                    <a href="imprimir.php?id=<?= $v['id'] ?>" target="_blank" title="Imprimir PDF" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    </a>
                    <?php if (currentRole() === 'admin'): ?>
                    <form method="POST" action="eliminar.php" data-confirm="¿Estás seguro de eliminar esta venta? Esta acción no se puede deshacer.">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
                      <input type="hidden" name="id" value="<?= $v['id'] ?>"/>
                      <button type="submit" title="Eliminar Venta" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                      </button>
                    </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($pages > 1): ?>
        <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-center gap-1">
          <?php for ($i=1; $i<=$pages; $i++): ?>
          <a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&desde=<?= $desde ?>&hasta=<?= $hasta ?>" class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-medium transition-colors <?= $i===$page?'bg-gray-900 text-white':'bg-white text-gray-400 border border-gray-200 hover:bg-gray-50' ?>"><?= $i ?></a>
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
