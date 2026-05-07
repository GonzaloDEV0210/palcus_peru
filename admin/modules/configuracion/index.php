<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin');

$activePage = 'configuracion';
$pageTitle  = 'Configuración del Sistema';
$success    = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $configs = $_POST['config'] ?? [];
    foreach ($configs as $clave => $valor) {
        db()->execute("UPDATE configuracion SET valor = ? WHERE clave = ?", [$valor, $clave]);
    }
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Configuración actualizada correctamente.'];
    header('Location: index.php');
    exit;
}

$rows = db()->fetchAll("SELECT * FROM configuracion ORDER BY id ASC");
$configMap = [];
foreach ($rows as $r) {
    $configMap[$r['clave']] = $r;
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
    <main class="flex-1 p-6 max-w-4xl mx-auto w-full">

      <div class="mb-8">
        <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Configuración Global</h2>
        <p class="text-gray-400 text-sm">Personaliza los parámetros generales del sistema y las conexiones externas</p>
      </div>

      <form method="POST" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>

        <!-- Información de la Tienda -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Información de la Tienda</h3>
          </div>
          <div class="p-6 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Nombre del Negocio</label>
                <input name="config[nombre_tienda]" class="form-input" value="<?= e($configMap['nombre_tienda']['valor']??'') ?>"/>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Símbolo de Moneda</label>
                <input name="config[moneda_simbolo]" class="form-input" value="<?= e($configMap['moneda_simbolo']['valor']??'S/') ?>"/>
              </div>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-400 uppercase mb-2">IGV / Tax (%)</label>
              <input name="config[igv_porcentaje]" type="number" class="form-input" value="<?= e($configMap['igv_porcentaje']['valor']??'18') ?>"/>
            </div>
          </div>
        </div>

        <!-- WhatsApp & Alertas -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Alertas de WhatsApp (CallMeBot)</h3>
            <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 text-[9px] font-bold uppercase tracking-tight">Gratuito</span>
          </div>
          <div class="p-6 space-y-5">
            <p class="text-xs text-gray-400 leading-relaxed italic">
              Obtén tu API Key gratuita enviando un mensaje de WhatsApp a CallMeBot. 
              <a href="https://www.callmebot.com/blog/free-api-whatsapp-messages/" target="_blank" class="text-indigo-600 hover:underline">Saber más →</a>
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Número de WhatsApp (con código de país)</label>
                <input name="config[callmebot_phone]" class="form-input" placeholder="Ej: 51981293422" value="<?= e($configMap['callmebot_phone']['valor']??'') ?>"/>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-2">API Key de CallMeBot</label>
                <input name="config[callmebot_api_key]" class="form-input" placeholder="Tu API Key" value="<?= e($configMap['callmebot_api_key']['valor']??'') ?>"/>
              </div>
            </div>
          </div>
        </div>

        <!-- Google Drive Integration -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Almacenamiento (Google Drive)</h3>
          </div>
          <div class="p-6 space-y-5">
            <div>
              <label class="block text-xs font-bold text-gray-400 uppercase mb-2">ID de Carpeta en Google Drive</label>
              <input name="config[google_drive_folder_id]" class="form-input" placeholder="ID de la carpeta donde se guardarán los PDFs" value="<?= e($configMap['google_drive_folder_id']['valor']??'') ?>"/>
            </div>
          </div>
        </div>

        <div class="flex justify-end pt-4">
          <button type="submit" class="bg-gray-900 hover:bg-gray-700 text-white font-bold px-10 py-3 rounded-xl transition-all shadow-sm">
            Guardar Cambios
          </button>
        </div>
      </form>

    </main>
  </div>
</div>
<?php include $ADMIN . '/includes/foot.php'; ?>
</body>
</html>
