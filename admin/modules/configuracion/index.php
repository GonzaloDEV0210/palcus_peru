<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();

// --- LÓGICA DE PROCESAMIENTO ---
$message = null;
$error   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Procesar archivos de Cloudinary
    $filesToProcess = [
        'logo'  => ['key' => 'url_logo',  'label' => 'Logo'],
        'icono' => ['key' => 'url_icono', 'label' => 'Icono'],
        'hero'  => ['key' => 'url_hero',  'label' => 'Hero'],
    ];

    $changes = 0;
    foreach ($filesToProcess as $inputName => $info) {
        if (!empty($_FILES[$inputName]['tmp_name'])) {
            $oldUrl = getConfig($info['key']);
            $newUrl = cloudinaryUpload($_FILES[$inputName]);
            if ($newUrl) {
                if ($oldUrl) cloudinaryDestroy($oldUrl);
                setConfig($info['key'], $newUrl);
                $changes++;
            }
        }
    }

    // 2. Procesar textos y campos simples
    $simpleFields = [
        'tienda_email'     => 'tienda_email',
        'social_facebook'  => 'social_facebook',
        'social_instagram' => 'social_instagram',
        'social_tiktok'    => 'social_tiktok',
        'delivery_cost'    => 'delivery_cost',
        'top_announcement' => 'top_announcement',
    ];

    foreach ($simpleFields as $postKey => $dbKey) {
        if (isset($_POST[$postKey])) {
            setConfig($dbKey, trim($_POST[$postKey]));
            $changes++;
        }
    }

    // 3. Procesar listas dinámicas (JSON)
    if (isset($_POST['whatsapps'])) {
        $whatsapps = array_filter($_POST['whatsapps'], function($item) {
            return !empty($item['number']);
        });
        setConfig('whatsapp_list', json_encode(array_values($whatsapps)));
        $changes++;
    }
    if (isset($_POST['direcciones'])) {
        $direcciones = array_filter($_POST['direcciones'], function($item) {
            return !empty($item['address']);
        });
        setConfig('direccion_list', json_encode(array_values($direcciones)));
        $changes++;
    }

    if ($changes > 0) {
        $message = "Configuración actualizada con éxito.";
    }
}

// Cargar datos
$whatsapps   = json_decode(getConfig('whatsapp_list') ?? '[]', true);
$direcciones = json_decode(getConfig('direccion_list') ?? '[]', true);

