<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= ASSETS_BASE ?>/assets/js/app.js"></script>
<?php if (!empty($extraJsCdn)): ?>
<?php foreach ((array)$extraJsCdn as $url): ?>
<script src="<?= e($url) ?>"></script>
<?php endforeach; ?>
<?php endif; ?>
<script>
(function(){
  var sidebar = document.getElementById('appSidebar');
  var toggle  = document.getElementById('sidebarToggle');
  var overlay = document.getElementById('sidebarOverlay');
  var main    = document.getElementById('mainContent');
  if (!sidebar) return;

  function isMobile() { return window.innerWidth < 992; }

  function openMobile() {
    sidebar.classList.add('mobile-open');
    overlay && overlay.classList.add('active');
  }
  function closeMobile() {
    sidebar.classList.remove('mobile-open');
    overlay && overlay.classList.remove('active');
  }
  function toggleDesktop() {
    sidebar.classList.toggle('collapsed');
    main && main.classList.toggle('sidebar-collapsed');
    try { localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed') ? '1' : '0'); } catch(e){}
  }

  if (toggle) {
    toggle.addEventListener('click', function() {
      isMobile() ? (sidebar.classList.contains('mobile-open') ? closeMobile() : openMobile()) : toggleDesktop();
    });
  }
  overlay && overlay.addEventListener('click', closeMobile);

  // Restore desktop state
  if (!isMobile()) {
    try {
      if (localStorage.getItem('sidebarCollapsed') === '1') {
        sidebar.classList.add('collapsed');
        main && main.classList.add('sidebar-collapsed');
      }
    } catch(e){}
  }
})();
</script>
<?php if (!empty($extraJs)): ?>
<?php foreach ((array)$extraJs as $js): ?>
<script src="<?= ASSETS_BASE ?>/assets/js/<?= e($js) ?>"></script>
<?php endforeach; ?>
<?php endif; ?>
<?php if (!empty($inlineJs)): ?>
<script><?= $inlineJs ?></script>
<?php endif; ?>
