<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin');

$activePage = 'documentos';
$pageTitle  = 'Subir Documento';
$errors     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    $nombre = trim($_POST['nombre'] ?? '');
    $url    = trim($_POST['drive_url'] ?? '');
    $tipo   = trim($_POST['tipo'] ?? 'otro');

    if (!$nombre) $errors[] = 'El nombre es obligatorio.';
    if (!$url)    $errors[] = 'El enlace de Google Drive es obligatorio.';

    if (!$errors) {
        db()->execute(
            "INSERT INTO documentos (nombre, drive_url, tipo, usuario_id) VALUES (?,?,?,?)",
            [$nombre, $url, $tipo, currentUser()['id']]
        );
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Documento registrado correctamente.'];
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
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>
    * {font-family:'Inter',sans-serif;}
    .form-input {width:100%;padding:.625rem .875rem;border:1.5px solid #e5e7eb;border-radius:.625rem;font-size:.9375rem;color:#111827;background:#fafafa;outline:none;transition:all .15s;}
    .form-input:focus{border-color:#111827;background:#fff;box-shadow:0 0 0 3px rgba(17,24,39,.07);}
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
<div id="app-wrapper" class="flex min-h-screen">
  <?php include $ADMIN . '/includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col lg:ml-64 min-w-0">
    <?php include $ADMIN . '/includes/header.php'; ?>
    <main class="flex-1 p-6 max-w-2xl mx-auto w-full">

      <div class="mb-6">
        <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Vincular Documento</h2>
        <p class="text-gray-400 text-sm">Registra enlaces a documentos guardados en Google Drive</p>
      </div>

      <?php if ($errors): ?>
      <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3 space-y-1">
        <?php foreach ($errors as $err): ?><p>• <?= e($err) ?></p><?php endforeach; ?>
      </div>
      <?php endif; ?>

      <form method="POST" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>

        <div>
          <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Nombre del Documento</label>
          <input name="nombre" class="form-input" placeholder="Ej: Factura de Compra Mayo" required value="<?= e($_POST['nombre'] ?? '') ?>"/>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Tipo de Documento</label>
          <select name="tipo" class="form-input">
            <option value="factura">Factura / Recibo</option>
            <option value="contrato">Contrato</option>
            <option value="reporte">Reporte</option>
            <option value="otro">Otro</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Enlace de Google Drive</label>
          <input name="drive_url" type="url" class="form-input" placeholder="https://drive.google.com/..." required value="<?= e($_POST['drive_url'] ?? '') ?>"/>
          <p class="text-[10px] text-gray-400 mt-1">Asegúrate de que el documento tenga permisos de lectura.</p>
        </div>

        <div class="flex gap-3 pt-2">
          <a href="index.php" class="flex-1 text-center py-3 border border-gray-200 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors">Cancelar</a>
          <button type="submit" class="flex-1 bg-gray-900 hover:bg-gray-700 text-white text-sm font-semibold py-3 rounded-xl transition-colors">Vincular Documento</button>
        </div>
      </form>

    </main>
  </div>
</div>
<?php include $ADMIN . '/includes/foot.php'; ?>
</body>
</html>
