<?php
require_once dirname(__DIR__).'/config/config.php';
$page_title='About Us'; $active_nav='about';
$about_img=get_setting('lp_about_image',''); $mission=get_setting('about_mission','To provide exceptional education.');
$vision=get_setting('about_vision','To be a leading institution.'); $about_text=get_setting('lp_about_text','We are committed to excellence.');
include INCLUDES_PATH.'public_header.php';
?>
<div class="page-hero text-center text-white"><div class="container"><div style="display:inline-block;background:rgba(255,255,255,0.1);border-radius:50px;padding:6px 20px;font-size:0.75rem;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:16px;color:rgba(255,255,255,0.7);">About Us</div><h1 style="font-family:'Playfair Display',serif;font-size:clamp(2rem,4vw,3rem);font-weight:800;margin-bottom:12px;">About Our School</h1><p style="color:rgba(255,255,255,0.6);max-width:520px;margin:0 auto;">Empowering students with knowledge, values, and skills.</p></div></div>
<section class="py-5" style="background:#0f1729;"><div class="container">
  <div class="row align-items-center g-5">
    <div class="col-lg-5"><?php if($about_img):?><img src="<?=UPLOADS_URL.'/'.htmlspecialchars($about_img,ENT_QUOTES)?>" class="img-fluid" style="border-radius:20px;width:100%;height:420px;object-fit:cover;"><?php else:?><div style="border-radius:20px;height:420px;background:linear-gradient(135deg,#131d35,#1a2545);display:flex;align-items:center;justify-content:center;"><i class="bi bi-building" style="font-size:5rem;color:rgba(245,166,35,0.2);"></i></div><?php endif;?></div>
    <div class="col-lg-7">
      <div style="display:inline-block;background:rgba(245,166,35,0.1);border:1px solid rgba(245,166,35,0.25);border-radius:99px;padding:4px 16px;font-size:0.7rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#f5a623;margin-bottom:14px;">Our Story</div>
      <h2 style="font-family:'Playfair Display',serif;font-size:clamp(1.8rem,3vw,2.6rem);font-weight:800;color:#fff;margin-bottom:16px;"><?=htmlspecialchars(get_setting('lp_about_title','About Our School'),ENT_QUOTES)?></h2>
      <p style="color:rgba(255,255,255,0.55);font-size:1rem;line-height:1.9;margin-bottom:24px;"><?=nl2br(htmlspecialchars($about_text,ENT_QUOTES))?></p>
      <div class="d-flex gap-3 flex-wrap">
        <a href="<?=SITE_URL?>/public/admission.php" style="padding:11px 24px;background:linear-gradient(135deg,#f5a623,#c47f0a);color:#0a0f1e;font-weight:700;border-radius:10px;text-decoration:none;font-size:0.9rem;"><i class="bi bi-pencil-square me-1"></i>Apply Now</a>
        <a href="#contact" style="padding:11px 24px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.15);color:#fff;font-weight:600;border-radius:10px;text-decoration:none;font-size:0.9rem;">Contact Us</a>
      </div>
    </div>
  </div>
</div></section>
<section class="py-5" style="background:#131d35;"><div class="container">
  <div class="text-center mb-5"><div style="display:inline-block;background:rgba(245,166,35,0.1);border:1px solid rgba(245,166,35,0.25);border-radius:99px;padding:4px 16px;font-size:0.7rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#f5a623;margin-bottom:14px;">Our Purpose</div><h2 style="font-family:'Playfair Display',serif;font-size:2.2rem;color:#fff;">Mission &amp; Vision</h2></div>
  <div class="row g-4">
    <div class="col-md-6"><div style="background:linear-gradient(135deg,#0d6efd,#0056b3);border-radius:18px;padding:32px;color:#fff;height:100%;"><i class="bi bi-bullseye" style="font-size:2rem;display:block;margin-bottom:16px;"></i><h3 style="font-family:'Playfair Display',serif;margin-bottom:12px;">Our Mission</h3><p style="opacity:0.85;line-height:1.8;margin:0;"><?=nl2br(htmlspecialchars($mission,ENT_QUOTES))?></p></div></div>
    <div class="col-md-6"><div style="background:linear-gradient(135deg,#22c55e,#16a34a);border-radius:18px;padding:32px;color:#fff;height:100%;"><i class="bi bi-eye" style="font-size:2rem;display:block;margin-bottom:16px;"></i><h3 style="font-family:'Playfair Display',serif;margin-bottom:12px;">Our Vision</h3><p style="opacity:0.85;line-height:1.8;margin:0;"><?=nl2br(htmlspecialchars($vision,ENT_QUOTES))?></p></div></div>
  </div>
</div></section>
<section id="contact" class="py-5" style="background:#0a0f1e;"><div class="container text-center text-white">
  <h2 style="font-family:'Playfair Display',serif;margin-bottom:8px;">Become Part of Our Family</h2>
  <p style="color:rgba(255,255,255,0.5);margin-bottom:24px;">Admissions for the new academic session are now open.</p>
  <a href="<?=SITE_URL?>/public/admission.php" style="padding:13px 32px;background:linear-gradient(135deg,#f5a623,#c47f0a);color:#0a0f1e;font-weight:700;border-radius:12px;text-decoration:none;margin-right:10px;"><i class="bi bi-pencil-square me-2"></i>Apply Now</a>
</div></section>
<?php include INCLUDES_PATH.'public_footer.php';?>
