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
    $tallas    = $_POST['tallas'] ?? [];
    $color     = trim($_POST['color']     ?? '');
    $color_hex = trim($_POST['color_hex'] ?? '#000000');
    $diseno    = trim($_POST['diseno']    ?? 'Estándar');
    $img_url   = '';

    if (empty($tallas) || !$color) { $errors[] = 'Debes seleccionar al menos una talla y el color.'; }
    
    if (empty($errors)) {
        // --- PROCESO NORMAL (Un solo color) ---
        if (!empty($_FILES['foto']['name']) && !is_array($_FILES['foto']['name'])) {
            $uploadedUrl = cloudinaryUpload($_FILES['foto']);
            if ($uploadedUrl) { $img_url = $uploadedUrl; }
            else { $errors[] = 'Error al subir la imagen.'; }
        }

        if (empty($errors)) {
            $addedCount = 0;
            foreach ($tallas as $talla) {
                $talla = trim($talla);
                $stock     = (int)($_POST['stock_talla'][$talla] ?? 0);
                $stockMin  = max(0, (int)($_POST['stock_min_talla'][$talla] ?? 5));

                $exists = db()->fetchOne(
                    'SELECT id FROM variaciones WHERE producto_id=? AND talla=? AND color=? AND diseno=?',
                    [$id, $talla, $color, $diseno]
                );
                if (!$exists) {
                    db()->execute(
                        'INSERT INTO variaciones (producto_id, talla, color, color_hex, diseno, imagen_url, stock, stock_minimo) VALUES (?,?,?,?,?,?,?,?)',
                        [$id, $talla, $color, $color_hex, $diseno, $img_url, $stock, $stockMin]
                    );
                    $addedCount++;
                    if ($stock > 0) {
                        $varId = db()->lastInsertId();
                        db()->execute(
                            'INSERT INTO movimientos_inventario (tipo,variacion_id,producto_id,nombre_producto,talla,color,diseno,cantidad,stock_antes,stock_despues,motivo,usuario_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                            ['entrada',$varId,$id,$producto['nombre'],$talla,$color,$diseno,$stock,0,$stock,'Stock inicial',currentUser()['id']]
                        );
                    }
                }
            }
            if ($addedCount > 0) {
                $_SESSION['flash'] = ['type'=>'success','msg'=>"$addedCount variaciones agregadas."];
                header("Location: variaciones.php?id=$id"); exit;
            } else {
                $errors[] = 'Las variaciones ya existen.';
            }
        }
    }
  }

  if ($action === 'bulk_add') {
    $tallas = $_POST['tallas'] ?? [];
    $diseno = trim($_POST['diseno'] ?? 'Estándar');
    $bulk_colors = $_POST['bulk_colors'] ?? []; // [{name, hex, index}]
    
    if (empty($tallas) || empty($bulk_colors)) {
        $errors[] = 'Debes seleccionar tallas y al menos un color.';
    }

    if (empty($errors)) {
        $addedTotal = 0;
        foreach ($bulk_colors as $index => $cData) {
            $cName = trim($cData['name']);
            $cHex  = trim($cData['hex']);
            $vImgUrl = '';

            if (!empty($_FILES['bulk_fotos']['name'][$index])) {
                $tmpFile = [
                    'tmp_name' => $_FILES['bulk_fotos']['tmp_name'][$index],
                    'name'     => $_FILES['bulk_fotos']['name'][$index],
                    'error'    => $_FILES['bulk_fotos']['error'][$index],
                    'size'     => $_FILES['bulk_fotos']['size'][$index]
                ];
                $vImgUrl = cloudinaryUpload($tmpFile);
            }

            foreach ($tallas as $talla) {
                // Obtener stock específico para este color y esta talla
                $stock = (int)($cData['stocks'][$talla] ?? 0);
                $stockMin = (int)($cData['mins'][$talla] ?? 5);

                $exists = db()->fetchOne(
                    'SELECT id FROM variaciones WHERE producto_id=? AND talla=? AND color=? AND diseno=?',
                    [$id, $talla, $cName, $diseno]
                );
                if (!$exists) {
                    db()->execute(
                        'INSERT INTO variaciones (producto_id, talla, color, color_hex, diseno, imagen_url, stock, stock_minimo) VALUES (?,?,?,?,?,?,?,?)',
                        [$id, $talla, $cName, $cHex, $diseno, $vImgUrl, $stock, $stockMin]
                    );
                    $varId = db()->lastInsertId();
                    if ($stock > 0) {
                        db()->execute(
                            'INSERT INTO movimientos_inventario (tipo,variacion_id,producto_id,nombre_producto,talla,color,diseno,cantidad,stock_antes,stock_despues,motivo,usuario_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                            ['entrada',$varId,$id,$producto['nombre'],$talla,$cName,$diseno,$stock,0,$stock,'Stock inicial (Carga lote)',currentUser()['id']]
                        );
                    }
                    $addedTotal++;
                }
            }
        }
        $_SESSION['flash'] = ['type'=>'success','msg'=>"Se han creado $addedTotal variaciones por lote."];
        header("Location: variaciones.php?id=$id"); exit;
    }
  }

  if ($action === 'update') {
    $varId     = (int)$_POST['var_id'];
    $diseno    = trim($_POST['diseno']    ?? 'Estándar');
    $color_hex = trim($_POST['color_hex'] ?? '#000000');
    $stock     = max(0, (int)$_POST['stock']);
    $stockMin  = max(0, (int)($_POST['stock_minimo'] ?? 5));
    $activo    = isset($_POST['activo']) ? 1 : 0;
    
    $old = db()->fetchOne('SELECT * FROM variaciones WHERE id=? AND producto_id=?', [$varId, $id]);
    if ($old) {
      $img_url = $old['imagen_url'];
      if (!empty($_FILES['foto']['name'])) {
          $uploadedUrl = cloudinaryUpload($_FILES['foto']);
          if ($uploadedUrl) {
              // Borrar anterior
              if (!empty($old['imagen_url'])) {
                  cloudinaryDestroy($old['imagen_url']);
              }
              $img_url = $uploadedUrl;
          }
      }
      db()->execute('UPDATE variaciones SET diseno=?, color_hex=?, imagen_url=?, stock=?, stock_minimo=?, activo=? WHERE id=?',
        [$diseno,$color_hex,$img_url,$stock,$stockMin,$activo,$varId]);
      // Registro de ajuste si cambió el stock
      if ((int)$old['stock'] !== $stock) {
        $var = db()->fetchOne('SELECT * FROM variaciones WHERE id=?', [$varId]);
        db()->execute(
          'INSERT INTO movimientos_inventario
           (tipo,variacion_id,producto_id,nombre_producto,talla,color,diseno,cantidad,stock_antes,stock_despues,motivo,usuario_id)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
          ['ajuste',$varId,$id,$producto['nombre'],$var['talla'],$var['color'],$var['diseno'],
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
  <link rel="icon" href="<?= getConfig('url_icono') ?: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo.png' ?>"/>
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
                    <th class="px-5 py-3 text-left w-12"></th>
                    <th class="px-5 py-3 text-left">Talla</th>
                    <th class="px-5 py-3 text-left">Color</th>
                    <th class="px-5 py-3 text-left">Diseño</th>
                    <th class="px-5 py-3 text-center">Stock</th>
                    <th class="px-5 py-3 text-center">Mín.</th>
                    <th class="px-5 py-3 text-center">Estado</th>
                    <th class="px-5 py-3"></th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <?php foreach ($variaciones as $v): ?>
                  <tr class="hover:bg-gray-50 border-b border-gray-50" id="row-<?= $v['id'] ?>">
                    <td class="px-5 py-3">
                      <?php if ($v['imagen_url']): ?>
                        <img src="<?= e($v['imagen_url']) ?>" class="w-10 h-10 object-cover rounded-lg border border-gray-100" alt=""/>
                      <?php else: ?>
                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center text-gray-300">
                           <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td class="px-5 py-3 font-semibold text-gray-900"><?= e($v['talla']) ?></td>
                    <td class="px-5 py-3 text-gray-700">
                      <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full border border-gray-200" style="background-color: <?= e($v['color_hex']) ?>;"></span>
                        <?= e($v['color']) ?>
                      </div>
                    </td>
                    <td class="px-5 py-3 text-gray-600 italic"><?= e($v['diseno']) ?></td>
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
          <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm">
            <div class="flex bg-gray-50/50">
              <button onclick="setTab('single')" id="tab-single" class="flex-1 py-3 text-[10px] font-bold uppercase tracking-widest border-b-2 border-gray-900 text-gray-900 transition-all">Individual</button>
              <button onclick="setTab('bulk')" id="tab-bulk" class="flex-1 py-3 text-[10px] font-bold uppercase tracking-widest border-b-2 border-transparent text-gray-400 hover:text-gray-600 transition-all">Por Lote (Varios Colores)</button>
            </div>

            <!-- Individual Form -->
            <div id="form-single" class="p-5">
              <h3 class="font-semibold text-gray-900 mb-4 italic text-xs">Añadir un solo color</h3>
              <form method="POST" enctype="multipart/form-data" class="space-y-3">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
                <input type="hidden" name="action" value="add"/>
                <!-- Tallas Checkboxes (Shared) -->
                <div class="tallas-selector">
                  <label class="block text-xs font-semibold text-gray-600 mb-2">Tallas <span class="text-red-500">*</span></label>
                  <div class="flex flex-wrap gap-1.5">
                    <?php foreach ($tallasComunes as $t): ?>
                    <label class="group relative inline-flex items-center justify-center p-0.5 rounded-lg border border-gray-200 bg-white cursor-pointer hover:border-gray-900 transition-all has-[:checked]:bg-gray-900 has-[:checked]:border-gray-900 has-[:checked]:text-white">
                      <input type="checkbox" name="tallas[]" value="<?= $t ?>" class="sr-only talla-cb"/>
                      <span class="px-2.5 py-1 text-[10px] font-bold uppercase"><?= $t ?></span>
                    </label>
                    <?php endforeach; ?>
                  </div>
                </div>

                <div class="grid grid-cols-1 gap-3">
                  <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Color *</label>
                    <div class="flex gap-1">
                      <input name="color" id="color_name_new" class="form-input" placeholder="Nombre del color..." required/>
                      <input type="color" name="color_hex" id="color_hex_new" value="#000000" class="w-10 h-9 p-0 border-0 bg-transparent cursor-pointer rounded-lg overflow-hidden"/>
                    </div>
                  </div>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1">Diseño gráfico</label>
                  <input name="diseno" class="form-input" placeholder="Ej: Estrella, Corazón..." value="Estándar"/>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1">Imagen del color</label>
                  <div class="flex items-center gap-3">
                    <div id="newVarPreview" class="hidden">
                      <img id="newVarPreviewImg" src="" class="w-12 h-12 object-cover rounded-lg border border-gray-200"/>
                    </div>
                    <div class="relative flex-1 group">
                      <input type="file" name="foto" id="foto_new" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"/>
                      <div class="flex items-center justify-center gap-2 py-2 border border-gray-200 rounded-xl text-[10px] text-gray-500 group-hover:border-gray-900 group-hover:text-gray-900 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span id="label_new">Elegir Foto</span>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Dynamic Stock (Oculto inicialmente, se muestra por JS) -->
                <div id="stock_configs_single" class="space-y-2 border-t border-gray-100 pt-3 hidden">
                  <div id="stock_inputs_container_single" class="space-y-2"></div>
                </div>

                <button type="submit" class="w-full bg-gray-900 hover:bg-black text-white text-[11px] font-bold uppercase tracking-wide py-3 rounded-xl transition-all shadow-lg shadow-gray-200 active:scale-[0.98]">
                  + Agregar color
                </button>
              </form>
            </div>

            <!-- Bulk Form -->
            <div id="form-bulk" class="p-5 hidden">
              <h3 class="font-semibold text-gray-900 mb-4 italic text-xs">Subir varios colores a la vez</h3>
              <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
                <input type="hidden" name="action" value="bulk_add"/>
                
                <div class="tallas-selector">
                  <label class="block text-xs font-semibold text-gray-600 mb-2">Tallas para todos <span class="text-red-500">*</span></label>
                  <div class="flex flex-wrap gap-1.5">
                    <?php foreach ($tallasComunes as $t): ?>
                    <label class="group relative inline-flex items-center justify-center p-0.5 rounded-lg border border-gray-200 bg-white cursor-pointer hover:border-gray-900 transition-all has-[:checked]:bg-gray-900 has-[:checked]:border-gray-900 has-[:checked]:text-white">
                      <input type="checkbox" name="tallas[]" value="<?= $t ?>" class="sr-only talla-cb-bulk"/>
                      <span class="px-2.5 py-1 text-[10px] font-bold uppercase"><?= $t ?></span>
                    </label>
                    <?php endforeach; ?>
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1">Diseño común (Ej: Estrella)</label>
                  <input name="diseno" class="form-input" placeholder="Ej: Estrella..." value="Estándar"/>
                </div>

                <div class="border-2 border-dashed border-gray-100 rounded-xl p-4 text-center group hover:border-gray-900 transition-all cursor-pointer relative">
                   <input type="file" id="bulk_files" name="fotos[]" multiple accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer"/>
                   <div class="flex flex-col items-center gap-1 text-gray-400 group-hover:text-gray-900">
                     <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                     <p class="text-xs font-bold uppercase">Seleccionar todas las fotos de colores</p>
                     <p class="text-[10px]">Puedes elegir varios archivos a la vez</p>
                   </div>
                </div>

                <div id="bulk_preview_container" class="space-y-3"></div>

                <button type="submit" id="btn-bulk-submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold uppercase tracking-wide py-3 rounded-xl transition-all shadow-lg shadow-emerald-100 active:scale-[0.98] hidden">
                   Registrar todos los colores →
                </button>
              </form>
            </div>
          </div>

          <a href="index.php" class="block text-center py-2.5 border border-gray-200 text-gray-500 text-[10px] font-bold uppercase tracking-wider rounded-xl hover:bg-gray-50 transition-colors">
            ← Volver al listado
          </a>
        </div>
      </div>

      <!-- Edit modal -->
      <div id="editModal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-sm p-6 shadow-xl">
          <h3 class="font-bold text-gray-900 mb-4">Editar variación</h3>
          <form method="POST" enctype="multipart/form-data" class="space-y-3">
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
                <div class="flex gap-1">
                  <input id="editColor" class="form-input bg-gray-100" readonly/>
                  <input type="color" name="color_hex" id="editColorHex" class="w-10 h-9 p-0 border-0 bg-transparent cursor-pointer rounded-lg overflow-hidden"/>
                </div>
              </div>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Diseño gráfico</label>
              <input name="diseno" id="editDiseno" class="form-input" required/>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Imagen de la variante</label>
              <div class="flex items-center gap-3">
                 <div id="editVarPreview">
                    <img id="editVarPreviewImg" src="" class="w-12 h-12 object-cover rounded-lg border border-gray-200"/>
                 </div>
                 <div class="relative flex-1 group">
                    <input type="file" name="foto" id="foto_edit" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"/>
                    <div class="flex items-center justify-center gap-2 py-2 border border-gray-200 rounded-xl text-[10px] text-gray-500 group-hover:border-gray-900 group-hover:text-gray-900 transition-all">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                      <span id="label_edit">Cambiar Foto</span>
                    </div>
                 </div>
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
  document.getElementById('editColorHex').value = v.color_hex || '#000000';
  document.getElementById('editDiseno').value   = v.diseno;
  document.getElementById('editStock').value   = v.stock;
  document.getElementById('editStockMin').value= v.stock_minimo;
  document.getElementById('editActivo').checked= v.activo == '1';
  
  const imgInput = document.getElementById('edit_imagen_url');
  const imgEl = document.getElementById('editVarPreviewImg');
  const imgWrap = document.getElementById('editVarPreview');
  imgInput.value = v.imagen_url || '';
  if (v.imagen_url) {
    imgEl.src = v.imagen_url;
    imgWrap.classList.remove('hidden');
  } else {
    imgEl.src = '';
    imgWrap.classList.add('hidden');
  }

  document.getElementById('editModal').classList.remove('hidden');
}
function closeEdit() { document.getElementById('editModal').classList.add('hidden'); }
document.getElementById('editModal').addEventListener('click', function(e) {
  if (e.target === this) closeEdit();
});

// Local image previews
function setupPreview(inputId, labelId, previewWrapId, previewImgId, defaultLabel) {
  const input = document.getElementById(inputId);
  if(!input) return;
  input.addEventListener('change', function(e) {
    const file = e.target.files[0];
    const label = document.getElementById(labelId);
    const preview = document.getElementById(previewWrapId);
    const previewEl = document.getElementById(previewImgId);
    if (file) {
      label.textContent = file.name;
      const reader = new FileReader();
      reader.onload = function(e) {
        previewEl.src = e.target.result;
        preview.classList.remove('hidden');
      }
      reader.readAsDataURL(file);
    } else {
      label.textContent = defaultLabel;
    }
  });
}
setupPreview('foto_new', 'label_new', 'newVarPreview', 'newVarPreviewImg', 'Elegir Foto');
setupPreview('foto_edit', 'label_edit', 'editVarPreview', 'editVarPreviewImg', 'Cambiar Foto');

// Color Sync Logic
const colorNames = {
  '#000000': 'Negro', '#ffffff': 'Blanco', '#ff0000': 'Rojo', '#00ff00': 'Verde',
  '#0000ff': 'Azul', '#ffff00': 'Amarillo', '#ff00ff': 'Fucsia', '#00ffff': 'Cian',
  '#808080': 'Gris', '#800000': 'Granate', '#808000': 'Oliva', '#008000': 'Verde Oscuro',
  '#800080': 'Púrpura', '#008080': 'Teal', '#000080': 'Azul Marino', '#ffa500': 'Naranja',
  '#a52a2a': 'Marrón', '#f0e68c': 'Mostaza', '#add8e6': 'Azul Claro', '#90ee90': 'Verde Claro'
};

function setupColorSync(pickerId, nameId) {
  const picker = document.getElementById(pickerId);
  const nameInput = document.getElementById(nameId);
  if(!picker || !nameInput) return;
  
  picker.addEventListener('input', function() {
    const hex = this.value.toLowerCase();
    // Si el campo de nombre está vacío o tiene un hexadecimal previo, lo actualizamos
    if (!nameInput.value || nameInput.value.startsWith('#')) {
      nameInput.value = colorNames[hex] || hex.toUpperCase();
    }
  });
}
setupColorSync('color_hex_new', 'color_name_new');

// --- TAB Logic ---
function setTab(t) {
  const ts = document.getElementById('tab-single'), tb = document.getElementById('tab-bulk');
  const fs = document.getElementById('form-single'), fb = document.getElementById('form-bulk');
  if (t === 'single') {
    ts.className = ts.className.replace('text-gray-400', 'text-gray-900 bg-gray-50').replace('border-transparent', 'border-gray-900');
    tb.className = tb.className.replace('text-gray-900 bg-gray-50', 'text-gray-400').replace('border-gray-900', 'border-transparent');
    fs.classList.remove('hidden'); fb.classList.add('hidden');
  } else {
    tb.className = tb.className.replace('text-gray-400', 'text-gray-900 bg-gray-50').replace('border-transparent', 'border-gray-900');
    ts.className = ts.className.replace('text-gray-900 bg-gray-50', 'text-gray-400').replace('border-gray-900', 'border-transparent');
    fb.classList.remove('hidden'); fs.classList.add('hidden');
  }
}

// --- Dynamic Stock (Individual) ---
const tSingleCBs = document.querySelectorAll('.talla-cb');
const stockSingleWrap = document.getElementById('stock_configs_single');
const stockSingleContainer = document.getElementById('stock_inputs_container_single');

function updateStockSingle() {
    const selected = Array.from(tSingleCBs).filter(cb => cb.checked);
    if (selected.length > 0) {
        stockSingleWrap.classList.remove('hidden');
        stockSingleContainer.innerHTML = `
            <div class="flex items-center gap-2 mb-1 px-2">
                <span class="w-8"></span>
                <div class="flex-1 grid grid-cols-2 gap-2 text-[9px] font-bold text-gray-400 uppercase">
                    <span>Stock Actual</span>
                    <span>Stock Mínimo</span>
                </div>
            </div>
            ${selected.map(cb => `
                <div class="flex items-center gap-2 bg-gray-50 p-2 rounded-lg border border-gray-100">
                    <span class="w-8 font-bold text-[10px] text-gray-700 text-center">${cb.value}</span>
                    <div class="flex-1 grid grid-cols-2 gap-2">
                        <input type="number" name="stock_talla[${cb.value}]" value="0" min="0" class="w-full text-[10px] p-1.5 border border-gray-200 rounded-md outline-none focus:border-gray-900 font-bold" placeholder="0">
                        <input type="number" name="stock_min_talla[${cb.value}]" value="5" min="0" class="w-full text-[10px] p-1.5 border border-gray-200 rounded-md outline-none focus:border-gray-900 text-gray-500" placeholder="5">
                    </div>
                </div>
            `).join('')}`;
    } else {
        stockSingleWrap.classList.add('hidden');
        stockSingleContainer.innerHTML = '';
    }
}
tSingleCBs.forEach(cb => cb.addEventListener('change', updateStockSingle));

// --- Bulk Upload Logic ---
const bulkFilesInput = document.getElementById('bulk_files');
const bulkPreviewContainer = document.getElementById('bulk_preview_container');
const btnBulkSubmit = document.getElementById('btn-bulk-submit');

function renderBulkStockInputs(index) {
    const selectedTallas = Array.from(document.querySelectorAll('.talla-cb-bulk')).filter(cb => cb.checked).map(cb => cb.value);
    if (selectedTallas.length === 0) return '<p class="text-[10px] text-amber-600 font-medium">⚠️ Selecciona tallas arriba para poner el stock</p>';
    
    return `
        <div class="mt-3 pt-3 border-t border-gray-50">
            <div class="flex items-center gap-2 mb-2 px-2">
                <span class="w-6"></span>
                <div class="flex-1 grid grid-cols-2 gap-2 text-[9px] font-bold text-gray-400 uppercase">
                    <span>Stock Actual</span>
                    <span>Stock Mínimo</span>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-2">
                ${selectedTallas.map(t => `
                    <div class="flex items-center gap-2 bg-gray-50/50 p-2 rounded-lg border border-gray-100">
                        <span class="w-6 font-bold text-[10px] text-gray-600 text-center">${t}</span>
                        <div class="flex-1 grid grid-cols-2 gap-2">
                            <input type="number" name="bulk_colors[${index}][stocks][${t}]" value="0" min="0" class="w-full text-[10px] p-1.5 border border-gray-200 rounded-md outline-none focus:border-gray-900 font-bold text-gray-900" placeholder="0">
                            <input type="number" name="bulk_colors[${index}][mins][${t}]" value="5" min="0" class="w-full text-[10px] p-1.5 border border-gray-200 rounded-md outline-none focus:border-gray-900 text-gray-500" placeholder="5">
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}

function updateAllBulkStock() {
    const previews = bulkPreviewContainer.querySelectorAll('.bulk-item');
    previews.forEach(item => {
        const index = item.getAttribute('data-index');
        const stockArea = item.querySelector('.stock-area');
        stockArea.innerHTML = renderBulkStockInputs(index);
    });
}

document.querySelectorAll('.talla-cb-bulk').forEach(cb => cb.addEventListener('change', updateAllBulkStock));

bulkFilesInput.addEventListener('change', function(e) {
  const files = e.target.files;
  bulkPreviewContainer.innerHTML = '';
  if (files.length > 0) {
    btnBulkSubmit.classList.remove('hidden');
    Array.from(files).forEach((file, index) => {
      const reader = new FileReader();
      reader.onload = function(e) {
        const hexSugg = Object.keys(colorNames)[Math.floor(Math.random() * Object.keys(colorNames).length)];
        const div = document.createElement('div');
        div.className = 'bulk-item bg-white p-4 border border-gray-200 rounded-2xl flex flex-col gap-3 shadow-sm';
        div.setAttribute('data-index', index);
        div.innerHTML = `
          <div class="flex items-center gap-4">
            <div class="relative group">
                <img src="${e.target.result}" class="w-16 h-20 object-cover rounded-xl border border-gray-100 shadow-sm">
                <div class="absolute inset-0 bg-black/10 opacity-0 group-hover:opacity-100 rounded-xl transition-all"></div>
            </div>
            <div class="flex-1 space-y-3">
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Nombre y Color:</label>
                    <div class="flex gap-1">
                      <input name="bulk_colors[${index}][name]" class="flex-1 text-xs font-semibold p-2 border border-gray-200 rounded-xl outline-none focus:border-gray-900 bg-gray-50/30" placeholder="Ej: Rojo Pasión" required>
                      <input type="color" name="bulk_colors[${index}][hex]" value="${hexSugg}" class="w-10 h-10 p-0 border-0 bg-transparent cursor-pointer rounded-xl overflow-hidden">
                    </div>
                </div>
                <input type="file" name="bulk_fotos[${index}]" class="hidden" id="bulk_file_${index}">
            </div>
          </div>
          <div class="stock-area">
            ${renderBulkStockInputs(index)}
          </div>
        `;
        bulkPreviewContainer.appendChild(div);
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById(`bulk_file_${index}`).files = dt.files;
      };
      reader.readAsDataURL(file);
    });
  } else {
    btnBulkSubmit.classList.add('hidden');
  }
});
</script>
</body>
</html>
