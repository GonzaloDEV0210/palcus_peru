<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin', 'vendedor');

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$cliente = db()->fetchOne('SELECT * FROM clientes WHERE id=? AND activo=1', [$id]);
if (!$cliente) { header('Location: index.php'); exit; }

$activePage = 'clientes';
$pageTitle  = 'Editar Cliente';
$errors     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $d = [
    'nombre'    => trim($_POST['nombre']   ?? ''),
    'dni_ruc'   => trim($_POST['dni_ruc']  ?? ''),
    'email'     => trim($_POST['email']    ?? ''),
    'telefono'  => trim($_POST['telefono'] ?? ''),
    'direccion' => trim($_POST['direccion']?? ''),
    'notas'     => trim($_POST['notas']    ?? ''),
  ];
  
  if (!$d['nombre']) $errors[] = 'El nombre es obligatorio.';
  
  // DNI/RUC unique except self
  if ($d['dni_ruc']) {
    $dup = db()->fetchOne('SELECT id FROM clientes WHERE dni_ruc=? AND id!=? AND activo=1', [$d['dni_ruc'], $id]);
    if ($dup) $errors[] = 'Este DNI/RUC ya está registrado con otro cliente.';
  }

  if (!$errors) {
    db()->execute(
      'UPDATE clientes SET nombre=?, dni_ruc=?, email=?, telefono=?, direccion=?, notas=? WHERE id=?',
      [$d['nombre'], $d['dni_ruc'], $d['email'], $d['telefono'], $d['direccion'], $d['notas'], $id]
    );
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Datos del cliente actualizados.'];
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
  <link rel="icon" href="<?= getConfig('url_icono') ?: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo.png' ?>"/>
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
        <a href="index.php" class="hover:text-gray-700">Clientes</a>
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
          <h3 class="font-semibold text-gray-900 text-lg">Editar Información del Cliente</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="md:col-span-2">
              <label class="form-label">Nombre Completo <span class="text-red-500">*</span></label>
              <input name="nombre" class="form-input" required value="<?= e($cliente['nombre']) ?>"/>
            </div>
            
            <div>
              <label class="form-label">DNI / RUC</label>
              <input name="dni_ruc" class="form-input" value="<?= e($cliente['dni_ruc']) ?>"/>
            </div>
            
            <div>
              <label class="form-label">Correo electrónico</label>
              <input name="email" type="email" class="form-input" value="<?= e($cliente['email']) ?>"/>
            </div>
            
            <div>
              <label class="form-label">Teléfono / WhatsApp</label>
              <input name="telefono" class="form-input" value="<?= e($cliente['telefono']) ?>"/>
            </div>
            
            <div>
              <label class="form-label">Dirección</label>
              <input name="direccion" class="form-input" value="<?= e($cliente['direccion']) ?>"/>
            </div>
            
            <div class="md:col-span-2">
              <label class="form-label">Notas / Observaciones</label>
              <textarea name="notas" rows="3" class="form-input resize-none"><?= e($cliente['notas']) ?></textarea>
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
