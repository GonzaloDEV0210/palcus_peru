<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin', 'almacenero');

$activePage = 'productos';
$pageTitle  = 'Nuevo Producto';
$errors     = [];

// SKU generation is now handled via AJAX based on category

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $d = [
    'nombre'        => trim($_POST['nombre']        ?? ''),
    'sku'           => trim($_POST['sku']           ?? ''),
    'descripcion'   => trim($_POST['descripcion']   ?? ''),
    'categoria_id'  => (int)($_POST['categoria_id'] ?? 0) ?: null,
    'precio_compra' => (float)str_replace(',','.',  $_POST['precio_compra'] ?? '0'),
    'precio_venta'  => (float)str_replace(',','.',  $_POST['precio_venta']  ?? '0'),
    'imagen_url'    => trim($_POST['imagen_url']    ?? ''),
    'activo'        => isset($_POST['activo']) ? 1 : 0,
  ];
  if (!$d['nombre'])        $errors[] = 'El nombre es obligatorio.';
  if ($d['precio_venta']<=0) $errors[] = 'El precio de venta debe ser mayor a 0.';
  if ($d['sku'] && db()->fetchOne('SELECT id FROM productos WHERE sku=?', [$d['sku']]))
    $errors[] = 'El SKU ya existe.';

  if (!$errors) {
    db()->execute(
      'INSERT INTO productos (nombre,sku,descripcion,categoria_id,precio_compra,precio_venta,imagen_url,activo)
       VALUES (?,?,?,?,?,?,?,?)',
      [$d['nombre'],$d['sku'],$d['descripcion'],$d['categoria_id'],
       $d['precio_compra'],$d['precio_venta'],$d['imagen_url'],$d['activo']]
    );
    $newId = db()->lastInsertId();
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Producto creado. Ahora agrega las variaciones (talla/color/stock).'];
    header('Location: variaciones.php?id=' . $newId);
    exit;
  }
}

