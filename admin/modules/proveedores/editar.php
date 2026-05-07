<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin', 'almacenero');

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$p  = db()->fetchOne('SELECT * FROM proveedores WHERE id=? AND activo=1', [$id]);
if (!$p) { header('Location: index.php'); exit; }

$activePage = 'proveedores';
$pageTitle  = 'Editar Proveedor';
$errors     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $d = [
    'nombre'    => trim($_POST['nombre']   ?? ''),
    'contacto'  => trim($_POST['contacto']  ?? ''),
    'ruc'       => trim($_POST['ruc']       ?? ''),
    'email'     => trim($_POST['email']     ?? ''),
    'telefono'  => trim($_POST['telefono']  ?? ''),
    'direccion' => trim($_POST['direccion'] ?? ''),
    'notas'     => trim($_POST['notas']     ?? ''),
  ];
  
  if (!$d['nombre']) $errors[] = 'El nombre de la empresa es obligatorio.';
  
  if ($d['ruc']) {
    $dup = db()->fetchOne('SELECT id FROM proveedores WHERE ruc=? AND id!=? AND activo=1', [$d['ruc'], $id]);
    if ($dup) $errors[] = 'Este RUC ya está registrado con otro proveedor.';
  }

  if (!$errors) {
    db()->execute(
      'UPDATE proveedores SET nombre=?, contacto=?, ruc=?, email=?, telefono=?, direccion=?, notas=? WHERE id=?',
      [$d['nombre'], $d['contacto'], $d['ruc'], $d['email'], $d['telefono'], $d['direccion'], $d['notas'], $id]
    );
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Datos del proveedor actualizados.'];
    header('Location: index.php');
    exit;
  }
}
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

      <nav class="text-xs text-gray-400 mb-5 flex items-center gap-1.5">
        <a href="index.php" class="hover:text-gray-700">Proveedores</a>
        <span>/</span><span class="text-gray-700">Editar</span>
      </nav>

      <?php if ($errors): ?>
      <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3 space-y-1">
        <?php foreach ($errors as $e): ?><p>• <?= e($e) ?></p><?php endforeach; ?>
      </div>
      <?php endif; ?>

      <form method="POST" class="max-w-4xl">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
        <input type="hidden" name="id" value="<?= $id ?>"/>

        <div class="bg-white rounded-2xl border border-gray-200 p-6 space-y-6">
          <h3 class="font-semibold text-gray-900 text-lg">Editar Información del Proveedor</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="md:col-span-2">
              <label class="form-label">Nombre de la Empresa <span class="text-red-500">*</span></label>
              <input name="nombre" class="form-input" required value="<?= e($p['nombre']) ?>"/>
            </div>
            
            <div>
              <label class="form-label">Nombre del Contacto</label>
              <input name="contacto" class="form-input" value="<?= e($p['contacto']) ?>"/>
            </div>
            
            <div>
              <label class="form-label">RUC</label>
              <input name="ruc" class="form-input font-mono" value="<?= e($p['ruc']) ?>"/>
            </div>
            
            <div>
              <label class="form-label">Correo electrónico</label>
              <input name="email" type="email" class="form-input" value="<?= e($p['email']) ?>"/>
            </div>
            
            <div>
              <label class="form-label">Teléfono / Celular</label>
              <input name="telefono" class="form-input" value="<?= e($p['telefono']) ?>"/>
            </div>
            

            <div>
              <label class="form-label">Dirección</label>
              <input name="direccion" class="form-input" value="<?= e($p['direccion']) ?>"/>
            </div>
            
            <div class="md:col-span-2">
              <label class="form-label">Notas / Observaciones</label>
              <textarea name="notas" rows="3" class="form-input resize-none"><?= e($p['notas']) ?></textarea>
            </div>
          </div>

          <div class="flex gap-3 pt-4">
            <a href="index.php" class="px-6 py-2.5 border border-gray-200 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors text-center">
              Cancelar
            </a>
            <button type="submit" class="flex-1 bg-gray-900 hover:bg-gray-700 text-white text-sm font-semibold py-2.5 rounded-xl transition-colors">
              Guardar Cambios
            </button>
          </div>
        </div>
      </form>

    </main>
  </div>
</div>
<?php include $ADMIN . '/includes/foot.php'; ?>
</body>
</html>
