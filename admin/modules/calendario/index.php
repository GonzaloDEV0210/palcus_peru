<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();

$activePage = 'calendario';
$pageTitle  = 'Calendario de Eventos';

// Simple calendar logic
$month = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('m');
$year  = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');

$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$numberDays = date('t', $firstDayOfMonth);
$dateComponents = getdate($firstDayOfMonth);
$monthName = strftime('%B', $firstDayOfMonth);
$dayOfWeek = $dateComponents['wday'];

$prevMonth = date('m', strtotime('-1 month', $firstDayOfMonth));
$prevYear  = date('Y', strtotime('-1 month', $firstDayOfMonth));
$nextMonth = date('m', strtotime('+1 month', $firstDayOfMonth));
$nextYear  = date('Y', strtotime('+1 month', $firstDayOfMonth));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= $pageTitle ?> — PalCus Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>* {font-family:'Inter',sans-serif;}</style>
</head>
<body class="bg-gray-50 min-h-screen">
<div id="app-wrapper" class="flex min-h-screen">
  <?php include $ADMIN . '/includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col lg:ml-64 min-w-0">
    <?php include $ADMIN . '/includes/header.php'; ?>
    <main class="flex-1 p-6 space-y-6">

      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Calendario</h2>
          <p class="text-gray-400 text-sm">Próximos eventos, recordatorios y fechas clave</p>
        </div>
        <div class="flex items-center bg-white border border-gray-100 rounded-xl overflow-hidden shadow-sm">
           <a href="?m=<?= $prevMonth ?>&y=<?= $prevYear ?>" class="p-2.5 hover:bg-gray-50 text-gray-400 hover:text-gray-900 transition-colors">
             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
           </a>
           <div class="px-4 py-2 text-sm font-bold text-gray-900 border-x border-gray-50 min-w-[140px] text-center">
             <?= date('F Y', $firstDayOfMonth) ?>
           </div>
           <a href="?m=<?= $nextMonth ?>&y=<?= $nextYear ?>" class="p-2.5 hover:bg-gray-50 text-gray-400 hover:text-gray-900 transition-colors">
             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
           </a>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="grid grid-cols-7 border-b border-gray-50 bg-gray-50">
          <?php $days = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb']; 
                foreach($days as $d): ?>
          <div class="py-3 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest"><?= $d ?></div>
          <?php endforeach; ?>
        </div>
        <div class="grid grid-cols-7 divide-x divide-y divide-gray-50">
          <?php 
          // Empty days before start
          for ($i=0; $i<$dayOfWeek; $i++) echo '<div class="h-32 bg-gray-50/50"></div>';
          
          // Days of the month
          for ($d=1; $d<=$numberDays; $d++): 
            $isToday = ($d == (int)date('d') && $month == (int)date('m') && $year == (int)date('Y'));
          ?>
          <div class="h-32 p-3 hover:bg-gray-50 transition-colors group cursor-pointer relative">
            <span class="text-sm font-bold <?= $isToday ? 'bg-gray-900 text-white w-6 h-6 flex items-center justify-center rounded-full' : 'text-gray-400 group-hover:text-gray-900' ?>">
              <?= $d ?>
            </span>
            
            <!-- Eventos Mock (Solo visual por ahora) -->
            <?php if ($d == 15): ?>
              <div class="mt-2 p-1.5 bg-indigo-50 border-l-4 border-indigo-500 rounded text-[10px] text-indigo-700 font-bold truncate">Reponer Stock</div>
            <?php endif; ?>
            <?php if ($d == 22): ?>
              <div class="mt-2 p-1.5 bg-emerald-50 border-l-4 border-emerald-500 rounded text-[10px] text-emerald-700 font-bold truncate">Cierre de Mes</div>
            <?php endif; ?>
          </div>
          <?php endfor; ?>
          
          <?php 
          // Empty days after end
          $remaining = (7 - (($dayOfWeek + $numberDays) % 7)) % 7;
          for ($i=0; $i<$remaining; $i++) echo '<div class="h-32 bg-gray-50/50"></div>';
          ?>
        </div>
      </div>

    </main>
  </div>
</div>
<?php include $ADMIN . '/includes/foot.php'; ?>
</body>
</html>
