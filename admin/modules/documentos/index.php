<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin');

$activePage = 'documentos';
$pageTitle  = 'Gestor de Documentos';

$search = trim($_GET['q'] ?? '');
$where = [];
$params = [];

if ($search) {
    $where[] = "nombre LIKE ?";
    $params[] = "%$search%";
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";
$documentos = db()->fetchAll("SELECT d.*, u.nombre AS usuario_nombre FROM documentos d LEFT JOIN usuarios u ON u.id = d.usuario_id $whereSQL ORDER BY d.created_at DESC", $params);
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

      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Archivo de Documentos</h2>
          <p class="text-gray-400 text-sm">Historial de PDFs, facturas y reportes generados</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-50">
          <form method="GET" class="relative max-w-md">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input name="q" value="<?= e($search) ?>" placeholder="Buscar por nombre de archivo..." class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-xl outline-none focus:border-gray-400 bg-gray-50"/>
          </form>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 text-gray-500 text-[10px] font-bold uppercase tracking-widest">
                <th class="px-6 py-4 text-left">Nombre del Documento</th>
                <th class="px-6 py-4 text-left">Tipo / Referencia</th>
                <th class="px-6 py-4 text-left">Generado por</th>
                <th class="px-6 py-4 text-left">Fecha</th>
                <th class="px-6 py-4 text-right">Acción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <?php if (empty($documentos)): ?>
              <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">No se encontraron documentos registrados.</td></tr>
              <?php endif; ?>
              <?php foreach ($documentos as $d): ?>
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-red-50 text-red-500 flex items-center justify-center">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <span class="font-bold text-gray-900"><?= e($d['nombre']) ?></span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span class="text-[11px] font-bold text-gray-500 uppercase"><?= e($d['referencia_tipo'] ?: $d['tipo']) ?></span>
                </td>
                <td class="px-6 py-4 text-gray-500 text-xs italic">
                  <?= e($d['usuario_nombre'] ?: 'Sistema') ?>
                </td>
                <td class="px-6 py-4 text-gray-400 text-xs">
                  <?= date('d/m/Y H:i', strtotime($d['created_at'])) ?>
                </td>
                <td class="px-6 py-4 text-right">
                  <a href="<?= e($d['drive_url']) ?>" target="_blank" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-[11px] font-bold px-3 py-1.5 rounded-lg transition-colors inline-flex items-center gap-2">
                    Ver en Drive
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </main>
  </div>
</div>
<?php include $ADMIN . '/includes/foot.php'; ?>
</body>
</html>