$activePage = 'configuracion_sistema';
$pageTitle  = 'Configuración del Sistema';
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
    body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; }
    .premium-card {
      background: white; border-radius: 2rem; border: 1px solid rgba(226, 232, 240, 0.8);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.02); transition: all 0.3s;
    }
    .tab-btn {
      padding: 0.75rem 1.5rem; border-radius: 1rem; font-weight: 600; font-size: 0.875rem;
      transition: all 0.3s; cursor: pointer; color: #64748b;
    }
    .tab-btn.active { background: white; color: #0f172a; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
    .glass-input {
      background: #f1f5f9; border: 2px solid transparent; border-radius: 1.25rem;
      padding: 0.75rem 1.25rem; font-size: 0.9rem; transition: all 0.3s; width: 100%;
    }
    .glass-input:focus { background: white; border-color: #3b82f6; outline: none; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
    .upload-zone { border: 2px dashed #e2e8f0; border-radius: 1.5rem; padding: 2rem; text-align: center; cursor: pointer; transition: all 0.3s; position: relative; }
    .upload-zone:hover { border-color: #3b82f6; background: #f0f7ff; }
    input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .btn-add { background: #eff6ff; color: #3b82f6; border-radius: 1rem; padding: 0.5rem 1rem; font-size: 0.75rem; font-weight: 700; transition: all 0.3s; }
    .btn-add:hover { background: #dbeafe; }
    .btn-save { background: #0f172a; color: white; border-radius: 1.25rem; padding: 1rem 2rem; font-weight: 700; transition: all 0.3s; }
    .btn-save:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
    .tab-content { display: none !important; }
    .tab-content.active { display: block !important; }
    .tab-btn.active { background: white !important; color: #0f172a !important; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important; border-bottom: 3px solid #94a3b8; }
  </style>
  <script>
    function switchTab(btn, tabId) {
      // 1. Ocultar todos los contenidos
      document.querySelectorAll('.tab-content').forEach(c => {
        c.style.display = 'none';
        c.classList.remove('active');
      });
      // 2. Desactivar todos los botones
      document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('active');
      });
      // 3. Mostrar el seleccionado
      const target = document.getElementById(tabId);
      if(target) {
        target.style.setProperty('display', 'block', 'important');
        target.classList.add('active');
      }
      // 4. Activar el botón
      btn.classList.add('active');
    }
  </script>

</head>

<body>
<div class="flex min-h-screen">
  <?php include $ADMIN . '/includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col lg:ml-64 min-w-0">
    <?php include $ADMIN . '/includes/header.php'; ?>
    <main class="flex-1 p-6 lg:p-10 max-w-7xl mx-auto w-full">
      
      <form method="POST" enctype="multipart/form-data" id="config-form">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
          <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Configuración Sistema</h1>
            <p class="text-slate-500 mt-1">Personaliza cada detalle de tu tienda desde un solo lugar.</p>
          </div>
          <div class="flex items-center gap-3">
            <button type="button" onclick="window.location.reload()" class="px-6 py-3 text-slate-500 font-bold hover:text-slate-800 transition-colors">
              Deshacer cambios
            </button>
            <button type="submit" class="btn-save flex items-center gap-3 px-8 py-3">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
              Guardar Configuración
            </button>
          </div>
        </div>


      <?php if ($message): ?>
        <div class="mb-8 p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3 animate-fade-in" data-flash>
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
          <span class="font-bold text-sm"><?= $message ?></span>
        </div>
      <?php endif; ?>

      <!-- Tabs Navigation -->
      <div class="flex p-1.5 bg-slate-200/50 rounded-2xl w-fit mb-10 gap-1 overflow-x-auto no-scrollbar">
        <button type="button" class="tab-btn active" onclick="switchTab(this, 'sys-tab-visual')">Visual & Marca</button>
        <button type="button" class="tab-btn" onclick="switchTab(this, 'sys-tab-contacto')">Información de Contacto</button>
        <button type="button" class="tab-btn" onclick="switchTab(this, 'sys-tab-ventas')">Ventas & Social</button>
      </div>


        
        <!-- TAB 1: VISUAL -->
        <div id="sys-tab-visual" class="tab-content active">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <div class="lg:col-span-7 space-y-8">
              <div class="premium-card p-8">
                <h2 class="text-xl font-bold mb-6">Logos e Iconografía</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div class="space-y-3">
                    <label class="text-sm font-bold text-slate-700">Logo Sidebar</label>
                    <div class="upload-zone">
                      <input type="file" name="logo" onchange="previewImage(this, 'p-logo')">
                      <p class="text-xs font-bold text-slate-600">Cambiar Logo</p>
                    </div>
                  </div>
                  <div class="space-y-3">
                    <label class="text-sm font-bold text-slate-700">Icono / Favicon</label>
                    <div class="upload-zone">
                      <input type="file" name="icono" onchange="previewImage(this, 'p-icon')">
                      <p class="text-xs font-bold text-slate-600">Cambiar Icono</p>
                    </div>
                  </div>
                </div>
              </div>
              <div class="premium-card p-8">
                <h2 class="text-xl font-bold mb-6">Banner Hero</h2>
                <div class="upload-zone py-8">
                  <input type="file" name="hero" onchange="previewImage(this, 'p-hero')">
                  <p class="text-sm font-bold">Selecciona un nuevo Banner Principal</p>
                </div>
              </div>
              <div class="premium-card p-8">
                <h2 class="text-xl font-bold mb-6">Barra de Anuncio Superior</h2>
                <div class="space-y-4">
                  <label class="text-sm font-bold text-slate-700">Texto del Anuncio</label>
                  <input type="text" name="top_announcement" value="<?= e(getConfig('top_announcement') ?: 'Envío gratis en compras mayores a S/200 · 100% Algodón Peruano') ?>" class="glass-input" placeholder="Ej: Envío gratis en compras mayores a S/200...">
                  <p class="text-xs text-slate-400">Este texto aparece en la parte más alta de la web (encima del menú).</p>
                </div>
              </div>
            </div>
            <div class="lg:col-span-5 space-y-6">
              <!-- Previsualización Logo Sidebar -->
              <div class="premium-card p-6">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Vista Sidebar</h3>
                <div class="bg-slate-900 rounded-2xl p-6 flex justify-center border-4 border-white shadow-sm">
                  <img id="p-logo" src="<?= e(getConfig('url_logo')) ?>" class="max-h-12 object-contain">
                </div>
              </div>
              
              <!-- Previsualización Icono App -->
              <div class="premium-card p-6">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Previsualización Icono</h3>
                <div class="flex items-center gap-6">
                  <div class="w-20 h-20 bg-white border-2 border-slate-100 rounded-3xl shadow-sm flex items-center justify-center p-2 overflow-hidden">
                    <img id="p-icon" src="<?= e(getConfig('url_icono')) ?>" class="w-full h-full object-contain rounded-2xl">
                  </div>
                  <div class="flex-1 space-y-2">
                    <div class="h-2 w-full bg-slate-100 rounded-full"></div>
                    <div class="h-2 w-2/3 bg-slate-100 rounded-full"></div>
                  </div>
                </div>
              </div>

              <!-- Previsualización Hero -->
              <div class="premium-card p-6">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Preview Hero</h3>
                <div class="rounded-2xl overflow-hidden aspect-video bg-slate-100 relative">
                  <img id="p-hero" src="<?= e(getConfig('url_hero')) ?>" class="w-full h-full object-cover">
                  <div class="absolute inset-0 bg-black/20"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 2: CONTACTO -->
        <!-- TAB 2: CONTACTO -->
        <div id="sys-tab-contacto" class="tab-content" style="background: white; padding: 20px; border-radius: 20px;">
          
          <!-- SECCIÓN WHATSAPP -->
          <div style="margin-bottom: 30px; border: 2px solid #e2e8f0; padding: 20px; border-radius: 15px;">
            <h3 style="font-weight: bold; font-size: 1.2rem; margin-bottom: 15px;">1. Números de WhatsApp</h3>
            <div id="whatsapp-list" style="display: flex; flex-direction: column; gap: 10px;">
              <?php if (empty($whatsapps)) $whatsapps = [['code' => '51', 'number' => '']]; ?>
              <?php foreach ($whatsapps as $i => $wa): ?>
              <div style="display: flex; gap: 10px; align-items: center; background: #f8fafc; padding: 10px; border-radius: 10px;">
                <select name="whatsapps[<?= $i ?>][code]" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 8px;">
                  <option value="51" <?= ($wa['code']??'') == '51' ? 'selected' : '' ?>>🇵🇪 +51</option>
                  <option value="57" <?= ($wa['code']??'') == '57' ? 'selected' : '' ?>>🇨🇴 +57</option>
                  <option value="56" <?= ($wa['code']??'') == '56' ? 'selected' : '' ?>>🇨🇱 +56</option>
                  <option value="1" <?= ($wa['code']??'') == '1' ? 'selected' : '' ?>>🇺🇸 +1</option>
                </select>
                <input type="text" name="whatsapps[<?= $i ?>][number]" value="<?= e($wa['number']??'') ?>" placeholder="Número de WhatsApp" style="flex: 1; padding: 8px; border: 1px solid #cbd5e1; border-radius: 8px;">
                <button type="button" onclick="this.parentElement.remove()" style="color: #ef4444; cursor: pointer; border: none; background: none;">Eliminar</button>
              </div>
              <?php endforeach; ?>
            </div>
            <button type="button" onclick="addWhatsApp()" style="margin-top: 15px; background: #eff6ff; color: #3b82f6; padding: 8px 15px; border-radius: 8px; border: none; font-weight: bold; cursor: pointer;">+ Añadir otro número</button>
          </div>

          <!-- SECCIÓN CORREO -->
          <div style="margin-bottom: 30px; border: 2px solid #2563eb; padding: 20px; border-radius: 15px; background: #f0f7ff;">
            <h3 style="font-weight: bold; font-size: 1.2rem; margin-bottom: 5px; color: #1e40af;">2. Correo de Contacto</h3>
            <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 15px;">Este es el correo que verán tus clientes.</p>
            <input type="email" name="tienda_email" value="<?= e(getConfig('tienda_email')) ?>" placeholder="ejemplo@correo.com" style="width: 100%; max-width: 400px; padding: 12px; border: 2px solid #3b82f6; border-radius: 10px; font-size: 1rem;">
          </div>

          <!-- SECCIÓN TIENDAS -->
          <div style="margin-bottom: 30px; border: 2px solid #e2e8f0; padding: 20px; border-radius: 15px;">
            <h3 style="font-weight: bold; font-size: 1.2rem; margin-bottom: 15px;">3. Direcciones de Tiendas</h3>
            <div id="direccion-list" style="display: flex; flex-direction: column; gap: 10px;">
              <?php if (empty($direcciones)) $direcciones = [['address' => '']]; ?>
              <?php foreach ($direcciones as $i => $dir): ?>
              <div style="display: flex; gap: 10px; align-items: center; background: #f8fafc; padding: 10px; border-radius: 10px;">
                <input type="text" name="direcciones[<?= $i ?>][address]" value="<?= e($dir['address']??'') ?>" placeholder="Dirección completa" style="flex: 1; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;">
                <button type="button" onclick="this.parentElement.remove()" style="color: #ef4444; cursor: pointer; border: none; background: none;">Eliminar</button>
              </div>
              <?php endforeach; ?>
            </div>
            <button type="button" onclick="addDireccionSimple()" style="margin-top: 15px; background: #eff6ff; color: #3b82f6; padding: 8px 15px; border-radius: 8px; border: none; font-weight: bold; cursor: pointer;">+ Añadir otra dirección</button>
          </div>
        </div>

        <!-- TAB 3: VENTAS & SOCIAL -->
        <div id="sys-tab-ventas" class="tab-content">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="premium-card p-8">
              <h2 class="text-xl font-bold mb-6">Costos de Envío</h2>
              <div class="space-y-4">
                <label class="text-sm font-bold text-slate-700">Precio de Delivery (Base)</label>
                <div class="relative max-w-[200px]">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">S/</span>
                  <input type="number" step="0.01" name="delivery_cost" value="<?= e(getConfig('delivery_cost')) ?>" class="glass-input pl-10" placeholder="0.00">
                </div>
              </div>
            </div>

            <div class="premium-card p-8">
              <h2 class="text-xl font-bold mb-2">Redes Sociales</h2>
              <p class="text-xs text-slate-400 mb-8">Añade los enlaces a tus perfiles para mostrarlos en el sitio.</p>
              <div class="space-y-4">

                <!-- Facebook -->
                <div class="flex items-center gap-4 p-3 rounded-2xl border-2 border-transparent hover:border-[#1877F2]/20 hover:bg-[#1877F2]/5 transition-all duration-300 group">
                  <div class="w-11 h-11 rounded-full flex-shrink-0 flex items-center justify-center shadow-lg" style="background: #1877F2;">
                    <svg class="w-6 h-6 text-white" fill="white" viewBox="0 0 24 24">
                      <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                  </div>
                  <div class="flex-1">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1 ml-1">Facebook</label>
                    <input type="text" name="social_facebook" value="<?= e(getConfig('social_facebook')) ?>" class="glass-input bg-white" placeholder="https://facebook.com/tupagina" style="border: 1.5px solid #e2e8f0; padding: 0.5rem 1rem;">
                  </div>
                </div>

                <!-- Instagram -->
                <div class="flex items-center gap-4 p-3 rounded-2xl border-2 border-transparent hover:border-pink-200 hover:bg-pink-50/50 transition-all duration-300 group">
                  <div class="w-11 h-11 rounded-full flex-shrink-0 flex items-center justify-center shadow-lg" style="background: linear-gradient(135deg, #405DE6, #5851DB, #833AB4, #C13584, #E1306C, #FD1D1D, #F56040, #F77737, #FCAF45, #FFDC80);">
                    <svg class="w-6 h-6 text-white" fill="white" viewBox="0 0 24 24">
                      <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                  </div>
                  <div class="flex-1">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1 ml-1">Instagram</label>
                    <input type="text" name="social_instagram" value="<?= e(getConfig('social_instagram')) ?>" class="glass-input bg-white" placeholder="https://instagram.com/tucuenta" style="border: 1.5px solid #e2e8f0; padding: 0.5rem 1rem;">
                  </div>
                </div>

                <!-- TikTok -->
                <div class="flex items-center gap-4 p-3 rounded-2xl border-2 border-transparent hover:border-slate-200 hover:bg-slate-50 transition-all duration-300 group">
                  <div class="w-11 h-11 rounded-full flex-shrink-0 flex items-center justify-center shadow-lg" style="background: #010101;">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none">
                      <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.18 8.18 0 004.78 1.52V6.75a4.84 4.84 0 01-1.01-.06z" fill="#FF004F"/>
                      <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.18 8.18 0 004.78 1.52V6.75a4.84 4.84 0 01-1.01-.06z" fill="white" opacity="0.15"/>
                      <path d="M15.82 2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5c-.65 0-1.24-.22-1.71-.57a2.89 2.89 0 002.88 2.57 2.89 2.89 0 002.88-2.89V2h2.28z" fill="#00F2EA"/>
                    </svg>
                  </div>
                  <div class="flex-1">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1 ml-1">TikTok</label>
                    <input type="text" name="social_tiktok" value="<?= e(getConfig('social_tiktok')) ?>" class="glass-input bg-white" placeholder="https://tiktok.com/@tuusuario" style="border: 1.5px solid #e2e8f0; padding: 0.5rem 1rem;">
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>


      </form>
    </main>
  </div>
</div>

<script>
  function addWhatsApp() {
    let waCount = document.querySelectorAll('#whatsapp-list > div').length;
    const div = document.createElement('div');
    div.style.cssText = 'display: flex; gap: 10px; align-items: center; background: #f8fafc; padding: 10px; border-radius: 10px;';
    div.innerHTML = `
      <select name="whatsapps[${waCount}][code]" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 8px;">
        <option value="51" selected>🇵🇪 +51</option>
        <option value="57">🇨🇴 +57</option>
        <option value="56">🇨🇱 +56</option>
        <option value="1">🇺🇸 +1</option>
      </select>
      <input type="text" name="whatsapps[${waCount}][number]" placeholder="Número de WhatsApp" style="flex: 1; padding: 8px; border: 1px solid #cbd5e1; border-radius: 8px;">
      <button type="button" onclick="this.parentElement.remove()" style="color: #ef4444; cursor: pointer; border: none; background: none;">Eliminar</button>
    `;
    document.getElementById('whatsapp-list').appendChild(div);
  }

  function addDireccionSimple() {
    let dirCount = document.querySelectorAll('#direccion-list > div').length;
    const div = document.createElement('div');
    div.style.cssText = 'display: flex; gap: 10px; align-items: center; background: #f8fafc; padding: 10px; border-radius: 10px;';
    div.innerHTML = `
      <input type="text" name="direcciones[${dirCount}][address]" placeholder="Dirección completa" style="flex: 1; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;">
      <button type="button" onclick="this.parentElement.remove()" style="color: #ef4444; cursor: pointer; border: none; background: none;">Eliminar</button>
    `;
    document.getElementById('direccion-list').appendChild(div);
  }

  function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = e => document.getElementById(previewId).src = e.target.result;
      reader.readAsDataURL(input.files[0]);
    }
  }
</script>
<?php include $ADMIN . '/includes/foot.php'; ?>
</body>
</html>
