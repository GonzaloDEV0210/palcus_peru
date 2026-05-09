<?php
// sidebar.php — se incluye en todas las páginas del admin
// Requiere: $activePage (string) definido en la página que lo incluye
$user = currentUser();
$role = currentRole();

$nav = [
  'principal' => [
    ['icon' => 'home',      'label' => 'Dashboard',   'href' => 'index.php',              'page' => 'dashboard', 'roles' => ['admin','vendedor','almacenero']],
  ],
  'ventas' => [
    ['icon' => 'cart',      'label' => 'Ventas',       'href' => 'modules/ventas/',        'page' => 'ventas',    'roles' => ['admin','vendedor']],
    ['icon' => 'users',     'label' => 'Clientes',     'href' => 'modules/clientes/',      'page' => 'clientes',  'roles' => ['admin','vendedor']],
  ],
  'inventario' => [
    ['icon' => 'package',   'label' => 'Productos',    'href' => 'modules/productos/',     'page' => 'productos', 'roles' => ['admin','almacenero']],
    ['icon' => 'layers',    'label' => 'Inventario',   'href' => 'modules/inventario/',    'page' => 'inventario','roles' => ['admin','almacenero']],
    ['icon' => 'truck',     'label' => 'Proveedores',  'href' => 'modules/proveedores/',   'page' => 'proveedores','roles' => ['admin','almacenero']],
    ['icon' => 'tag',       'label' => 'Categorías',   'href' => 'modules/categorias/',    'page' => 'categorias', 'roles' => ['admin','almacenero']],
    ['icon' => 'credit',    'label' => 'Gastos',       'href' => 'modules/gastos/',        'page' => 'gastos',    'roles' => ['admin']],
  ],
  'gestión' => [
    ['icon' => 'chart',     'label' => 'Reportes',     'href' => 'modules/reportes/',      'page' => 'reportes',  'roles' => ['admin']],
    ['icon' => 'file',      'label' => 'Documentos',   'href' => 'modules/documentos/',    'page' => 'documentos','roles' => ['admin']],
    ['icon' => 'calendar',  'label' => 'Calendario',   'href' => 'modules/calendario/',    'page' => 'calendario','roles' => ['admin','vendedor']],
  ],
  'sistema' => [
    ['icon' => 'cog',       'label' => 'Configuración','href' => 'modules/configuracion/', 'page' => 'configuracion','roles' => ['admin']],
    ['icon' => 'shield',    'label' => 'Usuarios',     'href' => 'modules/usuarios/',      'page' => 'usuarios',  'roles' => ['admin']],
  ],
];

function sidebarIcon(string $name): string {
  $icons = [
    'home'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
    'cart'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>',
    'users'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
    'package'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
    'layers'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>',
    'truck'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h11a2 2 0 012 2v3m0 0h3l3 5v4h-3m-3 0a2 2 0 11-4 0 2 2 0 014 0zm9 0a2 2 0 11-4 0 2 2 0 014 0z"/>',
    'credit'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>',
    'chart'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
    'file'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>',
    'calendar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
    'cog'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
    'shield'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
    'tag'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>',
  ];
  return $icons[$name] ?? '';
}

$groupLabels = [
  'principal' => '',
  'ventas'    => 'Ventas',
  'inventario'=> 'Inventario',
  'gestión'   => 'Gestión',
  'sistema'   => 'Sistema',
];
?>

<aside id="sidebar"
  class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 flex flex-col transition-transform duration-300 lg:translate-x-0 -translate-x-full"
  aria-label="Sidebar">

  <!-- Logo -->
  <div class="flex items-center gap-3 px-5 py-5 border-b border-slate-800">
    <img
      src="<?= getConfig('url_logo') ?: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo.png' ?>"
      alt="<?= e(getConfig('nombre_tienda') ?: 'PalCus') ?>"
      class="w-9 h-9 object-contain shrink-0"
      style="filter: brightness(0) invert(1);"
    />
    <div>
      <p class="text-white font-bold text-sm leading-none">PalCus Admin</p>
      <p class="text-slate-500 text-xs mt-0.5">Panel de Gestión</p>
    </div>
    <!-- Close btn mobile -->
    <button id="sidebarClose" class="ml-auto lg:hidden text-slate-400 hover:text-white">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
  </div>

  <!-- Navigation -->
  <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
    <?php foreach ($nav as $group => $items): ?>
      <?php if ($groupLabels[$group]): ?>
      <p class="text-slate-500 text-[10px] font-semibold uppercase tracking-widest px-3 pt-4 pb-1">
        <?= $groupLabels[$group] ?>
      </p>
      <?php endif; ?>

      <?php foreach ($items as $item): ?>
        <?php if (!in_array($role, $item['roles'])) continue; ?>
        <?php $isActive = ($activePage ?? '') === $item['page']; ?>
        <a href="<?= APP_URL ?>/<?= $item['href'] ?>"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                  <?= $isActive
                    ? 'bg-white/10 text-white border border-white/10'
                    : 'text-slate-400 hover:bg-slate-800 hover:text-white' ?>">
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <?= sidebarIcon($item['icon']) ?>
          </svg>
          <?= $item['label'] ?>
          <?php if ($item['page'] === 'ventas'): ?>
            <!-- Badge de notificaciones futuro -->
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>

  <!-- User info -->
  <div class="border-t border-slate-800 p-4">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-full bg-white/10 border border-white/10 flex items-center justify-center shrink-0">
        <span class="text-white text-sm font-bold"><?= strtoupper(substr($user['nombre'] ?? 'U', 0, 1)) ?></span>
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-white text-sm font-medium truncate"><?= htmlspecialchars($user['nombre'] ?? '') ?></p>
        <p class="text-slate-500 text-xs capitalize"><?= $user['rol'] ?? '' ?></p>
      </div>
      <a href="<?= APP_URL ?>/logout.php"
         title="Cerrar sesión"
         class="text-slate-500 hover:text-red-400 transition-colors">
        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
      </a>
    </div>
  </div>
</aside>

<!-- Overlay mobile -->
<div id="sidebarOverlay"
  class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden"
  onclick="toggleSidebar()"></div>
