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
    
    // Guardar configuraciones de texto
    $configs = $_POST['config'] ?? [];
    foreach ($configs as $clave => $valor) {
        setConfig($clave, $valor);
    }

    // Manejar subida de imágenes de Branding
    $filesToUpload = [
        'url_icono' => 'icono',
        'url_logo'  => 'logo',
        'url_hero'  => 'hero'
    ];

    foreach ($filesToUpload as $clave => $fileKey) {
        if (!empty($_FILES[$fileKey]['tmp_name'])) {
            $url = cloudinaryUpload($_FILES[$fileKey]);
            if ($url) {
                // Opcional: eliminar el anterior si existe
                $oldUrl = getConfig($clave);
                if ($oldUrl) cloudinaryDestroy($oldUrl);
                
                setConfig($clave, $url);
            }
        }
    }

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Configuración y activos de marca actualizados.'];
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
  <link rel="icon" href="<?= getConfig('url_icono') ?: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo.png' ?>"/>
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

      <form method="POST" enctype="multipart/form-data" class="space-y-6">
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

        <!-- Identidad Visual (Branding) -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Identidad Visual (Branding)</h3>
            <span class="text-[10px] text-gray-400 font-medium">Cloudinary Storage</span>
          </div>
          <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <!-- Icono -->
              <div class="space-y-3">
                <label class="block text-xs font-bold text-gray-400 uppercase">Icono / Favicon</label>
                <div class="flex flex-col items-center p-4 border-2 border-dashed border-gray-100 rounded-2xl hover:border-gray-300 transition-colors bg-gray-50/50">
                  <img src="<?= getConfig('url_icono') ?: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo.png' ?>" 
                       class="w-12 h-12 object-contain mb-3 drop-shadow-sm bg-white p-1 rounded-lg" alt="Icono actual"/>
                  <input type="file" name="icono" id="icono" class="hidden" accept="image/*"/>
                  <label for="icono" class="cursor-pointer text-[10px] font-bold text-indigo-600 uppercase hover:text-indigo-800 transition-colors">Cambiar Icono</label>
                </div>
                <p class="text-[9px] text-gray-400 text-center italic">Uso: Favicon y Preloader</p>
              </div>

              <!-- Logo -->
              <div class="space-y-3">
                <label class="block text-xs font-bold text-gray-400 uppercase">Logo Principal (Nav)</label>
                <div class="flex flex-col items-center p-4 border-2 border-dashed border-gray-100 rounded-2xl hover:border-gray-300 transition-colors bg-gray-50/50">
                  <div class="h-12 flex items-center justify-center mb-3">
                    <img src="<?= getConfig('url_logo') ?: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo.png' ?>" 
                         class="max-h-full object-contain" style="filter: brightness(0);" alt="Logo actual"/>
                  </div>
                  <input type="file" name="logo" id="logo" class="hidden" accept="image/*"/>
                  <label for="logo" class="cursor-pointer text-[10px] font-bold text-indigo-600 uppercase hover:text-indigo-800 transition-colors">Subir Logo</label>
                </div>
                <p class="text-[9px] text-gray-400 text-center italic">Uso: Navbar y Sidebar</p>
              </div>

              <!-- Hero -->
              <div class="space-y-3">
                <label class="block text-xs font-bold text-gray-400 uppercase">Hero Image (Banner)</label>
                <div class="flex flex-col items-center p-4 border-2 border-dashed border-gray-100 rounded-2xl hover:border-gray-300 transition-colors bg-gray-50/50">
                  <img src="<?= getConfig('url_hero') ?: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/hero-banner-mujer.jpg' ?>" 
                       class="w-full h-12 object-cover rounded-lg mb-3 shadow-sm" alt="Hero actual"/>
                  <input type="file" name="hero" id="hero" class="hidden" accept="image/*"/>
                  <label for="hero" class="cursor-pointer text-[10px] font-bold text-indigo-600 uppercase hover:text-indigo-800 transition-colors">Cambiar Hero</label>
                </div>
                <p class="text-[9px] text-gray-400 text-center italic">Uso: Portada de la web</p>
              </div>
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
