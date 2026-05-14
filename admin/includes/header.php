<?php
// header.php — Top navigation bar
// Requiere: $pageTitle (string)
$lowStockCount = count(checkLowStock());
?>
<header class="sticky top-0 z-30 bg-white border-b border-gray-200 flex items-center gap-4 px-6 py-3">

  <!-- Mobile menu toggle + Logo -->
  <div class="flex items-center gap-3">
    <button id="sidebarToggle" class="lg:hidden text-gray-500 hover:text-gray-900 transition-colors">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
    </button>
    <!-- Logo visible only on mobile (sidebar hidden) -->
    <img
      src="<?= getConfig('url_icono') ?: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/v1778354037/vjypdweg16udzxoptdxz.png' ?>"
      alt="<?= e(getConfig('nombre_tienda') ?: 'Palcus Peru') ?>"
      class="w-7 h-7 object-contain lg:hidden"
      style="filter: brightness(0);"
    />
  </div>

  <!-- Page title -->
  <div class="flex-1">
    <h1 class="text-gray-900 font-semibold text-base"><?= $pageTitle ?? 'Dashboard' ?></h1>
    <p class="text-gray-400 text-xs"><?= fechaLarga() ?></p>
  </div>

  <!-- Actions -->
  <div class="flex items-center gap-3">

    <!-- Stock alert bell -->
    <?php if ($lowStockCount > 0): ?>
    <a href="<?= APP_URL ?>/modules/inventario/?filter=low_stock"
       title="<?= $lowStockCount ?> producto(s) con bajo stock"
       class="relative text-amber-500 hover:text-amber-600 transition-colors">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
      </svg>
      <span class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center">
        <?= $lowStockCount ?>
      </span>
    </a>
    <?php endif; ?>

    <!-- User pill -->
    <div class="hidden sm:flex items-center gap-2.5 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2">
      <div class="w-7 h-7 rounded-full bg-gray-900 flex items-center justify-center">
        <span class="text-white text-xs font-bold"><?= strtoupper(substr(currentUser()['nombre'] ?? 'U', 0, 1)) ?></span>
      </div>
      <div>
        <p class="text-gray-800 text-xs font-medium leading-none"><?= htmlspecialchars(currentUser()['nombre'] ?? '') ?></p>
        <p class="text-gray-400 text-[10px] capitalize"><?= currentRole() ?></p>
      </div>
    </div>

    <!-- Logout -->
    <a href="<?= APP_URL ?>/logout.php"
       class="text-slate-400 hover:text-red-500 transition-colors"
       title="Cerrar sesión">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
      </svg>
    </a>
  </div>
</header>
