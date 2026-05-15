<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();

$message = null;
$error   = null;

// ── Función: generar slug desde texto ──────────────────────────
function makeSlug(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = strtr($text, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u']);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    return trim(preg_replace('/[\s-]+/', '-', $text), '-');
}

// ── Función: buildCategoryPath (para mostrar jerarquía) ────────
function getCategoryPath(array $allCats, int $id): string {
    $map = array_column($allCats, null, 'id');
    $parts = [];
    $current = $map[$id] ?? null;
    while ($current) {
        array_unshift($parts, $current['nombre']);
        $current = $current['parent_id'] ? ($map[$current['parent_id']] ?? null) : null;
    }
    return implode(' › ', $parts);
}

// ── Función: generar prefijo único de 3 letras ────────────────
function generateUniquePrefix(string $name, int $excludeId = 0): string {
    $name = mb_strtoupper($name, 'UTF-8');
    // Limpiar: solo letras
    $clean = preg_replace('/[^A-Z]/', '', strtr($name, ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N']));
    
    // Candidato 1: Primeras 3 letras
    $candidate = substr($clean, 0, 3);
    if (strlen($candidate) < 3) $candidate = str_pad($candidate, 3, 'X');

    $exists = db()->fetchOne("SELECT id FROM categorias WHERE prefijo = ? AND id != ?", [$candidate, $excludeId]);
    if (!$exists) return $candidate;

    // Candidato 2: 1ra, 2da y última letra
    $candidate = substr($clean, 0, 2) . substr($clean, -1);
    $exists = db()->fetchOne("SELECT id FROM categorias WHERE prefijo = ? AND id != ?", [$candidate, $excludeId]);
    if (!$exists) return $candidate;

    // Candidato 3: 1ra, 3ra, 5ta
    if (strlen($clean) >= 5) {
        $candidate = $clean[0] . $clean[2] . $clean[4];
        $exists = db()->fetchOne("SELECT id FROM categorias WHERE prefijo = ? AND id != ?", [$candidate, $excludeId]);
        if (!$exists) return $candidate;
    }

    // Fallback: Primeras 2 letras + número secuencial
    $base = substr($clean, 0, 2);
    for ($i = 1; $i <= 9; $i++) {
        $candidate = $base . $i;
        $exists = db()->fetchOne("SELECT id FROM categorias WHERE prefijo = ? AND id != ?", [$candidate, $excludeId]);
        if (!$exists) return $candidate;
    }

    return substr(md5($name), 0, 3); // Último recurso: hash
}

// ── POST: crear / editar / eliminar ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id      = (int)($_POST['id'] ?? 0);
        $nombre  = trim($_POST['nombre'] ?? '');
        $desc    = trim($_POST['descripcion'] ?? '');
        $parent  = ($_POST['parent_id'] ?? '') !== '' ? (int)$_POST['parent_id'] : null;
        $orden   = (int)($_POST['orden'] ?? 0);
        $activo  = isset($_POST['activo']) ? 1 : 0;

        if (!$nombre) { $error = 'El nombre es obligatorio.'; goto end; }

        $slug = makeSlug($nombre);
        $existingSlug = db()->fetchOne("SELECT id FROM categorias WHERE slug = ? AND id != ?", [$slug, $id]);
        if ($existingSlug) $slug .= '-' . substr(uniqid(), -3);

        if ($id) {
            // Editar: NO permitimos editar el prefijo una vez creado para mantener integridad de SKU
            db()->execute(
                "UPDATE categorias SET nombre=?, slug=?, descripcion=?, parent_id=?, orden=?, activo=? WHERE id=?",
                [$nombre, $slug, $desc, $parent, $orden, $activo, $id]
            );
            $message = "Categoría actualizada correctamente.";
        } else {
            // Crear: GENERAR PREFIJO AUTOMÁTICO E INMUTABLE
            $prefijo = generateUniquePrefix($nombre);
            db()->execute(
                "INSERT INTO categorias (nombre, slug, prefijo, descripcion, parent_id, orden, activo) VALUES (?,?,?,?,?,?,?)",
                [$nombre, $slug, $prefijo, $desc, $parent, $orden, $activo]
            );
            $message = "Categoría creada con prefijo automático: <strong>{$prefijo}</strong>";
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $hijos = db()->fetchOne("SELECT COUNT(*) as c FROM categorias WHERE parent_id = ?", [$id]);
        $prods = db()->fetchOne("SELECT COUNT(*) as c FROM productos WHERE categoria_id = ?", [$id]);
        if ($hijos['c'] > 0) { $error = 'No se puede eliminar: tiene subcategorías activas.'; goto end; }
        if ($prods['c'] > 0) { $error = 'No se puede eliminar: tiene productos asignados.'; goto end; }
        db()->execute("DELETE FROM categorias WHERE id = ?", [$id]);
        $message = "Categoría eliminada.";
    }
}
end:

// ── Cargar árbol completo ─────────────────────────────────────
$allCats = db()->fetchAll("SELECT * FROM categorias ORDER BY orden ASC, nombre ASC");
$mapById = array_column($allCats, null, 'id');

// Construir árbol recursivo
function buildTree(array $cats, ?int $parentId = null): array {
    $tree = [];
    foreach ($cats as $c) {
        $pid = $c['parent_id'] ? (int)$c['parent_id'] : null;
        if ($pid === $parentId) {
            $c['children'] = buildTree($cats, (int)$c['id']);
            $tree[] = $c;
        }
    }
    return $tree;
}
$tree = buildTree($allCats);

// Categoría a editar
$editing = null;
if (isset($_GET['edit'])) {
    $editing = $mapById[(int)$_GET['edit']] ?? null;
}

$activePage = 'categorias';
$pageTitle  = 'Gestión de Categorías';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= $pageTitle ?> — Palcus Peru</title>
  <link rel="icon" href="<?= getConfig('url_icono') ?: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo.png' ?>"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>
    body { font-family: 'Outfit', sans-serif; background: #f8fafc; }
    .premium-card { background: white; border-radius: 1.5rem; border: 1px solid #e2e8f0; box-shadow: 0 4px 24px -4px rgba(0,0,0,0.04); }
    .glass-input { background: #f1f5f9; border: 2px solid transparent; border-radius: 1rem; padding: .65rem 1rem; font-size: .9rem; transition: all .25s; width: 100%; outline: none; }
    .glass-input:focus { background: white; border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59,130,246,.1); }
    .btn-primary { background: #0f172a; color: white; border-radius: 1rem; padding: .7rem 1.75rem; font-weight: 700; font-size: .875rem; transition: all .25s; border: none; cursor: pointer; }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 20px -4px rgba(0,0,0,.25); }
    .btn-danger { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; border-radius: .75rem; padding: .4rem .9rem; font-weight: 600; font-size: .75rem; cursor: pointer; transition: all .2s; }
    .btn-danger:hover { background: #fee2e2; }
    .btn-edit { background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe; border-radius: .75rem; padding: .4rem .9rem; font-weight: 600; font-size: .75rem; cursor: pointer; transition: all .2s; }
    .btn-edit:hover { background: #dbeafe; }
    .tree-node { border-left: 2px solid #e2e8f0; margin-left: 1.5rem; padding-left: 1rem; }
    .cat-row { display: flex; align-items: center; gap: .75rem; padding: .75rem 1rem; border-radius: 1rem; transition: background .15s; }
    .cat-row:hover { background: #f8fafc; }
    .level-badge { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; padding: .2rem .55rem; border-radius: 9999px; }
    .badge-0 { background: #0f172a; color: white; }
    .badge-1 { background: #3b82f6; color: white; }
    .badge-2 { background: #8b5cf6; color: white; }
    .badge-3 { background: #ec4899; color: white; }
    .badge-4 { background: #f59e0b; color: white; }
    .inactive-row { opacity: .45; }
    .slug-preview { font-family: monospace; font-size: .75rem; background: #f1f5f9; color: #475569; padding: .25rem .6rem; border-radius: .5rem; display: inline-block; }
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
          <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Categorías</h1>
          <p class="text-slate-500 text-sm mt-1">Árbol jerárquico sin límite de niveles. Define prefijos para el SKU automático.</p>
        </div>
        <button onclick="openModal()" class="btn-primary flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
          Nueva Categoría
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
        <span class="font-semibold text-sm"><?= $error ?></span>
      </div>
      <?php endif; ?>

      <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">

        <!-- Árbol de categorías -->
        <div class="xl:col-span-8">
          <div class="premium-card p-6">
            <div class="flex items-center justify-between mb-5">
              <h2 class="text-lg font-bold text-slate-900">Árbol de Categorías</h2>
              <span class="text-xs text-slate-400 font-medium"><?= count($allCats) ?> categorías en total</span>
            </div>

            <?php if (empty($tree)): ?>
            <div class="text-center py-16">
              <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
              </div>
              <p class="text-slate-500 font-medium">No hay categorías aún</p>
              <p class="text-slate-400 text-sm mt-1">Crea tu primera categoría raíz (ej: Mujer, Hombre)</p>
              <button onclick="openModal()" class="btn-primary mt-4">Crear primera categoría</button>
            </div>
            <?php else: ?>
            <div id="cat-tree">
              <?php renderTree($tree, 0); ?>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Leyenda + Info SKU -->
        <div class="xl:col-span-4 space-y-6">
          <div class="premium-card p-6">
            <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-4">Niveles del Árbol</h3>
            <div class="space-y-3">
              <?php
              $levels = [['Nivel 1','Categoría Raíz','Aparece en el Nav','badge-0'],['Nivel 2','Subcategoría','Mega menú hover','badge-1'],['Nivel 3','Sub-sub','Columna en el panel','badge-2'],['Nivel 4+','Hoja profunda','Sub-ítem en columna','badge-3']];
              foreach ($levels as [$lbl,$name,$hint,$badge]): ?>
              <div class="flex items-center gap-3">
                <span class="level-badge <?= $badge ?>"><?= $lbl ?></span>
                <div>
                  <p class="text-sm font-semibold text-slate-700"><?= $name ?></p>
                  <p class="text-xs text-slate-400"><?= $hint ?></p>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="premium-card p-6">
            <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-4">¿Cómo funciona el SKU?</h3>
            <div class="space-y-3 text-sm text-slate-600">
              <p>El <strong>Prefijo</strong> de la categoría más profunda construye el SKU del producto:</p>
              <div class="bg-slate-50 rounded-xl p-3 space-y-1 font-mono text-xs">
                <p>Manga Corta → prefijo: <strong class="text-blue-600">MCO</strong></p>
                <p>Producto #1 → SKU: <strong class="text-purple-600">MCO-001</strong></p>
                <p>+ Talla M + Blanco → <strong class="text-pink-600">MCO-001-M-BLC</strong></p>
              </div>
              <p class="text-xs text-slate-400">Esto te permite saber exactamente cuántos <em>Polos Manga Corta Blancos Talla M</em> tienes en stock con solo ver el código.</p>
            </div>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- ══════════════════════════════════════
     MODAL CREAR / EDITAR
══════════════════════════════════════ -->
<div id="cat-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
  <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg p-8 relative">
    <button onclick="closeModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-800 transition-colors">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    <h2 id="modal-title" class="text-2xl font-extrabold text-slate-900 mb-6">Nueva Categoría</h2>

    <form method="POST" id="cat-form" class="space-y-5">
      <input type="hidden" name="action" value="save"/>
      <input type="hidden" name="id" id="f-id" value="0"/>

      <!-- Nombre -->
      <div>
        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Nombre *</label>
        <input type="text" name="nombre" id="f-nombre" class="glass-input" placeholder="ej: Manga Corta" required oninput="previewSlug(this.value)"/>
        <p class="mt-1.5 text-xs text-slate-400">Slug generado: <span id="slug-preview" class="slug-preview">—</span></p>
      </div>

      <!-- Prefijo SKU (SOLO LECTURA) -->
      <div>
        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Código de Categoría (Prefijo SKU)</label>
        <div class="relative">
          <input type="text" name="prefijo" id="f-prefijo" class="glass-input pr-10" placeholder="Se generará automáticamente" readonly tabindex="-1" style="background:#f8fafc; color:#94a3b8; cursor:not-allowed;"/>
          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg class="w-4 h-4 text-slate-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
          </div>
        </div>
        <p id="prefijo-hint" class="text-[10px] text-slate-400 mt-1.5">Este código es único, inmutable y se genera automáticamente a partir del nombre.</p>
      </div>

      <!-- Categoría Padre -->
      <div>
        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Categoría Padre</label>
        <select name="parent_id" id="f-parent" class="glass-input">
          <option value="">— Sin padre (Categoría Raíz) —</option>
          <?php foreach ($allCats as $c): ?>
          <option value="<?= $c['id'] ?>" data-path="<?= e(getCategoryPath($allCats, $c['id'])) ?>">
            <?= e(getCategoryPath($allCats, $c['id'])) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Descripción -->
      <div>
        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Descripción</label>
        <textarea name="descripcion" id="f-desc" class="glass-input resize-none" rows="2" placeholder="Descripción breve (opcional)"></textarea>
      </div>

      <!-- Orden + Activo -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Orden en menú</label>
          <input type="number" name="orden" id="f-orden" class="glass-input" value="0" min="0"/>
        </div>
        <div class="flex items-end pb-1">
          <label class="flex items-center gap-2 cursor-pointer select-none">
            <input type="checkbox" name="activo" id="f-activo" value="1" checked class="w-4 h-4 rounded accent-slate-900"/>
            <span class="text-sm font-semibold text-slate-700">Activa / visible</span>
          </label>
        </div>
      </div>

      <button type="submit" class="btn-primary w-full py-3 text-base">
        Guardar Categoría
      </button>
    </form>
  </div>
</div>

<?php include $ADMIN . '/includes/foot.php'; ?>

<script>
// Renderizado del slug en tiempo real
function makeSlug(s) {
  return s.toLowerCase()
    .replace(/[áàäâ]/g,'a').replace(/[éèëê]/g,'e').replace(/[íìïî]/g,'i')
    .replace(/[óòöô]/g,'o').replace(/[úùüû]/g,'u').replace(/[ñ]/g,'n')
    .replace(/[^a-z0-9\s-]/g,'').replace(/\s+/g,'-').replace(/-+/g,'-').replace(/^-|-$/g,'');
}
function previewSlug(val) {
  document.getElementById('slug-preview').textContent = makeSlug(val) || '—';
}

// Modal
function openModal(data = null) {
  const m = document.getElementById('cat-modal');
  document.getElementById('modal-title').textContent = data ? 'Editar Categoría' : 'Nueva Categoría';
  document.getElementById('f-id').value      = data?.id      ?? 0;
  document.getElementById('f-nombre').value  = data?.nombre  ?? '';
  document.getElementById('f-prefijo').value = data?.prefijo ?? '';
  document.getElementById('f-parent').value  = data?.parent_id ?? '';
  document.getElementById('f-desc').value    = data?.descripcion ?? '';
  document.getElementById('f-orden').value   = data?.orden   ?? 0;
  document.getElementById('f-activo').checked = data ? (data.activo == 1) : true;
  previewSlug(data?.nombre ?? '');
  m.classList.remove('hidden');
  m.classList.add('flex');
  document.body.style.overflow = 'hidden';
}
function closeModal() {
  const m = document.getElementById('cat-modal');
  m.classList.add('hidden');
  m.classList.remove('flex');
  document.body.style.overflow = '';
}
document.getElementById('cat-modal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
</script>
</body>
</html>
<?php
// ─── Función de renderizado del árbol (al final del archivo para acceder desde arriba) ───
function renderTree(array $nodes, int $level): void {
    $badgeMap = ['badge-0','badge-1','badge-2','badge-3','badge-4'];
    $badge = $badgeMap[min($level, 4)];
    $levelLabels = ['Nivel 1','Nivel 2','Nivel 3','Nivel 4','Nivel 5+'];
    $levelLabel  = $levelLabels[min($level, 4)];

    foreach ($nodes as $node):
        $inactive = !$node['activo'] ? 'inactive-row' : '';
        $hasChildren = !empty($node['children']);
        $prefixBadge = $node['prefijo'] ? "<span class='ml-auto font-mono text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-lg font-bold'>{$node['prefijo']}</span>" : '';
        $slug = $node['slug'] ?: '—';
        $childCount = countAllDescendants($node);
        $countBadge = $childCount > 0 ? "<span class='text-xs text-slate-400 font-medium'>{$childCount} sub</span>" : '';
        ?>
        <div class="<?= $level > 0 ? 'tree-node' : '' ?>">
          <div class="cat-row <?= $inactive ?>">
            <?php if ($hasChildren): ?>
            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            <?php else: ?>
            <svg class="w-4 h-4 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <?php endif; ?>

            <span class="level-badge <?= $badge ?>"><?= $levelLabel ?></span>

            <div class="flex-1 min-w-0">
              <p class="font-semibold text-slate-800 text-sm truncate"><?= e($node['nombre']) ?></p>
              <span class="slug-preview text-xs"><?= $slug ?></span>
            </div>

            <?= $countBadge ?>
            <?= $prefixBadge ?>

            <div class="flex items-center gap-2 shrink-0">
              <button onclick='openModal(<?= json_encode(['id'=>(int)$node["id"],'nombre'=>$node["nombre"],'prefijo'=>$node["prefijo"],'parent_id'=>$node["parent_id"],'descripcion'=>$node["descripcion"],'orden'=>(int)$node["orden"],'activo'=>(int)$node["activo"]]) ?>)' class="btn-edit">Editar</button>
              <form method="POST" onsubmit="return confirm('¿Eliminar «<?= e($node["nombre"]) ?>»?')">
                <input type="hidden" name="action" value="delete"/>
                <input type="hidden" name="id" value="<?= $node['id'] ?>"/>
                <button type="submit" class="btn-danger">Eliminar</button>
              </form>
            </div>
          </div>

          <?php if ($hasChildren): renderTree($node['children'], $level + 1); endif; ?>
        </div>
    <?php endforeach;
}

function countAllDescendants(array $node): int {
    $count = count($node['children']);
    foreach ($node['children'] as $child) $count += countAllDescendants($child);
    return $count;
}
