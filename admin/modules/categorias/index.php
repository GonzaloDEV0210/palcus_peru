<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin', 'almacenero');

$activePage = 'categorias';
$pageTitle  = 'Categorías';

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$errors = [];

// ── Actions ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {

        $id   = (int)($_POST['id'] ?? 0);
        $nom  = trim($_POST['nombre'] ?? '');
        $pref = trim($_POST['prefijo'] ?? '');
        $desc = trim($_POST['descripcion'] ?? '');
        
        // Validate name and prefijo
        if (!$nom) { $errors[] = 'El nombre es obligatorio.'; }
        if (!$pref) { $errors[] = 'El prefijo es obligatorio.'; }
        // Check unique prefijo
        if ($pref) {
            $exists = db()->fetchOne('SELECT id FROM categorias WHERE prefijo = ? AND activo = 1' . ($id ? ' AND id != ?' : ''), $id ? [$pref, $id] : [$pref]);
            if ($exists) { $errors[] = 'El prefijo ya está en uso.'; }
        }
        if (empty($errors)) {
            if ($id > 0) {
                db()->execute('UPDATE categorias SET nombre=?, prefijo=?, descripcion=? WHERE id=?', [$nom, $pref, $desc, $id]);
                $_SESSION['flash'] = ['type'=>'success', 'msg'=>'Categoría actualizada.'];
            } else {
                db()->execute('INSERT INTO categorias (nombre, prefijo, descripcion) VALUES (?,?,?)', [$nom, $pref, $desc]);
                $_SESSION['flash'] = ['type'=>'success', 'msg'=>'Categoría creada.'];
            }
            header('Location: index.php'); exit;
        }

    }
    
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        // Check if has products
        $has = db()->fetchOne('SELECT id FROM productos WHERE categoria_id=? AND activo=1 LIMIT 1', [$id]);
        if ($has) {
            $_SESSION['flash'] = ['type'=>'error', 'msg'=>'No se puede eliminar: hay productos asociados.'];
        } else {
            db()->execute('UPDATE categorias SET activo=0 WHERE id=?', [$id]);
            $_SESSION['flash'] = ['type'=>'success', 'msg'=>'Categoría eliminada.'];
        }
        header('Location: index.php'); exit;
    }
}

$categorias = db()->fetchAll('SELECT * FROM categorias WHERE activo=1 ORDER BY nombre ASC');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= $pageTitle ?> — PalCus Admin</title>
  <link rel="icon" href="https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo.png"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>* {font-family:'Inter',sans-serif;}
    .form-input {width:100%;padding:.625rem .875rem;border:1.5px solid #e5e7eb;border-radius:.625rem;font-size:.9375rem;color:#111827;background:#fafafa;outline:none;transition:border-color .15s;}
    .form-input:focus{border-color:#111827;background:#fff;}
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
<div id="app-wrapper" class="flex min-h-screen">
  <?php include $ADMIN . '/includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col lg:ml-64 min-w-0">
    <?php include $ADMIN . '/includes/header.php'; ?>
    <main class="flex-1 p-6 space-y-5">

      <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Categorías</h2>
        <button onclick="openModal()" class="bg-gray-900 hover:bg-gray-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-colors">
          + Nueva Categoría
        </button>
      </div>


      <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 text-gray-500 text-xs font-semibold uppercase tracking-wide">
              <th class="px-5 py-3 text-left w-20">Prefijo</th>
              <th class="px-5 py-3 text-left">Nombre</th>
              <th class="px-5 py-3 text-left">Descripción</th>
              <th class="px-5 py-3 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php if (empty($categorias)): ?>
            <tr><td colspan="3" class="text-center py-12 text-gray-400">No hay categorías.</td></tr>
            <?php else: ?>
            <?php foreach ($categorias as $c): ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3 text-gray-600 font-mono text-xs uppercase"><?= e($c['prefijo'] ?: '—') ?></td>
              <td class="px-5 py-3 font-medium text-gray-900"><?= e($c['nombre']) ?></td>
              <td class="px-5 py-3 text-gray-500"><?= e($c['descripcion'] ?: '—') ?></td>
              <td class="px-5 py-3">
                <div class="flex justify-center gap-1">
                  <button onclick='openModal(<?= json_encode($c) ?>)' class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                  </button>
                  <form method="POST" data-confirm="¿Eliminar esta categoría? Esto podría afectar a los productos asociados.">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
                    <input type="hidden" name="action" value="delete"/>
                    <input type="hidden" name="id" value="<?= $c['id'] ?>"/>
                    <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg">
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
    </main>
  </div>
</div>

<!-- Modal -->
<div id="catModal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl">
    <h3 id="modalTitle" class="font-bold text-gray-900 text-lg mb-4">Nueva Categoría</h3>
    <form method="POST" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
      <input type="hidden" name="action" value="save"/>
      <input type="hidden" name="id" id="catId" value="0"/>
      
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre <span class="text-red-500">*</span></label>
        <input name="nombre" id="catNombre" class="form-input" required placeholder="Ej: Polos, Casacas..."/>
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Prefijo (Automático)</label>
        <input name="prefijo" id="catPrefijo" class="form-input font-mono uppercase bg-gray-100 cursor-not-allowed" readonly placeholder="Se generará al escribir el nombre..." maxlength="5"/>
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Descripción</label>
        <textarea name="descripcion" id="catDesc" rows="3" class="form-input resize-none" placeholder="Opcional..."></textarea>
      </div>
      
      <div class="flex gap-3 pt-2">
        <button type="button" onclick="closeModal()" class="flex-1 py-2.5 border border-gray-200 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors">Cancelar</button>
        <button type="submit" class="flex-1 bg-gray-900 text-white text-sm font-semibold py-2.5 rounded-xl hover:bg-gray-700 transition-colors">Guardar</button>
      </div>
    </form>
  </div>
</div>

<?php include $ADMIN . '/includes/foot.php'; ?>
<script>
function openModal(c = null) {
    document.getElementById('modalTitle').textContent = c ? 'Editar Categoría' : 'Nueva Categoría';
    document.getElementById('catId').value = c ? c.id : 0;
    document.getElementById('catNombre').value = c ? c.nombre : '';
    document.getElementById('catPrefijo').value = c ? c.prefijo : '';
    document.getElementById('catDesc').value = c ? c.descripcion : '';
    document.getElementById('catModal').classList.remove('hidden');
}
function closeModal() { document.getElementById('catModal').classList.add('hidden'); }

// Automate Prefix generation
const catNombre = document.getElementById('catNombre');
const catPrefijo = document.getElementById('catPrefijo');
const catIdInput = document.getElementById('catId');

catNombre.addEventListener('input', async function() {
    if (catIdInput.value === '0') {
        const val = this.value.trim();
        if (val.length >= 2) {
            try {
                const resp = await fetch('ajax_generate_prefix.php?nombre=' + encodeURIComponent(val));
                const res = await resp.json();
                if (res.prefix) catPrefijo.value = res.prefix;
            } catch (e) { console.error(e); }
        } else {
            catPrefijo.value = '';
        }
    }
});
</script>
</body>
</html>
