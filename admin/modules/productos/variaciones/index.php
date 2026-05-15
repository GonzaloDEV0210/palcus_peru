<?php
$ADMIN = dirname(dirname(dirname(__DIR__)));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();

$producto_id = (int)($_GET['producto_id'] ?? 0);
if (!$producto_id) {
    header('Location: ../');
    exit;
}

$producto = db()->fetchOne("SELECT * FROM productos WHERE id = ?", [$producto_id]);
if (!$producto) {
    header('Location: ../');
    exit;
}

$message = null;
$error   = null;

// ── POST: gestionar variaciones ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_variacion') {
        $id           = (int)($_POST['id'] ?? 0);
        $talla        = trim($_POST['talla'] ?? '');
        $color        = trim($_POST['color'] ?? '');
        $color_hex    = trim($_POST['color_hex'] ?? '#000000');
        $diseno       = trim($_POST['diseno'] ?? '');
        $stock        = (int)($_POST['stock'] ?? 0);
        $stock_minimo = (int)($_POST['stock_minimo'] ?? 0);
        $activo       = isset($_POST['activo']) ? 1 : 0;
        
        // Manejo de Imágenes (Galería JSON)
        $current_gallery = [];
        if ($id) {
            $old = db()->fetchOne("SELECT imagen_url FROM variaciones WHERE id = ?", [$id]);
            if ($old && !empty($old['imagen_url'])) {
                $decoded = json_decode($old['imagen_url'], true);
                $current_gallery = is_array($decoded) ? $decoded : [$old['imagen_url']];
            }
        }

        // 1. Eliminar imágenes marcadas (si existe lógica en el futuro, por ahora mantenemos las que no se borren)
        if (isset($_POST['remove_images'])) {
            $to_remove = $_POST['remove_images']; // Array de URLs
            $current_gallery = array_values(array_diff($current_gallery, $to_remove));
        }

        // 2. Procesar nuevas imágenes subidas
        if (!empty($_FILES['fotos']['name'][0])) {
            $files = $_FILES['fotos'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $singleFile = [
                        'tmp_name' => $files['tmp_name'][$i],
                        'error'    => $files['error'][$i],
                        'name'     => $files['name'][$i],
                        'size'     => $files['size'][$i]
                    ];
                    $url = cloudinaryUpload($singleFile);
                    if ($url) {
                        $current_gallery[] = $url;
                    }
                }
            }
        }

        $imagen_url_final = json_encode($current_gallery);

        if (!$talla || !$color) {
            $error = 'Talla y Color son obligatorios.';
            goto end_post;
        }

        $sku_variacion = generarSkuVariacion($producto['sku'], $talla, $color);
        $existing = db()->fetchOne("SELECT id FROM variaciones WHERE sku = ? AND id != ?", [$sku_variacion, $id]);
        if ($existing) {
            $error = "Ya existe una variación con el SKU {$sku_variacion}.";
            goto end_post;
        }

        if ($id) {
            db()->execute(
                "UPDATE variaciones SET sku=?, talla=?, color=?, color_hex=?, diseno=?, stock=?, stock_minimo=?, imagen_url=?, activo=? WHERE id=?",
                [$sku_variacion, $talla, $color, $color_hex, $diseno, $stock, $stock_minimo, $imagen_url_final, $activo, $id]
            );
            $message = "Variación actualizada correctamente.";
        } else {
            db()->execute(
                "INSERT INTO variaciones (sku, producto_id, talla, color, color_hex, diseno, stock, stock_minimo, imagen_url, activo)
                 VALUES (?,?,?,?,?,?,?,?,?,?)",
                [$sku_variacion, $producto_id, $talla, $color, $color_hex, $diseno, $stock, $stock_minimo, $imagen_url_final, $activo]
            );
            $message = "Variación creada con éxito.";
        }
    }

    if ($action === 'delete_variacion') {
        $id = (int)($_POST['id'] ?? 0);
        db()->execute("DELETE FROM variaciones WHERE id = ?", [$id]);
        $message = "Variación eliminada.";
    }
}
end_post:

