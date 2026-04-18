<?php
/* public_header.php - included by public pages (about, gallery, notices, contact) */
$site_name  = get_setting('site_name', 'School ERP');
$site_logo  = get_setting('site_logo', '');
$meta_desc  = $meta_desc  ?? get_setting('meta_description', 'Quality education for every student.');
$page_title = $page_title ?? $site_name;
$full_title = ($page_title !== $site_name) ? "$page_title — $site_name" : $site_name;
$primary    = get_setting('lp_primary_color', '#f5a623');
$active_nav = $active_nav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= htmlspecialchars($full_title, ENT_QUOTES) ?></title>
  <meta name="description" content="<?= htmlspecialchars($meta_desc, ENT_QUOTES) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= ASSETS_URL ?>/css/style.css" rel="stylesheet">
  <style>
    body { background: #0a0f1e; color: #e8eaf6; }
    .page-hero { padding: 140px 0 80px; background: linear-gradient(135deg,#070c1a,#0d1a3a); }
    .page-hero h1 { font-family:'Playfair Display',serif; font-weight:800; color:#fff; }
  </style>
  <?php if (!empty($extra_head)) echo $extra_head; ?>
</head>
<body>
<nav id="pubNav" class="pub-nav" style="background:transparent;">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between">
      <a href="<?= SITE_URL ?>/" class="d-flex align-items-center gap-2 text-decoration-none">
        <?php if ($site_logo): ?>
          <img src="<?= UPLOADS_URL.'/'.htmlspecialchars($site_logo,ENT_QUOTES) ?>" height="36" class="rounded-2">
        <?php else: ?>
          <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#f5a623,#c47f0a);display:flex;align-items:center;justify-content:center;">🎓</div>
        <?php endif; ?>
        <span style="font-family:'Playfair Display',serif;font-weight:700;color:#fff;"><?= htmlspecialchars($site_name,ENT_QUOTES) ?></span>
      </a>
      <button class="d-lg-none" onclick="document.getElementById('pubMob').style.display=document.getElementById('pubMob').style.display==='none'?'block':'none'" style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);border-radius:10px;width:40px;height:40px;color:#fff;font-size:1.2rem;display:flex;align-items:center;justify-content:center;">☰</button>
      <div class="d-none d-lg-flex align-items-center gap-1">
        <?php foreach (['home'=>['Home','/'],'about'=>['About','/public/about.php'],'gallery'=>['Gallery','/public/gallery-page.php'],'notices'=>['Notices','/public/notices-page.php'],'contact'=>['Contact','/public/contact.php']] as $k=>[$lbl,$url]): ?>
        <a href="<?= SITE_URL.$url ?>" class="pub-nav-link <?= $active_nav===$k?'active':'' ?>"><?= $lbl ?></a>
        <?php endforeach; ?>
        <a href="<?= SITE_URL ?>/auth/login.php" style="margin-left:8px;padding:8px 18px;border:1px solid rgba(255,255,255,0.2);border-radius:10px;color:#fff;font-size:0.85rem;font-weight:600;text-decoration:none;">Login</a>
        <a href="<?= SITE_URL ?>/public/admission.php" style="margin-left:6px;padding:9px 20px;background:linear-gradient(135deg,#f5a623,#c47f0a);color:#0a0f1e;font-size:0.85rem;font-weight:700;border-radius:10px;text-decoration:none;">Apply Now</a>
      </div>
    </div>
    <div id="pubMob" style="display:none;padding:12px 0;border-top:1px solid rgba(255,255,255,0.08);margin-top:10px;">
      <?php foreach (['Home'=>'/','About'=>'/public/about.php','Gallery'=>'/public/gallery-page.php','Notices'=>'/public/notices-page.php','Contact'=>'/public/contact.php','Admission'=>'/public/admission.php','Login'=>'/auth/login.php'] as $lbl=>$url): ?>
      <a href="<?= SITE_URL.$url ?>" style="display:block;padding:8px 0;color:rgba(255,255,255,0.7);font-size:0.9rem;text-decoration:none;"><?= $lbl ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</nav>
