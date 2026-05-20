<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();

$message = null;
$error   = null;

// ── Función helper: obtener prefijo de una categoría (sube al padre si no tiene) ──
function getPrefijoCat(int $catId): string {
    $visited = [];
    $id = $catId;
    while ($id && !in_array($id, $visited)) {
        $visited[] = $id;
        $cat = db()->fetchOne("SELECT prefijo, parent_id FROM categorias WHERE id = ?", [$id]);
        if (!$cat) break;
        if (!empty($cat['prefijo'])) return strtoupper($cat['prefijo']);
        $id = $cat['parent_id'] ? (int)$cat['parent_id'] : null;
    }
    return 'GEN';
}

// ── POST: guardar producto ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_producto') {
        $id         = (int)($_POST['id'] ?? 0);
        $nombre     = trim($_POST['nombre'] ?? '');
        $catId      = (int)($_POST['categoria_id'] ?? 0);
        $pventa     = (float)($_POST['precio_venta'] ?? 0);
        $pcompra    = (float)($_POST['precio_compra'] ?? 0);
        $desc       = trim($_POST['descripcion'] ?? '');
        $caract     = trim($_POST['caracteristicas'] ?? '');
        $infoModelo = trim($_POST['info_modelo'] ?? '');
        $activo     = isset($_POST['activo']) ? 1 : 0;

        if (!$nombre || !$catId || $pventa <= 0) {
            $error = 'Nombre, categoría y precio de venta son obligatorios.';
            goto end_post;
        }

        if ($id) {
            // Editar: SKU NO se toca jamás
            db()->execute(
                "UPDATE productos SET nombre=?, categoria_id=?, precio_venta=?, precio_compra=?,
                 descripcion=?, caracteristicas=?, info_modelo=?, activo=?, updated_at=NOW()
                 WHERE id=?",
                [$nombre, $catId, $pventa, $pcompra, $desc, $caract, $infoModelo, $activo, $id]
            );
            $message = "Producto actualizado correctamente.";
        } else {
            // Crear: generar SKU automático e inmutable
            $prefijo = getPrefijoCat($catId);
            $sku     = generarSkuProducto($prefijo);
            db()->execute(
                "INSERT INTO productos (sku, nombre, categoria_id, precio_venta, precio_compra,
                 descripcion, caracteristicas, info_modelo, activo)
                 VALUES (?,?,?,?,?,?,?,?,?)",
                [$sku, $nombre, $catId, $pventa, $pcompra, $desc, $caract, $infoModelo, $activo]
            );
            $message = "Producto creado con SKU <strong>{$sku}</strong> asignado automáticamente.";
        }
    }

    if ($action === 'delete_producto') {
        $id = (int)($_POST['id'] ?? 0);
        $vars = db()->fetchOne("SELECT COUNT(*) as c FROM variaciones WHERE producto_id = ?", [$id]);
        if ($vars['c'] > 0) { $error = 'No se puede eliminar: tiene variaciones con stock. Desactívalo primero.'; goto end_post; }
        db()->execute("DELETE FROM productos WHERE id = ?", [$id]);
        $message = "Producto eliminado.";
    }

    if ($action === 'toggle_activo') {
        $id  = (int)($_POST['id'] ?? 0);
        $val = (int)($_POST['valor'] ?? 0);
        db()->execute("UPDATE productos SET activo = ? WHERE id = ?", [$val, $id]);
        $message = $val ? "Producto activado." : "Producto desactivado.";
    }
}
end_post:

