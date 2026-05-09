<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin', 'almacenero');

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$p  = db()->fetchOne('SELECT * FROM productos WHERE id=? AND activo=1', [$id]);
if (!$p) { header('Location: index.php'); exit; }

$activePage = 'productos';
$pageTitle  = 'Editar Producto';
$errors     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $d = [
    'nombre'        => trim($_POST['nombre']        ?? ''),
    'sku'           => trim($_POST['sku']           ?? ''),
    'categoria_id'  => (int)($_POST['categoria_id'] ?? 0) ?: null,
    'precio_compra' => (float)str_replace(',','.',  $_POST['precio_compra'] ?? '0'),
    'precio_venta'  => (float)str_replace(',','.',  $_POST['precio_venta']  ?? '0'),
    'caracteristicas' => trim($_POST['caracteristicas'] ?? ''),
    'info_modelo'     => trim($_POST['info_modelo']     ?? ''),
    'activo'        => isset($_POST['activo']) ? 1 : 0,
  ];

    $d['imagen_url'] = $p['imagen_url'];
  if (!$d['nombre'])         $errors[] = 'El nombre es obligatorio.';
  if (!$d['categoria_id'])   $errors[] = 'La categoría es obligatoria.';
  if ($d['precio_compra']<=0) $errors[] = 'El precio de compra es obligatorio.';
  if ($d['precio_venta']<=0)  $errors[] = 'El precio de venta debe ser mayor a 0.';
  
  if ($d['sku']) {
    $dup = db()->fetchOne('SELECT id FROM productos WHERE sku=? AND id!=?', [$d['sku'], $id]);
    if ($dup) $errors[] = 'El SKU ya existe en otro producto.';
  }

  if (!$errors) {
    db()->execute(
      'UPDATE productos SET nombre=?,sku=?,descripcion=?,categoria_id=?,precio_compra=?,precio_venta=?,imagen_url=?,caracteristicas=?,info_modelo=?,activo=? WHERE id=?',
      [$d['nombre'],$d['sku'],'', $d['categoria_id'],
       $d['precio_compra'],$d['precio_venta'],$d['imagen_url'],$d['caracteristicas'],$d['info_modelo'],$d['activo'],$id]
    );

    header('Location: index.php');
    exit;
  }
  $p = array_merge($p, $d);
}

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
  <style>* {font-family:'Inter',sans-serif;}
    .form-input{width:100%;padding:.625rem .875rem;border:1.5px solid #e5e7eb;border-radius:.625rem;font-size:.9375rem;color:#111827;background:#fafafa;outline:none;transition:border-color .15s,box-shadow .15s;}
    .form-input:focus{border-color:#111827;background:#fff;box-shadow:0 0 0 3px rgba(17,24,39,.07);}
    .form-label{display:block;font-size:.8125rem;font-weight:600;color:#374151;margin-bottom:.4rem;}
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
<div id="app-wrapper" class="flex min-h-screen">
  <?php include $ADMIN . '/includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col lg:ml-64 min-w-0">
    <?php include $ADMIN . '/includes/header.php'; ?>
    <main class="flex-1 p-6">

      <nav class="text-xs text-gray-400 mb-5 flex items-center gap-1.5">
        <a href="index.php" class="hover:text-gray-700">Productos</a>
        <span>/</span><span class="text-gray-700">Editar</span>
      </nav>

      <?php if ($errors): ?>
      <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3 space-y-1">
        <?php foreach ($errors as $err): ?><p>• <?= e($err) ?></p><?php endforeach; ?>
      </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
        <input type="hidden" name="id" value="<?= $id ?>"/>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
          <div class="xl:col-span-2 space-y-5">
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
              <h3 class="font-semibold text-gray-900 mb-4">Información del producto</h3>
              <div class="space-y-4">
                <div>
                  <label class="form-label">Nombre <span class="text-red-500">*</span></label>
                  <input name="nombre" class="form-input" required value="<?= e($p['nombre']) ?>"/>
                </div>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="form-label">SKU (Automático)</label>
                    <input name="sku" id="sku" class="form-input font-mono bg-gray-100 cursor-not-allowed" readonly value="<?= e($p['sku'] ?? '') ?>"/>
                  </div>
                  <div>
                    <label class="form-label">Categoría <span class="text-red-500">*</span></label>
                    <select id="categoria_id" name="categoria_id" class="form-input" required>
                      <option value="">Seleccione categoría...</option>
                      <?php foreach ($categorias as $cat): ?>
                      <option value="<?= $cat['id'] ?>" <?= $p['categoria_id']==$cat['id']?'selected':'' ?>>
                        <?= e($cat['nombre']) ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div>
                  <label class="form-label">Características del producto</label>
                  <textarea name="caracteristicas" rows="4" class="form-input resize-none" placeholder="Algodón premium, costuras reforzadas..."><?= e($p['caracteristicas'] ?? '') ?></textarea>
                </div>
                <div>
                  <label class="form-label">Información del modelo</label>
                  <textarea name="info_modelo" rows="3" class="form-input resize-none" placeholder="Usa talla S, mide 1.70m..."><?= e($p['info_modelo'] ?? '') ?></textarea>
                </div>
              </div>
            </div>
          </div>

          <div class="space-y-5">
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
              <h3 class="font-semibold text-gray-900 mb-4">Precios</h3>
              <div class="space-y-4">
                <div>
                  <label class="form-label">Precio de compra (S/) <span class="text-red-500">*</span></label>
                  <input name="precio_compra" type="number" step="0.01" min="0.01"
                    class="form-input" value="<?= number_format((float)$p['precio_compra'],2, '.', '') ?>" required/>
                </div>
                <div>
                  <label class="form-label">Precio de venta (S/) <span class="text-red-500">*</span></label>
                  <input name="precio_venta" type="number" step="0.01" min="0.01"
                    class="form-input" value="<?= number_format((float)$p['precio_venta'],2, '.', '') ?>" required/>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-5">
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="activo" value="1" <?= $p['activo']?'checked':'' ?>
                  class="w-4 h-4 rounded border-gray-300"/>
                <span class="text-sm text-gray-700 font-medium">Producto activo</span>
              </label>
            </div>

            <div class="flex gap-3 pt-2">
              <a href="index.php" class="flex-1 text-center py-3 border border-gray-200 text-gray-500 text-[10px] font-bold uppercase tracking-wider rounded-xl hover:bg-gray-50 transition-colors">
                Cancelar
              </a>
              <button type="submit" class="flex-1 bg-gray-900 hover:bg-black text-white text-[10px] font-bold uppercase tracking-widest py-3 rounded-xl transition-all shadow-lg shadow-gray-200 active:scale-[0.98]">
                Guardar cambios
              </button>
            </div>

            <a href="variaciones.php?id=<?= $id ?>"
               class="block text-center text-sm text-gray-500 hover:text-gray-800 transition-colors">
              → Gestionar variaciones (tallas/colores)
            </a>
          </div>
        </div>
      </form>
    </main>
  </div>
</div>
<?php include $ADMIN . '/includes/foot.php'; ?>
<script>
// Auto-generate SKU based on category
const catSelect = document.getElementById('categoria_id');
const skuInput = document.getElementById('sku');

async function updateSku() {
    const cid = catSelect.value;
    if (!cid) { skuInput.value = 'SKU-00000'; return; }
    try {
        const resp = await fetch('ajax_next_sku.php?categoria_id=' + cid);
        const res = await resp.json();
        if (res.sku) skuInput.value = res.sku;
    } catch (e) { console.error(e); }
}
catSelect.addEventListener('change', updateSku);

// Local image preview
document.getElementById('foto_input').addEventListener('change', function(e) {
  const file = e.target.files[0];
  const label = document.getElementById('foto_label');
  const preview = document.getElementById('imgPreview');
  const previewEl = document.getElementById('imgPreviewEl');
  
  if (file) {
    label.textContent = file.name;
    const reader = new FileReader();
    reader.onload = function(e) {
      previewEl.src = e.target.result;
      preview.classList.remove('hidden');
    }
    reader.readAsDataURL(file);
  } else {
    label.textContent = 'Cambiar Foto';
  }
});
</script>
</body>
</html>
