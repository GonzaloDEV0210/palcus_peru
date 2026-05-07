<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin');

$activePage = 'usuarios';
$pageTitle  = 'Gestión de Usuarios';

$usuarios = db()->fetchAll("SELECT * FROM usuarios WHERE activo = 1 ORDER BY nombre ASC");
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
          <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Usuarios del Sistema</h2>
          <p class="text-gray-400 text-sm">Administra los accesos y roles de tu equipo</p>
        </div>
        <a href="crear.php" class="bg-gray-900 hover:bg-gray-700 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition-all shadow-sm">
          + Nuevo Usuario
        </a>
      </div>

      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 text-gray-500 text-[10px] font-bold uppercase tracking-widest">
                <th class="px-6 py-4 text-left">Usuario</th>
                <th class="px-6 py-4 text-left">Rol</th>
                <th class="px-6 py-4 text-left">Contacto</th>
                <th class="px-6 py-4 text-center">Último Acceso</th>
                <th class="px-6 py-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <?php foreach ($usuarios as $u): ?>
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 font-bold">
                      <?= strtoupper(substr($u['nombre'], 0, 1)) ?>
                    </div>
                    <div>
                      <p class="font-bold text-gray-900 leading-none"><?= e($u['nombre']) ?></p>
                      <p class="text-[11px] text-gray-400 mt-1"><?= e($u['email']) ?></p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-tight
                    <?= $u['rol']==='admin' ? 'bg-indigo-50 text-indigo-600' : 'bg-slate-100 text-slate-600' ?>">
                    <?= $u['rol'] ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-gray-500 text-xs">
                  <?= e($u['telefono'] ?: '—') ?>
                </td>
                <td class="px-6 py-4 text-center text-gray-400 text-xs">
                  <?= $u['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acceso'])) : 'Nunca' ?>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex justify-end gap-2">
                    <a href="editar.php?id=<?= $u['id'] ?>" class="p-1.5 text-gray-400 hover:text-gray-900 transition-colors">
                      <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                    <?php if ($u['id'] != $user['id']): ?>
                    <form method="POST" action="eliminar.php" data-confirm="¿Estás seguro de eliminar a este usuario? Perderá el acceso de inmediato.">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
                      <input type="hidden" name="id" value="<?= $u['id'] ?>"/>
                      <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 transition-colors">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                      </button>
                    </form>
                    <?php endif; ?>
                  </div>
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