// ── Cargar datos ───────────────────────────────────────────────
$productos = db()->fetchAll("
    SELECT p.*, c.nombre AS cat_nombre,
           (SELECT COUNT(*) FROM variaciones v WHERE v.producto_id = p.id AND v.activo = 1) AS n_variaciones,
           (SELECT COALESCE(SUM(v.stock),0) FROM variaciones v WHERE v.producto_id = p.id AND v.activo = 1) AS stock_total
    FROM productos p
    LEFT JOIN categorias c ON c.id = p.categoria_id
    ORDER BY p.created_at DESC
");

$categorias = db()->fetchAll("SELECT id, nombre, prefijo, parent_id FROM categorias WHERE activo = 1 ORDER BY nombre ASC");

// Construir ruta de categoría para el selector
function catPath(array $all, int $id): string {
    $map = array_column($all, null, 'id');
    $parts = []; $cur = $map[$id] ?? null;
    while ($cur) { array_unshift($parts, $cur['nombre']); $cur = $cur['parent_id'] ? ($map[$cur['parent_id']] ?? null) : null; }
    return implode(' › ', $parts);
}

$editing = isset($_GET['edit']) ? db()->fetchOne("SELECT * FROM productos WHERE id = ?", [(int)$_GET['edit']]) : null;

$activePage = 'productos';
$pageTitle  = 'Gestión de Productos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= $pageTitle ?> — Palcus Peru</title>
  <link rel="icon" href="<?= getConfig('url_icono') ?>"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>
    body { font-family:'Outfit',sans-serif; background:#f8fafc; }
    .premium-card { background:white; border-radius:1.5rem; border:1px solid #e2e8f0; box-shadow:0 4px 24px -4px rgba(0,0,0,.04); }
    .glass-input { background:#f1f5f9; border:2px solid transparent; border-radius:1rem; padding:.65rem 1rem; font-size:.9rem; transition:all .25s; width:100%; outline:none; }
    .glass-input:focus { background:white; border-color:#3b82f6; box-shadow:0 0 0 4px rgba(59,130,246,.1); }
    .sku-badge { font-family:monospace; font-size:.8rem; background:#0f172a; color:#94a3b8; padding:.3rem .75rem; border-radius:.6rem; display:inline-flex; align-items:center; gap:.5rem; }
    .sku-badge strong { color:#e2e8f0; }
    .sku-preview-box { background:#f8fafc; border:2px dashed #e2e8f0; border-radius:1rem; padding:.75rem 1rem; display:flex; align-items:center; gap:.75rem; }
    .stock-ok { background:#ecfdf5; color:#059669; }
    .stock-low { background:#fff7ed; color:#ea580c; }
    .stock-zero { background:#fef2f2; color:#dc2626; }
    .btn-primary { background:#0f172a; color:white; border-radius:1rem; padding:.7rem 1.75rem; font-weight:700; font-size:.875rem; border:none; cursor:pointer; transition:all .25s; }
    .btn-primary:hover { transform:translateY(-1px); box-shadow:0 8px 20px -4px rgba(0,0,0,.25); }
    .btn-sm { border-radius:.6rem; padding:.3rem .75rem; font-size:.75rem; font-weight:600; cursor:pointer; border:1px solid; transition:all .2s; }
    td, th { padding:.75rem 1rem; }
    th { font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; }
    tr:hover td { background:#f8fafc; }
  </style>
</head>
<body>
<div class="flex min-h-screen">
  <?php include $ADMIN . '/includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col lg:ml-64 min-w-0">
    <?php include $ADMIN . '/includes/header.php'; ?>
    <main class="flex-1 p-6 lg:p-10">

      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Productos</h1>
          <p class="text-slate-500 text-sm mt-1">El SKU se genera automáticamente · No es editable · Garantía de unicidad</p>
        </div>
        <button onclick="openModal()" class="btn-primary flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
          Nuevo Producto
        </button>
      </div>

      <?php if ($message): ?>
      <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        <span class="font-semibold text-sm"><?= $message ?></span>
      </div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="mb-6 p-4 bg-red-50 border border-red-100 text-red-700 rounded-2xl flex items-center gap-3">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
        <span class="font-semibold text-sm"><?= e($error) ?></span>
      </div>
      <?php endif; ?>

      <!-- Tabla de productos -->
      <div class="premium-card overflow-hidden">
        <?php if (empty($productos)): ?>
        <div class="text-center py-20">
          <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
          </div>
          <p class="font-bold text-slate-700">No hay productos aún</p>
          <p class="text-slate-400 text-sm mt-1">Crea tu primer producto. El SKU se asignará solo.</p>
          <button onclick="openModal()" class="btn-primary mt-4">Crear primer producto</button>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="border-b border-slate-100">
              <tr>
                <th class="text-left">SKU</th>
                <th class="text-left">Producto</th>
                <th class="text-left">Categoría</th>
                <th class="text-right">Precio</th>
                <th class="text-center">Stock</th>
                <th class="text-center">Estado</th>
                <th class="text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <?php foreach ($productos as $p):
                $stockClass = $p['stock_total'] == 0 ? 'stock-zero' : ($p['stock_total'] <= 5 ? 'stock-low' : 'stock-ok');
              ?>
              <tr>
                <td>
                  <span class="sku-badge"><strong><?= e($p['sku']) ?></strong></span>
                </td>
                <td>
                  <p class="font-semibold text-slate-800 text-sm"><?= e($p['nombre']) ?></p>
                  <p class="text-xs text-slate-400"><?= $p['n_variaciones'] ?> variaciones</p>
                </td>
                <td class="text-sm text-slate-600"><?= e($p['cat_nombre'] ?? '—') ?></td>
                <td class="text-right">
                  <p class="font-bold text-slate-900 text-sm">S/ <?= number_format($p['precio_venta'], 2) ?></p>
                  <?php if ($p['precio_compra'] > 0): ?>
                  <p class="text-xs text-slate-400">Costo: S/ <?= number_format($p['precio_compra'], 2) ?></p>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <span class="<?= $stockClass ?> text-xs font-bold px-2 py-1 rounded-lg">
                    <?= $p['stock_total'] ?> uds
                  </span>
                </td>
                <td class="text-center">
                  <form method="POST" class="inline">
                    <input type="hidden" name="action" value="toggle_activo"/>
                    <input type="hidden" name="id" value="<?= $p['id'] ?>"/>
                    <input type="hidden" name="valor" value="<?= $p['activo'] ? 0 : 1 ?>"/>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold shadow-sm transition-all duration-200 hover:scale-105 active:scale-95 border <?= $p['activo'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border-slate-200 hover:bg-slate-200' ?>" title="<?= $p['activo'] ? 'Desactivar Producto' : 'Activar Producto' ?>">
                      <span class="w-2 h-2 rounded-full <?= $p['activo'] ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' ?>"></span>
                      <?= $p['activo'] ? 'Activo' : 'Inactivo' ?>
                    </button>
                  </form>
                </td>
                <td class="text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button onclick='openModal(<?= json_encode([
                      "id"=>(int)$p["id"],"nombre"=>$p["nombre"],"categoria_id"=>(int)$p["categoria_id"],
                      "precio_venta"=>(float)$p["precio_venta"],"precio_compra"=>(float)$p["precio_compra"],
                      "descripcion"=>$p["descripcion"],"caracteristicas"=>$p["caracteristicas"],
                      "info_modelo"=>$p["info_modelo"],"activo"=>(int)$p["activo"],"sku"=>$p["sku"]
                    ]) ?>)' class="w-8 h-8 rounded-xl flex items-center justify-center bg-blue-50 text-blue-600 border border-blue-200 shadow-sm hover:bg-blue-100 hover:text-blue-700 hover:scale-110 active:scale-95 transition-all duration-200" title="Editar Producto">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                      </svg>
                    </button>
                    <a href="variaciones/?producto_id=<?= $p['id'] ?>" class="w-8 h-8 rounded-xl flex items-center justify-center bg-purple-50 text-purple-600 border border-purple-200 shadow-sm hover:bg-purple-100 hover:text-purple-700 hover:scale-110 active:scale-95 transition-all duration-200" title="Gestionar Variaciones">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m11.142 0L21.75 12l-4.179-2.25m-11.142 0L12 7.5l4.179 2.25m-11.142 0l4.179 2.25m11.142 0l-4.179 2.25m-11.142 0L12 16.5l4.179-2.25" />
                      </svg>
                    </a>
                    <form method="POST" onsubmit="return confirm('¿Eliminar «<?= e($p['nombre']) ?>»? Solo si no tiene variaciones.')" class="inline">
                      <input type="hidden" name="action" value="delete_producto"/>
                      <input type="hidden" name="id" value="<?= $p['id'] ?>"/>
                      <button type="submit" class="w-8 h-8 rounded-xl flex items-center justify-center bg-red-50 text-red-500 border border-red-200 shadow-sm hover:bg-red-100 hover:text-red-600 hover:scale-110 active:scale-95 transition-all duration-200" title="Eliminar Producto">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

    </main>
  </div>
</div>

<!-- ══ MODAL CREAR/EDITAR PRODUCTO ══ -->
<div id="prod-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
  <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl p-8 relative max-h-[90vh] overflow-y-auto">
    <button onclick="closeModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-800">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    <h2 id="modal-title" class="text-2xl font-extrabold text-slate-900 mb-2">Nuevo Producto</h2>

    <!-- SKU display — SOLO LECTURA, siempre visible -->
    <div class="sku-preview-box mb-6">
      <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5a1 1 0 01.707.293l7 7a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A1 1 0 013 8V5a2 2 0 012-2h2z"/></svg>
      <div class="flex-1">
        <p class="text-xs font-black text-slate-400 uppercase tracking-widest">SKU del Producto</p>
        <p id="sku-display" class="font-mono font-bold text-slate-900 text-sm mt-0.5">Se asignará automáticamente al guardar</p>
      </div>
      <span class="text-xs bg-slate-100 text-slate-500 px-2 py-1 rounded-lg font-semibold">🔒 No editable</span>
    </div>

    <form method="POST" id="prod-form" class="space-y-5">
      <input type="hidden" name="action" value="save_producto"/>
      <input type="hidden" name="id" id="f-id" value="0"/>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <!-- Nombre -->
        <div class="md:col-span-2">
          <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Nombre del Producto *</label>
          <input type="text" name="nombre" id="f-nombre" class="glass-input" placeholder="ej: Polo Classic Mujer" required/>
        </div>

        <!-- Categoría -->
        <div class="md:col-span-2">
          <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Categoría *</label>
          <select name="categoria_id" id="f-categoria" class="glass-input" required onchange="previewSku(this.value)">
            <option value="">— Selecciona una categoría —</option>
            <?php foreach ($categorias as $c): ?>
            <option value="<?= $c['id'] ?>"
              data-prefijo="<?= e($c['prefijo'] ?? '') ?>"
              data-path="<?= e(catPath($categorias, $c['id'])) ?>">
              <?= e(catPath($categorias, $c['id'])) ?>
              <?= $c['prefijo'] ? " [{$c['prefijo']}]" : '' ?>
            </option>
            <?php endforeach; ?>
          </select>
          <p class="text-xs text-slate-400 mt-1">El prefijo de la categoría determina el SKU asignado.</p>
        </div>

        <!-- Precios -->
        <div>
          <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Precio de Venta * (S/)</label>
          <input type="number" name="precio_venta" id="f-pventa" class="glass-input" step="0.01" min="0" placeholder="0.00" required/>
        </div>
        <div>
          <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Costo de Compra (S/)</label>
          <input type="number" name="precio_compra" id="f-pcompra" class="glass-input" step="0.01" min="0" placeholder="0.00"/>
          <p class="text-xs text-slate-400 mt-1">Interno. No visible para el cliente.</p>
        </div>

        <!-- Descripción -->
        <div class="md:col-span-2">
          <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Descripción</label>
          <textarea name="descripcion" id="f-desc" class="glass-input resize-none" rows="3" placeholder="Descripción visible en la web..."></textarea>
        </div>

        <!-- Características -->
        <div>
          <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Características</label>
          <textarea name="caracteristicas" id="f-caract" class="glass-input resize-none" rows="2" placeholder="100% algodón, lavado a mano..."></textarea>
        </div>

        <!-- Info modelo -->
        <div>
          <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Info de Modelo</label>
          <textarea name="info_modelo" id="f-modelo" class="glass-input resize-none" rows="2" placeholder="Talla S / 1.62m / 56kg..."></textarea>
        </div>

        <!-- Activo -->
        <div class="flex items-center gap-3 md:col-span-2 pt-1">
          <input type="checkbox" name="activo" id="f-activo" value="1" checked class="w-4 h-4 rounded accent-slate-900"/>
          <label for="f-activo" class="text-sm font-semibold text-slate-700 cursor-pointer">Publicar en la tienda (visible para clientes)</label>
        </div>
      </div>

      <button type="submit" class="btn-primary w-full py-3 text-base">
        Guardar Producto
      </button>
    </form>
  </div>
</div>

<?php include $ADMIN . '/includes/foot.php'; ?>

<script>
const SKU_PREVIEWS = {}; // cache de previews
<?php foreach ($categorias as $c): ?>
<?php if ($c['prefijo']): ?>
SKU_PREVIEWS[<?= $c['id'] ?>] = "<?= e($c['prefijo']) ?>";
<?php endif; ?>
<?php endforeach; ?>

async function previewSku(catId) {
  const display = document.getElementById('sku-display');
  const fId = document.getElementById('f-id').value;
  if (fId && fId !== '0') return; // Editando: SKU ya asignado, no cambia

  if (!catId) { display.textContent = 'Se asignará automáticamente al guardar'; return; }

  // Buscar prefijo del catId o subir al padre
  const opt = document.querySelector(`#f-categoria option[value="${catId}"]`);
  let prefijo = opt?.dataset?.prefijo || '';

  if (!prefijo) {
    display.textContent = 'Sin prefijo — se usará GEN como prefijo';
    return;
  }

  display.textContent = 'Consultando...';
  try {
    const resp = await fetch(`<?= APP_URL ?>/admin/api/get_next_sku.php?prefijo=${prefijo}`);
    const data = await resp.json();
    display.textContent = data.success
      ? `${data.next_sku}  (próximo disponible)`
      : 'Se asignará automáticamente al guardar';
  } catch(e) {
    display.textContent = `${prefijo}-????  (se asignará al guardar)`;
  }
}

function openModal(data = null) {
  const m = document.getElementById('prod-modal');
  document.getElementById('modal-title').textContent = data ? 'Editar Producto' : 'Nuevo Producto';
  document.getElementById('f-id').value        = data?.id       ?? 0;
  document.getElementById('f-nombre').value    = data?.nombre   ?? '';
  document.getElementById('f-categoria').value = data?.categoria_id ?? '';
  document.getElementById('f-pventa').value    = data?.precio_venta  ?? '';
  document.getElementById('f-pcompra').value   = data?.precio_compra ?? '';
  document.getElementById('f-desc').value      = data?.descripcion   ?? '';
  document.getElementById('f-caract').value    = data?.caracteristicas ?? '';
  document.getElementById('f-modelo').value    = data?.info_modelo   ?? '';
  document.getElementById('f-activo').checked  = data ? (data.activo == 1) : true;

  // Mostrar SKU actual si editamos, o previsualizar si es nuevo
  const display = document.getElementById('sku-display');
  if (data?.sku) {
    display.textContent = data.sku + '  (asignado · inmutable)';
    display.style.color = '#0f172a';
  } else {
    display.textContent = 'Se asignará automáticamente al guardar';
    previewSku(data?.categoria_id ?? '');
  }

  m.classList.remove('hidden'); m.classList.add('flex');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  document.getElementById('prod-modal').classList.add('hidden');
  document.getElementById('prod-modal').classList.remove('flex');
  document.body.style.overflow = '';
}
document.getElementById('prod-modal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
</script>
</body>
</html>