$variaciones = db()->fetchAll("SELECT * FROM variaciones WHERE producto_id = ? ORDER BY color ASC, talla ASC", [$producto_id]);
$activePage = 'productos';
$pageTitle  = 'Variaciones de ' . $producto['nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= $pageTitle ?> — Palcus Peru</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>
    body { font-family:'Outfit',sans-serif; background:#f8fafc; }
    .premium-card { background:white; border-radius:1.5rem; border:1px solid #e2e8f0; box-shadow:0 4px 24px -4px rgba(0,0,0,.04); }
    .glass-input { background:#f1f5f9; border:2px solid transparent; border-radius:1rem; padding:.65rem 1rem; font-size:.9rem; transition:all .25s; width:100%; outline:none; }
    .glass-input:focus { background:white; border-color:#3b82f6; box-shadow:0 0 0 4px rgba(59,130,246,.1); }
    .sku-badge { font-family:monospace; font-size:.75rem; background:#0f172a; color:#94a3b8; padding:.25rem .6rem; border-radius:.5rem; }
    .sku-badge strong { color:#e2e8f0; }
    .btn-primary { background:#0f172a; color:white; border-radius:1rem; padding:.7rem 1.75rem; font-weight:700; font-size:.875rem; border:none; cursor:pointer; transition:all .25s; }
    .btn-primary:hover { transform:translateY(-1px); box-shadow:0 8px 20px -4px rgba(0,0,0,.25); }
    .btn-upload { border: 2px dashed #cbd5e1; border-radius:1rem; padding: 1.5rem; text-align:center; cursor:pointer; transition:all .2s; }
    .btn-upload:hover { border-color:#3b82f6; background:#eff6ff; }
    .img-preview-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 0.5rem; }
    .img-thumb { position:relative; aspect-ratio:1; border-radius:0.5rem; overflow:hidden; border:1px solid #e2e8f0; }
    .img-thumb img { width:100%; height:100%; object-fit:cover; }
    .img-remove { position:absolute; top:2px; right:2px; background:rgba(255,255,255,0.9); color:#ef4444; border-radius:50%; width:18px; height:18px; display:flex; align-items:center; justify-content:center; font-size:12px; cursor:pointer; }
  </style>
</head>
<body>
<div class="flex min-h-screen">
  <?php include $ADMIN . '/includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col lg:ml-64 min-w-0">
    <?php include $ADMIN . '/includes/header.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
      
      <nav class="flex items-center gap-2 text-xs font-medium text-slate-400 mb-4">
        <a href="../" class="hover:text-slate-600 transition-colors">Productos</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
        <span class="text-slate-600"><?= e($producto['nombre']) ?></span>
      </nav>

      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
          <div class="flex items-center gap-3 mb-1">
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Variaciones</h1>
            <span class="sku-badge">Prod: <strong><?= e($producto['sku']) ?></strong></span>
          </div>
          <p class="text-slate-500 text-sm">Gestiona tallas, colores y fotos exclusivas por modelo.</p>
        </div>
        <button onclick="openModal()" class="btn-primary flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
          Nueva Variación
        </button>
      </div>

      <?php if ($message): ?>
      <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3 animate-fade-in">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        <span class="font-semibold text-sm"><?= $message ?></span>
      </div>
      <?php endif; ?>

      <div class="premium-card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-slate-50 border-b border-slate-100">
              <tr>
                <th class="py-4 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Fotos / SKU</th>
                <th class="py-4 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Atributos</th>
                <th class="py-4 px-6 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Stock</th>
                <th class="py-4 px-6 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <?php foreach ($variaciones as $v): 
                $gallery = json_decode($v['imagen_url'], true) ?: ($v['imagen_url'] ? [$v['imagen_url']] : []);
                $mainImg = $gallery[0] ?? 'https://via.placeholder.com/100?text=Sin+Foto';
              ?>
              <tr class="hover:bg-slate-50/50 transition-colors">
                <td class="py-4 px-6">
                  <div class="flex items-center gap-3">
                    <img src="<?= e($mainImg) ?>" class="w-12 h-16 object-cover rounded-lg border border-slate-200" alt="">
                    <div>
                      <span class="sku-badge"><strong><?= e($v['sku']) ?></strong></span>
                      <?php if(count($gallery) > 1): ?>
                        <div class="text-[9px] text-slate-400 mt-1 uppercase font-bold">+ <?= count($gallery)-1 ?> fotos adicionales</div>
                      <?php endif; ?>
                    </div>
                  </div>
                </td>
                <td class="py-4 px-6">
                  <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-2 text-sm font-bold text-slate-900">
                      <div class="w-3 h-3 rounded-full border" style="background-color: <?= e($v['color_hex']) ?>;"></div>
                      <?= e($v['color']) ?> / <?= e($v['talla']) ?>
                    </div>
                    <?php if($v['diseno']): ?>
                      <span class="text-[11px] text-slate-500 font-medium">Estilo: <?= e($v['diseno']) ?></span>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="py-4 px-6 text-center">
                  <span class="text-sm font-bold <?= $v['stock'] <= $v['stock_minimo'] ? 'text-red-500' : 'text-slate-900' ?>">
                    <?= $v['stock'] ?> uds
                  </span>
                </td>
                <td class="py-4 px-6 text-right">
                   <div class="flex justify-end gap-2">
                      <button onclick='openModal(<?= json_encode($v) ?>)' class="btn-sm bg-blue-50 text-blue-600 border-blue-100 hover:bg-blue-100">Editar</button>
                      <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar variación?')">
                        <input type="hidden" name="action" value="delete_variacion"/><input type="hidden" name="id" value="<?= $v['id'] ?>"/>
                        <button type="submit" class="btn-sm bg-red-50 text-red-500 border-red-100 hover:bg-red-100">X</button>
                      </form>
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

<!-- ══ MODAL GESTIONAR VARIACIÓN ══ -->
<div id="var-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
  <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-xl p-10 relative max-h-[90vh] overflow-y-auto">
    <button onclick="closeModal()" class="absolute top-8 right-8 text-slate-400 hover:text-slate-900"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
    <h2 id="modal-title" class="text-3xl font-extrabold text-slate-900 mb-8">Variación</h2>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
      <input type="hidden" name="action" value="save_variacion"/>
      <input type="hidden" name="id" id="f-id" value="0"/>

      <div class="grid grid-cols-2 gap-6">
        <div>
          <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Talla *</label>
          <select name="talla" id="f-talla" class="glass-input" required>
            <option value="">— Talla —</option>
            <option value="S">S</option><option value="M">M</option><option value="L">L</option><option value="XL">XL</option><option value="XXL">XXL</option><option value="Estándar">Estándar</option>
          </select>
        </div>
        <div>
          <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Color *</label>
          <input type="text" name="color" id="f-color" class="glass-input" placeholder="ej: Blanco" required/>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-6">
        <div>
          <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Color Visual</label>
          <div class="flex gap-2">
            <input type="color" name="color_hex" id="f-color-hex" class="h-10 w-12 rounded-xl border-none p-0 cursor-pointer" value="#000000"/>
            <input type="text" id="f-color-hex-text" class="glass-input flex-1 uppercase" placeholder="#000000" maxlength="7"/>
          </div>
        </div>
        <div>
          <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Diseño</label>
          <input type="text" name="diseno" id="f-diseno" class="glass-input" placeholder="ej: Bordado"/>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-6">
        <div>
          <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Stock Actual</label>
          <input type="number" name="stock" id="f-stock" class="glass-input" value="0" min="0" required/>
        </div>
        <div>
          <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Stock Mínimo</label>
          <input type="number" name="stock_minimo" id="f-stock-minimo" class="glass-input" value="2" min="0" required/>
        </div>
      </div>

      <!-- CARGA DE FOTOS -->
      <div>
        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Fotos del Modelo</label>
        
        <!-- Galería actual -->
        <div id="current-gallery" class="img-preview-grid mb-4"></div>

        <!-- Uploader -->
        <label class="btn-upload block">
          <input type="file" name="fotos[]" multiple accept="image/*" class="hidden" onchange="previewUploads(this)"/>
          <svg class="w-8 h-8 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          <span class="text-xs font-bold text-slate-500">Subir fotos desde dispositivo</span>
          <p class="text-[10px] text-slate-400 mt-1">Puedes seleccionar varias fotos a la vez.</p>
        </label>
        <div id="new-previews" class="img-preview-grid mt-4"></div>
      </div>

      <div class="flex items-center gap-3 py-2 border-t border-slate-100 pt-6">
        <input type="checkbox" name="activo" id="f-activo" value="1" checked class="w-5 h-5 rounded-lg accent-slate-900"/>
        <label for="f-activo" class="text-sm font-bold text-slate-700 cursor-pointer">Variación activa</label>
      </div>

      <button type="submit" class="btn-primary w-full py-4 text-base shadow-xl shadow-slate-200">
        Guardar Cambios y Subir Fotos
      </button>
    </form>
  </div>
</div>

<script>
function openModal(data = null) {
  const m = document.getElementById('var-modal');
  document.getElementById('modal-title').textContent = data ? 'Editar Variación' : 'Nueva Variación';
  document.getElementById('f-id').value           = data?.id           ?? 0;
  document.getElementById('f-talla').value        = data?.talla        ?? '';
  document.getElementById('f-color').value        = data?.color        ?? '';
  document.getElementById('f-color-hex').value    = data?.color_hex    ?? '#000000';
  document.getElementById('f-color-hex-text').value = data?.color_hex  ?? '#000000';
  document.getElementById('f-diseno').value       = data?.diseno       ?? '';
  document.getElementById('f-stock').value        = data?.stock        ?? 0;
  document.getElementById('f-stock-minimo').value = data?.stock_minimo ?? 2;
  document.getElementById('f-activo').checked     = data ? (data.activo == 1) : true;
  
  // Limpiar galerías
  document.getElementById('new-previews').innerHTML = '';
  const galleryEl = document.getElementById('current-gallery');
  galleryEl.innerHTML = '';
  
  if (data && data.imagen_url) {
      let gallery = [];
      try {
          gallery = JSON.parse(data.imagen_url);
          if (!Array.isArray(gallery)) gallery = [data.imagen_url];
      } catch(e) { gallery = [data.imagen_url]; }
      
      gallery.forEach(url => {
          if(!url) return;
          const div = document.createElement('div');
          div.className = 'img-thumb';
          div.innerHTML = `
            <img src="${url}">
            <input type="hidden" name="keep_images[]" value="${url}">
            <div class="img-remove" onclick="this.parentElement.remove()">×</div>
            <input type="hidden" name="remove_images[]" value="${url}" disabled>
          `;
          // Lógica de borrado: al hacer clic en X, el input remove_images se activa y el div se oculta
          const removeBtn = div.querySelector('.img-remove');
          removeBtn.onclick = () => {
              const hiddenRemove = document.createElement('input');
              hiddenRemove.type = 'hidden';
              hiddenRemove.name = 'remove_images[]';
              hiddenRemove.value = url;
              m.querySelector('form').appendChild(hiddenRemove);
              div.remove();
          };
          galleryEl.appendChild(div);
      });
  }

  m.classList.remove('hidden'); m.classList.add('flex');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  const m = document.getElementById('var-modal');
  m.classList.add('hidden'); m.classList.remove('flex');
  document.body.style.overflow = '';
}

function previewUploads(input) {
  const container = document.getElementById('new-previews');
  container.innerHTML = '';
  if (input.files) {
    [...input.files].forEach(file => {
      const reader = new FileReader();
      reader.onload = (e) => {
        const div = document.createElement('div');
        div.className = 'img-thumb opacity-60';
        div.innerHTML = `<img src="${e.target.result}"><div class="absolute inset-0 flex items-center justify-center text-[8px] font-black text-white bg-black/20">NUEVA</div>`;
        container.appendChild(div);
      };
      reader.readAsDataURL(file);
    });
  }
}

document.getElementById('f-color-hex').addEventListener('input', e => {
    document.getElementById('f-color-hex-text').value = e.target.value.toUpperCase();
});
</script>
</body>
</html>
