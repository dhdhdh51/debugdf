  </div><!-- /.container-fluid -->
</main><!-- /.main-content -->

<footer class="app-footer" style="margin-left:0;">
  <div style="font-size:0.76rem;color:rgba(255,255,255,0.2);">
    <?= sanitize(get_setting('footer_text','© '.date('Y').' School ERP')) ?>
    &nbsp;·&nbsp; v<?= APP_VERSION ?>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= ASSETS_URL ?>/js/main.js"></script>
<?php if (!empty($extra_scripts)) echo $extra_scripts; ?>
<script>
// Sidebar toggle (mobile)
const sidebar  = document.getElementById('appSidebar');
const overlay  = document.getElementById('sidebarOverlay');
const hamburger= document.getElementById('hamburgerBtn');
if (hamburger) {
  hamburger.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('open');
  });
}
if (overlay) {
  overlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    overlay.classList.remove('open');
  });
}
// Auto-dismiss flash
document.querySelectorAll('.alert.alert-success,.alert.alert-info').forEach(el => {
  setTimeout(() => { el.style.opacity='0'; el.style.transition='opacity 0.4s'; setTimeout(()=>el.remove(),400); }, 5000);
});
</script>
</body>
</html>
