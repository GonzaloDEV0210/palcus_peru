<?php 
// foot.php — JS común para todas las páginas del admin 
include_once __DIR__ . '/premium_alerts.php';
?>
<!-- Cloudinary Widget -->
<script src="https://upload-widget.cloudinary.com/global/all.js" type="text/javascript"></script>
<script>
function toggleSidebar() {
  const s = document.getElementById('sidebar');
  const o = document.getElementById('sidebarOverlay');
  const h = s.classList.contains('-translate-x-full');
  s.classList.toggle('-translate-x-full', !h);
  o.classList.toggle('hidden', !h);
}
document.getElementById('sidebarToggle')?.addEventListener('click', toggleSidebar);
document.getElementById('sidebarClose')?.addEventListener('click', toggleSidebar);

// Render flash messages if present
<?php
$flash = $_SESSION['flash'] ?? null;
if ($flash) {
    echo "window.addEventListener('load', () => { window.PalCus.toast('{$flash['type']}', '" . addslashes($flash['msg']) . "'); });";
    unset($_SESSION['flash']);
}
?>
</script>
