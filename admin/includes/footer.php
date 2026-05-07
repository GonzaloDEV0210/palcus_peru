<?php // footer.php ?>
  </main><!-- end #main-content -->
</div><!-- end #app-wrapper -->

<script>
  // Sidebar toggle (mobile)
  function toggleSidebar() {
    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('sidebarOverlay');
    const isHidden = sidebar.classList.contains('-translate-x-full');
    sidebar.classList.toggle('-translate-x-full', !isHidden);
    overlay.classList.toggle('hidden', !isHidden);
  }
  document.getElementById('sidebarToggle')?.addEventListener('click', toggleSidebar);
  document.getElementById('sidebarClose')?.addEventListener('click', toggleSidebar);

  // Flash auto-hide
  setTimeout(() => {
    document.querySelectorAll('[data-flash]').forEach(el => {
      el.style.transition = 'opacity 0.5s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 500);
    });
  }, 3500);
</script>
</body>
</html>
