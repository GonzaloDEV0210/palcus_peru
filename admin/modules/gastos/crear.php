<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin');

$activePage = 'gastos';
$pageTitle  = 'Registrar Gasto';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $d = [
    'descripcion' => trim($_POST['descripcion'] ?? ''),
    'monto'       => (float)str_replace(',','.', $_POST['monto'] ?? '0'),
    'fecha'       => $_POST['fecha'] ?? date('Y-m-d'),
    'categoria'   => trim($_POST['categoria'] ?? ''),
    'metodo_pago' => trim($_POST['metodo_pago'] ?? 'Efectivo'),
    'usuario_id'  => currentUser()['id'],
  ];
  
  if (!$d['descripcion']) $errors[] = 'La descripción es obligatoria.';
  if ($d['monto'] <= 0)    $errors[] = 'El monto debe ser mayor a 0.';

  if (!$errors) {
    db()->execute(
      'INSERT INTO gastos (descripcion, monto, fecha, categoria, metodo_pago, usuario_id) VALUES (?,?,?,?,?,?)',
      [$d['descripcion'], $d['monto'], $d['fecha'], $d['categoria'], $d['metodo_pago'], $d['usuario_id']]
    );
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Gasto registrado correctamente.'];
    header('Location: index.php'); exit;
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= $pageTitle ?> — PalCus Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>* {font-family:'Inter',sans-serif;}
    .form-input {width:100%;padding:.625rem .875rem;border:1.5px solid #e5e7eb;border-radius:.625rem;font-size:.9375rem;color:#111827;background:#fafafa;outline:none;}
    .form-input:focus{border-color:#111827;background:#fff;}
    .form-label{display:block;font-size:.8125rem;font-weight:600;color:#374151;margin-bottom:.4rem;}
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
<div id="app-wrapper" class="flex min-h-screen">
  <?php include $ADMIN . '/includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col lg:ml-64 min-w-0">
    <?php include $ADMIN . '/includes/header.php'; ?>
    <main class="flex-1 p-6">
      <nav class="text-xs text-gray-400 mb-5 flex items-center gap-1.5"><a href="index.php" class="hover:text-gray-700">Gastos</a><span>/</span><span class="text-gray-700">Nuevo</span></nav>
      <?php if ($errors): ?><div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3"><?php foreach ($errors as $e): ?><p>• <?= e($e) ?></p><?php endforeach; ?></div><?php endif; ?>
      <form method="POST" class="max-w-2xl bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
        <div><label class="form-label">Descripción del gasto <span class="text-red-500">*</span></label><input name="descripcion" class="form-input" required placeholder="Ej: Pago de luz, Alquiler, Compras de insumos..." value="<?= e($_POST['descripcion']??'') ?>"/></div>
        <div class="grid grid-cols-2 gap-4">
          <div><label class="form-label">Monto (S/) <span class="text-red-500">*</span></label><input name="monto" type="number" step="0.01" min="0.01" class="form-input" required value="<?= e($_POST['monto']??'') ?>"/></div>
          <div><label class="form-label">Fecha</label><input name="fecha" type="date" class="form-input" value="<?= e($_POST['fecha']??date('Y-m-d')) ?>"/></div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div><label class="form-label">Categoría</label><input name="categoria" list="cats" class="form-input" placeholder="Ej: Servicios, Local, Insumos..." value="<?= e($_POST['categoria']??'') ?>"/><datalist id="cats"><option value="Servicios"/><option value="Alquiler"/><option value="Marketing"/><option value="Personal"/><option value="Insumos"/></datalist></div>
          <div><label class="form-label">Método de Pago</label><select name="metodo_pago" class="form-input"><option>Efectivo</option><option>Transferencia</option><option>Tarjeta</option><option>Yape/Plin</option></select></div>
        </div>
        <div class="flex gap-3 pt-4">
          <a href="index.php" class="px-6 py-2.5 border border-gray-200 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 text-center">Cancelar</a>
          <button type="submit" class="flex-1 bg-gray-900 hover:bg-gray-700 text-white text-sm font-semibold py-2.5 rounded-xl transition-colors">Guardar Gasto</button>
        </div>
      </form>
    </main>
  </div>
</div>
<?php include $ADMIN . '/includes/foot.php'; ?>
</body>
</html>
