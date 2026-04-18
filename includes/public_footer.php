<?php
$site_name   = get_setting('site_name','School ERP');
$footer_text = get_setting('footer_text','© '.date('Y').' School ERP');
$phone = get_setting('contact_phone',''); $email = get_setting('contact_email','');
$fb = get_setting('social_facebook',''); $ig = get_setting('social_instagram','');
$site_logo = get_setting('site_logo','');
?>
<footer style="background:#060a12;border-top:1px solid rgba(245,166,35,0.1);padding:50px 0 24px;">
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-lg-4">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
          <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#f5a623,#c47f0a);display:flex;align-items:center;justify-content:center;">🎓</div>
          <span style="font-family:'Playfair Display',serif;font-weight:700;color:#fff;"><?= htmlspecialchars($site_name) ?></span>
        </div>
        <p style="color:rgba(255,255,255,0.3);font-size:0.82rem;"><?= htmlspecialchars(get_setting('site_tagline','Empowering Education')) ?></p>
        <div style="display:flex;gap:8px;margin-top:14px;">
          <?php if ($fb): ?><a href="<?= htmlspecialchars($fb) ?>" target="_blank" style="width:34px;height:34px;border-radius:8px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none;"><i class="bi bi-facebook"></i></a><?php endif; ?>
          <?php if ($ig): ?><a href="<?= htmlspecialchars($ig) ?>" target="_blank" style="width:34px;height:34px;border-radius:8px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none;"><i class="bi bi-instagram"></i></a><?php endif; ?>
        </div>
      </div>
      <div class="col-lg-2 col-6">
        <h6 style="color:#fff;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:14px;">Links</h6>
        <?php foreach (['Home'=>'/','About'=>'/public/about.php','Gallery'=>'/public/gallery-page.php','Notices'=>'/public/notices-page.php','Contact'=>'/public/contact.php'] as $l=>$u): ?>
        <a href="<?= SITE_URL.$u ?>" style="display:block;color:rgba(255,255,255,0.35);font-size:0.82rem;margin-bottom:7px;text-decoration:none;"><?= $l ?></a>
        <?php endforeach; ?>
      </div>
      <div class="col-lg-2 col-6">
        <h6 style="color:#fff;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:14px;">Portals</h6>
        <?php foreach (['Student Login'=>'/auth/login.php','Teacher Login'=>'/auth/login.php','Parent Login'=>'/auth/login.php','Apply Now'=>'/public/admission.php','Track Status'=>'/public/admission-status.php'] as $l=>$u): ?>
        <a href="<?= SITE_URL.$u ?>" style="display:block;color:rgba(255,255,255,0.35);font-size:0.82rem;margin-bottom:7px;text-decoration:none;"><?= $l ?></a>
        <?php endforeach; ?>
      </div>
      <div class="col-lg-4">
        <h6 style="color:#fff;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:14px;">Contact</h6>
        <?php if ($phone): ?><p style="color:rgba(255,255,255,0.35);font-size:0.82rem;margin-bottom:6px;"><i class="bi bi-telephone me-2" style="color:#f5a623;"></i><?= htmlspecialchars($phone) ?></p><?php endif; ?>
        <?php if ($email): ?><p style="color:rgba(255,255,255,0.35);font-size:0.82rem;"><i class="bi bi-envelope me-2" style="color:#f5a623;"></i><?= htmlspecialchars($email) ?></p><?php endif; ?>
      </div>
    </div>
    <div style="border-top:1px solid rgba(245,166,35,0.08);padding-top:20px;text-align:center;">
      <p style="color:rgba(255,255,255,0.18);font-size:0.75rem;margin:0;"><?= htmlspecialchars($footer_text) ?></p>
    </div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.addEventListener('scroll',()=>document.getElementById('pubNav')?.classList.toggle('scrolled',scrollY>60));</script>
<?php if (!empty($extra_footer)) echo $extra_footer; ?>
</body></html>