$categorias = db()->fetchAll('SELECT id, nombre FROM categorias WHERE activo=1 ORDER BY nombre');
$defaultSku = ''; // Will be filled by JS
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
    .form-input {width:100%;padding:.625rem .875rem;border:1.5px solid #e5e7eb;border-radius:.625rem;font-size:.9375rem;color:#111827;background:#fafafa;outline:none;transition:border-color .15s,box-shadow .15s;}
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

      <!-- Breadcrumb -->
      <nav class="text-xs text-gray-400 mb-5 flex items-center gap-1.5">
        <a href="index.php" class="hover:text-gray-700">Productos</a>
        <span>/</span><span class="text-gray-700">Nuevo producto</span>
      </nav>

      <?php if ($errors): ?>
      <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3 space-y-1">
        <?php foreach ($errors as $e): ?><p>• <?= e($e) ?></p><?php endforeach; ?>
      </div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

          <!-- Left: main info -->
          <div class="xl:col-span-2 space-y-5">
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
              <h3 class="font-semibold text-gray-900 mb-4">Información del producto</h3>
              <div class="space-y-4">
                <div>
                  <label class="form-label" for="nombre">Nombre <span class="text-red-500">*</span></label>
                  <input id="nombre" name="nombre" class="form-input" required
                    value="<?= e($_POST['nombre'] ?? '') ?>" placeholder="Ej: Polo Manga Corta Básico"/>
                </div>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="form-label" for="sku">SKU (Automático)</label>
                    <input id="sku" name="sku" class="form-input font-mono bg-gray-100 cursor-not-allowed" readonly
                      value="<?= e($_POST['sku'] ?? $defaultSku) ?>" placeholder="Selecciona una categoría..."/>
                  </div>
                  <div>
                    <label class="form-label" for="categoria_id">Categoría</label>
                    <select id="categoria_id" name="categoria_id" class="form-input">
                      <option value="">Sin categoría</option>
                      <?php foreach ($categorias as $cat): ?>
                      <option value="<?= $cat['id'] ?>" <?= ($_POST['categoria_id']??'')==$cat['id']?'selected':'' ?>>
                        <?= e($cat['nombre']) ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div>
                  <label class="form-label" for="descripcion">Descripción</label>
                  <textarea id="descripcion" name="descripcion" rows="3" class="form-input resize-none"
                    placeholder="Descripción opcional del producto..."><?= e($_POST['descripcion'] ?? '') ?></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Right: price, image, status -->
          <div class="space-y-5">
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
              <h3 class="font-semibold text-gray-900 mb-4">Precios</h3>
              <div class="space-y-4">
                <div>
                  <label class="form-label" for="precio_compra">Precio de compra (S/)</label>
                  <input id="precio_compra" name="precio_compra" type="number" step="0.01" min="0"
                    class="form-input" value="<?= e($_POST['precio_compra'] ?? '0.00') ?>"/>
                </div>
                <div>
                  <label class="form-label" for="precio_venta">Precio de venta (S/) <span class="text-red-500">*</span></label>
                  <input id="precio_venta" name="precio_venta" type="number" step="0.01" min="0.01"
                    class="form-input" value="<?= e($_POST['precio_venta'] ?? '') ?>" required/>
                </div>
                <!-- Margen estimado -->
                <div id="margen" class="text-xs text-gray-400 hidden">Margen estimado: <span id="margenVal" class="font-semibold"></span></div>
              </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-5">
              <h3 class="font-semibold text-gray-900 mb-4">Imagen</h3>
              <div>
                <label class="form-label" for="imagen_url">URL de imagen</label>
                <input id="imagen_url" name="imagen_url" type="url" class="form-input"
                  value="<?= e($_POST['imagen_url'] ?? '') ?>" placeholder="https://..."/>
              </div>
              <div id="imgPreview" class="mt-3 hidden">
                <img id="imgPreviewEl" src="" alt="" class="w-full h-40 object-cover rounded-xl border border-gray-200"/>
              </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-5">
              <h3 class="font-semibold text-gray-900 mb-3">Estado</h3>
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="activo" value="1" <?= ($_POST['activo']??'1')?'checked':'' ?>
                  class="w-4 h-4 rounded border-gray-300 text-gray-900"/>
                <span class="text-sm text-gray-700">Producto activo y visible</span>
              </label>
            </div>

            <div class="flex gap-3">
              <a href="index.php" class="flex-1 text-center py-2.5 border border-gray-200 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors">
                Cancelar
              </a>
              <button type="submit" class="flex-1 bg-gray-900 hover:bg-gray-700 text-white text-sm font-semibold py-2.5 rounded-xl transition-colors">
                Crear y agregar variaciones →
              </button>
            </div>
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
    if (!cid) {
        skuInput.value = 'SKU-00000'; 
        return; 
    }
    try {
        const resp = await fetch('ajax_next_sku.php?categoria_id=' + cid);
        const res = await resp.json();
        if (res.sku) skuInput.value = res.sku;
    } catch (e) { console.error(e); }
}
catSelect.addEventListener('change', updateSku);
if (catSelect.value) updateSku();
else skuInput.value = 'SKU-00000';

// Image preview
document.getElementById('imagen_url').addEventListener('input', function() {
  const el = document.getElementById('imgPreviewEl');
  const wrap = document.getElementById('imgPreview');
  if (this.value) { el.src = this.value; wrap.classList.remove('hidden'); }
  else wrap.classList.add('hidden');
});

// Margin estimator
const pc = document.getElementById('precio_compra');
const pv = document.getElementById('precio_venta');
function updateMargen() {
  const c = parseFloat(pc.value)||0, v = parseFloat(pv.value)||0;
  const m = document.getElementById('margen'), mv = document.getElementById('margenVal');
  if (v > 0) {
    const pct = c > 0 ? ((v-c)/c*100).toFixed(1) : 'N/A';
    mv.textContent = 'S/' + (v-c).toFixed(2) + (c>0?' ('+pct+'%)':'');
    m.classList.remove('hidden');
  } else m.classList.add('hidden');
}
if(pc && pv) {
    pc.addEventListener('input', updateMargen); 
    pv.addEventListener('input', updateMargen);
    updateMargen();
}
</script>
</body>
</html>
