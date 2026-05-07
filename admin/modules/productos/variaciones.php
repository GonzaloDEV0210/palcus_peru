<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin', 'almacenero');

$id = (int)($_GET['id'] ?? 0);
$producto = db()->fetchOne('SELECT * FROM productos WHERE id=? AND activo=1', [$id]);
if (!$producto) { header('Location: index.php'); exit; }

$activePage = 'productos';
$pageTitle  = 'Variaciones — ' . $producto['nombre'];
$errors = [];
$flash  = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

// ── Actions ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $action = $_POST['action'] ?? '';

  if ($action === 'add') {
    $talla    = trim($_POST['talla']    ?? '');
    $color    = trim($_POST['color']    ?? '');
    $stock    = max(0, (int)$_POST['stock']);
    $stockMin = max(0, (int)($_POST['stock_minimo'] ?? 5));

    if (!$talla || !$color) { $errors[] = 'La talla y el color son obligatorios.'; }
    else {
      $exists = db()->fetchOne(
        'SELECT id FROM variaciones WHERE producto_id=? AND talla=? AND color=?',
        [$id, $talla, $color]
      );
      if ($exists) {
        $errors[] = "Ya existe la variación $talla / $color.";
      } else {
        db()->execute(
          'INSERT INTO variaciones (producto_id, talla, color, stock, stock_minimo) VALUES (?,?,?,?,?)',
          [$id, $talla, $color, $stock, $stockMin]
        );
        // Registrar movimiento si stock > 0
        if ($stock > 0) {
          $varId = db()->lastInsertId();
          db()->execute(
            'INSERT INTO movimientos_inventario
             (tipo,variacion_id,producto_id,nombre_producto,talla,color,cantidad,stock_antes,stock_despues,motivo,usuario_id)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            ['entrada',$varId,$id,$producto['nombre'],$talla,$color,$stock,0,$stock,'Stock inicial',currentUser()['id']]
          );
        }
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Variación agregada.'];
        header("Location: variaciones.php?id=$id"); exit;
      }
    }
  }

  if ($action === 'update') {
    $varId    = (int)$_POST['var_id'];
    $stock    = max(0, (int)$_POST['stock']);
    $stockMin = max(0, (int)($_POST['stock_minimo'] ?? 5));
    $activo   = isset($_POST['activo']) ? 1 : 0;
    $old = db()->fetchOne('SELECT stock FROM variaciones WHERE id=? AND producto_id=?', [$varId, $id]);
    if ($old) {
      db()->execute('UPDATE variaciones SET stock=?,stock_minimo=?,activo=? WHERE id=?',
        [$stock,$stockMin,$activo,$varId]);
      // Registro de ajuste si cambió el stock
      if ((int)$old['stock'] !== $stock) {
        $var = db()->fetchOne('SELECT * FROM variaciones WHERE id=?', [$varId]);
        db()->execute(
          'INSERT INTO movimientos_inventario
           (tipo,variacion_id,producto_id,nombre_producto,talla,color,cantidad,stock_antes,stock_despues,motivo,usuario_id)
           VALUES (?,?,?,?,?,?,?,?,?,?,?)',
          ['ajuste',$varId,$id,$producto['nombre'],$var['talla'],$var['color'],
           abs($stock-(int)$old['stock']),(int)$old['stock'],$stock,'Ajuste manual',currentUser()['id']]
        );
      }
      $_SESSION['flash'] = ['type'=>'success','msg'=>'Variación actualizada.'];
    }
    header("Location: variaciones.php?id=$id"); exit;
  }

  if ($action === 'delete') {
    $varId = (int)$_POST['var_id'];
    db()->execute('UPDATE variaciones SET activo=0 WHERE id=? AND producto_id=?', [$varId, $id]);
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Variación eliminada.'];
    header("Location: variaciones.php?id=$id"); exit;
  }
}

$variaciones = db()->fetchAll(
  'SELECT * FROM variaciones WHERE producto_id=? AND activo=1 ORDER BY talla, color',
  [$id]
);
$stockTotal = array_sum(array_column($variaciones, 'stock'));

