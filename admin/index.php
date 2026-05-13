<?php
$ADMIN = __DIR__;
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();

$activePage = 'dashboard';
$pageTitle  = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= $pageTitle ?> — PalCus Admin</title>
  <link rel="icon" href="<?= getConfig('url_icono') ?: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo.png' ?>"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>* {font-family:'Inter',sans-serif;}</style>
</head>
<body class="bg-gray-50 min-h-screen">
<div id="app-wrapper" class="flex min-h-screen">
  <?php include $ADMIN . '/includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col lg:ml-64 min-w-0">
    <?php include $ADMIN . '/includes/header.php'; ?>
    
    <main class="flex-1 p-6">
      <!-- Welcome -->
      <div class="mb-8">
        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Bienvenido, <?= e(currentUser()['nombre']) ?></h1>
        <p class="text-gray-400 text-sm mt-1"><?= fechaLarga() ?></p>
      </div>

      <!-- Empty State -->
      <div class="flex flex-col items-center justify-center py-24 text-center">
        <div class="w-20 h-20 bg-gray-100 rounded-3xl flex items-center justify-center mb-6">
          <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
          </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900 mb-2">Panel listo para empezar</h2>
        <p class="text-gray-400 text-sm max-w-sm">Los módulos se irán construyendo paso a paso. Por ahora todo está limpio y organizado.</p>
      </div>
    </main>
  </div>
</div>

<?php include $ADMIN . '/includes/foot.php'; ?>
<?php include $ADMIN . '/includes/premium_alerts.php'; ?>
</body>
</html>
