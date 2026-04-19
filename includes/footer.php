  </div><!-- /.container-fluid -->
</main><!-- /.main-content -->

<footer class="app-footer">
  <div style="font-size:0.76rem;color:var(--text-muted);">
    <?= sanitize(get_setting('footer_text','© '.date('Y').' School ERP')) ?>
    &nbsp;·&nbsp; v<?= APP_VERSION ?>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= ASSETS_URL ?>/js/main.js"></script>
<?php if (!empty($extra_scripts)) echo $extra_scripts; ?>
<script>
// Sidebar toggle (mobile)
const sidebar   = document.getElementById('appSidebar');
const overlay   = document.getElementById('sidebarOverlay');
const hamburger = document.getElementById('hamburgerBtn');

function openSidebar() {
  sidebar.classList.add('open');
  overlay.classList.add('open');
  if (hamburger) {
    hamburger.querySelector('i').className = 'bi bi-x fs-5';
  }
}
function closeSidebar() {
  sidebar.classList.remove('open');
  overlay.classList.remove('open');
  if (hamburger) {
    hamburger.querySelector('i').className = 'bi bi-list fs-5';
  }
}
if (hamburger) {
  hamburger.addEventListener('click', () => {
    sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
  });
}
if (overlay) {
  overlay.addEventListener('click', closeSidebar);
}
// Close sidebar when a nav link is clicked on mobile
if (sidebar) {
  sidebar.querySelectorAll('.sidebar-link').forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth < 992) closeSidebar();
    });
  });
}
// Auto-dismiss flash
document.querySelectorAll('.alert.alert-success,.alert.alert-info').forEach(el => {
  setTimeout(() => { el.style.opacity='0'; el.style.transition='opacity 0.4s'; setTimeout(()=>el.remove(),400); }, 5000);
});
</script>
</body>
</html>