// Tallas comunes para sugerencias
$tallasComunes = ['XS','S','M','L','XL','XXL','XXXL'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= e($pageTitle) ?> — PalCus Admin</title>
  <link rel="icon" href="https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo.png"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>
    * {font-family:'Inter',sans-serif;}
    .form-input{width:100%;padding:.5rem .75rem;border:1.5px solid #e5e7eb;border-radius:.5rem;font-size:.875rem;color:#111827;background:#fafafa;outline:none;transition:border-color .15s;}
    .form-input:focus{border-color:#111827;background:#fff;}
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
<div id="app-wrapper" class="flex min-h-screen">
  <?php include $ADMIN . '/includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col lg:ml-64 min-w-0">
    <?php include $ADMIN . '/includes/header.php'; ?>
    <main class="flex-1 p-6 space-y-5">

      <!-- Breadcrumb -->
      <nav class="text-xs text-gray-400 flex items-center gap-1.5">
        <a href="index.php" class="hover:text-gray-700">Productos</a>
        <span>/</span>
        <a href="editar.php?id=<?= $id ?>" class="hover:text-gray-700"><?= e($producto['nombre']) ?></a>
        <span>/</span><span class="text-gray-700">Variaciones</span>
      </nav>

      <?php if ($errors): ?>
      <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
        <?php foreach ($errors as $err): ?><p>• <?= e($err) ?></p><?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Product summary card -->
      <div class="bg-white rounded-2xl border border-gray-200 p-5 flex items-center gap-4">
        <?php if ($producto['imagen_url']): ?>
        <img src="<?= e($producto['imagen_url']) ?>" class="w-16 h-16 object-cover rounded-xl border border-gray-100" alt=""/>
        <?php endif; ?>
        <div class="flex-1">
          <h2 class="font-bold text-gray-900 text-lg"><?= e($producto['nombre']) ?></h2>
          <p class="text-gray-400 text-sm">SKU: <?= e($producto['sku'] ?? '—') ?> &nbsp;·&nbsp; Venta: <?= money((float)$producto['precio_venta']) ?></p>
        </div>
        <div class="text-right">
          <p class="text-2xl font-bold text-gray-900"><?= $stockTotal ?></p>
          <p class="text-gray-400 text-xs">unidades totales</p>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        <!-- Variaciones table -->
        <div class="xl:col-span-2">
          <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
              <h3 class="font-semibold text-gray-900">Variaciones (<?= count($variaciones) ?>)</h3>
            </div>
            <?php if (empty($variaciones)): ?>
            <div class="py-12 text-center text-gray-400 text-sm">
              <p>No hay variaciones. Agrega al menos una talla y color.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 font-semibold uppercase tracking-wide">
                  <tr>
                    <th class="px-5 py-3 text-left">Talla</th>
                    <th class="px-5 py-3 text-left">Color</th>
                    <th class="px-5 py-3 text-center">Stock</th>
                    <th class="px-5 py-3 text-center">Mín.</th>
                    <th class="px-5 py-3 text-center">Estado</th>
                    <th class="px-5 py-3"></th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <?php foreach ($variaciones as $v): ?>
                  <tr class="hover:bg-gray-50" id="row-<?= $v['id'] ?>">
                    <td class="px-5 py-3 font-semibold text-gray-900"><?= e($v['talla']) ?></td>
                    <td class="px-5 py-3 text-gray-700"><?= e($v['color']) ?></td>
                    <td class="px-5 py-3 text-center">
                      <span class="font-bold <?= $v['stock']==0?'text-red-500':($v['stock']<=$v['stock_minimo']?'text-amber-500':'text-gray-900') ?>">
                        <?= $v['stock'] ?>
                      </span>
                    </td>
                    <td class="px-5 py-3 text-center text-gray-500"><?= $v['stock_minimo'] ?></td>
                    <td class="px-5 py-3 text-center">
                      <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold
                        <?= $v['stock']>0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-500' ?>">
                        <?= $v['stock']>0 ? 'Disponible' : 'Agotado' ?>
                      </span>
                    </td>
                    <td class="px-5 py-3">
                      <div class="flex gap-1 justify-end">
                        <button onclick="openEdit(<?= htmlspecialchars(json_encode($v)) ?>)"
                          class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <form method="POST" data-confirm="¿Deseas eliminar esta variación del producto?">
                          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
                          <input type="hidden" name="action" value="delete"/>
                          <input type="hidden" name="var_id" value="<?= $v['id'] ?>"/>
                          <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
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
        </div>

        <!-- Add variation form -->
        <div class="space-y-5">
          <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <h3 class="font-semibold text-gray-900 mb-4">Agregar variación</h3>
            <form method="POST" class="space-y-3">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
              <input type="hidden" name="action" value="add"/>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Talla *</label>
                <input list="tallasList" name="talla" class="form-input" placeholder="M, L, XL..." required/>
                <datalist id="tallasList">
                  <?php foreach ($tallasComunes as $t): ?><option value="<?= $t ?>"/><?php endforeach; ?>
                </datalist>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Color *</label>
                <input name="color" class="form-input" placeholder="Blanco, Negro, Azul..." required/>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1">Stock inicial</label>
                  <input name="stock" type="number" min="0" value="0" class="form-input"/>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1">Stock mínimo</label>
                  <input name="stock_minimo" type="number" min="0" value="5" class="form-input"/>
                </div>
              </div>
              <button type="submit" class="w-full bg-gray-900 hover:bg-gray-700 text-white text-sm font-semibold py-2.5 rounded-xl transition-colors">
                + Agregar variación
              </button>
            </form>
          </div>

          <a href="index.php" class="block text-center py-2.5 border border-gray-200 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors">
            ← Volver a productos
          </a>
        </div>
      </div>

      <!-- Edit modal -->
      <div id="editModal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-sm p-6 shadow-xl">
          <h3 class="font-bold text-gray-900 mb-4">Editar variación</h3>
          <form method="POST" class="space-y-3">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
            <input type="hidden" name="action" value="update"/>
            <input type="hidden" name="var_id" id="editVarId"/>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Talla</label>
                <input id="editTalla" class="form-input bg-gray-100" readonly/>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Color</label>
                <input id="editColor" class="form-input bg-gray-100" readonly/>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Stock</label>
                <input name="stock" id="editStock" type="number" min="0" class="form-input" required/>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Stock mínimo</label>
                <input name="stock_minimo" id="editStockMin" type="number" min="0" class="form-input" required/>
              </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
              <input type="checkbox" name="activo" value="1" id="editActivo" class="rounded"/>
              Variación activa
            </label>
            <div class="flex gap-3 pt-1">
              <button type="button" onclick="closeEdit()" class="flex-1 py-2.5 border border-gray-200 text-gray-600 text-sm rounded-xl hover:bg-gray-50 transition-colors">Cancelar</button>
              <button type="submit" class="flex-1 bg-gray-900 text-white text-sm font-semibold py-2.5 rounded-xl hover:bg-gray-700 transition-colors">Guardar</button>
            </div>
          </form>
        </div>
      </div>

    </main>
  </div>
</div>
<?php include $ADMIN . '/includes/foot.php'; ?>
<script>
function openEdit(v) {
  document.getElementById('editVarId').value   = v.id;
  document.getElementById('editTalla').value   = v.talla;
  document.getElementById('editColor').value   = v.color;
  document.getElementById('editStock').value   = v.stock;
  document.getElementById('editStockMin').value= v.stock_minimo;
  document.getElementById('editActivo').checked= v.activo == '1';
  document.getElementById('editModal').classList.remove('hidden');
}
function closeEdit() { document.getElementById('editModal').classList.add('hidden'); }
document.getElementById('editModal').addEventListener('click', function(e) {
  if (e.target === this) closeEdit();
});
</script>
</body>
</html>
