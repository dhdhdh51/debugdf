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
// Auto-dismiss flash
document.querySelectorAll('.alert.alert-success,.alert.alert-info').forEach(el => {
  setTimeout(() => { el.style.transition='opacity 0.4s'; el.style.opacity='0'; setTimeout(()=>el.remove(),400); }, 5000);
});
</script>
</body>
</html>
