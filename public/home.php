<?php
/**
 * public/home.php — Ultra Premium Public Landing Page
 * Dark navy + gold theme, Playfair Display + DM Sans
 */
require_once dirname(__DIR__) . '/config/config.php';

$rows = $pdo->query(
    "SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'lp_%'
     OR setting_key IN ('site_name','site_tagline','site_logo','contact_email','contact_phone','contact_address','footer_text')"
)->fetchAll();
$s = [];
foreach ($rows as $r) { $s[$r['setting_key']] = $r['setting_value']; }
function lp(array $s, string $k, string $d = ''): string {
    return htmlspecialchars($s[$k] ?? $d, ENT_QUOTES, 'UTF-8');
}

$gallery      = ($s['lp_show_gallery']      ?? '1') === '1' ? $pdo->query("SELECT * FROM gallery WHERE is_active=1 ORDER BY sort_order,id LIMIT 12")->fetchAll() : [];
$notices      = ($s['lp_show_notices']      ?? '1') === '1' ? $pdo->query("SELECT * FROM notices WHERE is_active=1 ORDER BY created_at DESC LIMIT 5")->fetchAll()   : [];
$testimonials = ($s['lp_show_testimonials'] ?? '1') === '1' ? $pdo->query("SELECT * FROM testimonials WHERE is_active=1 ORDER BY sort_order,id LIMIT 6")->fetchAll() : [];

$site_name = lp($s, 'site_name', 'School ERP');
$hero_img  = !empty($s['lp_hero_image'])  ? UPLOADS_URL . '/' . htmlspecialchars($s['lp_hero_image'],  ENT_QUOTES) : '';
$about_img = !empty($s['lp_about_image']) ? UPLOADS_URL . '/' . htmlspecialchars($s['lp_about_image'], ENT_QUOTES) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $site_name ?> — <?= lp($s,'site_tagline','Excellence in Education') ?></title>
  <meta name="description" content="<?= lp($s,'lp_hero_subtitle') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= ASSETS_URL ?>/css/style.css" rel="stylesheet">
  <style>
    :root {
      --navy: #0a0f1e; --navy-2: #0f1729; --navy-3: #131d35;
      --gold: #f5a623; --gold-light: #ffc857; --gold-dark: #c47f0a;
      --accent: #4f8ef7; --text-muted: #8892b0; --border: rgba(255,255,255,0.08);
    }
    body { background: var(--navy); color: #e8eaf6; font-family:'DM Sans',sans-serif; overflow-x:hidden; }
    /* ── Hero ── */
    .hero {
      min-height: 100vh;
      display: flex; align-items: center;
      position: relative; overflow: hidden;
      background: linear-gradient(135deg, #070c1a 0%, #0d1a3a 60%, #0a2060 100%);
    }
    .hero-bg { position:absolute;inset:0;background-size:cover;background-position:center; }
    .hero-overlay { position:absolute;inset:0;background:linear-gradient(135deg,rgba(7,12,26,0.88),rgba(10,32,96,0.6)); }
    .hero-content { position:relative;z-index:2; }
    .hero-orb {
      position:absolute; border-radius:50%; filter:blur(80px); pointer-events:none;
    }
    .orb1 { width:500px;height:500px;background:radial-gradient(circle,rgba(245,166,35,0.12),transparent 70%);top:-150px;right:-100px; }
    .orb2 { width:400px;height:400px;background:radial-gradient(circle,rgba(79,142,247,0.1),transparent 70%);bottom:-100px;left:-80px; }
    .hero-badge {
      display:inline-flex;align-items:center;gap:8px;
      background:rgba(245,166,35,0.1);border:1px solid rgba(245,166,35,0.3);
      border-radius:99px;padding:6px 18px;font-size:0.75rem;font-weight:700;
      letter-spacing:0.1em;text-transform:uppercase;color:var(--gold);
      margin-bottom:20px;
    }
    .hero-title {
      font-family:'Playfair Display',serif;
      font-size:clamp(2.4rem,5vw,4.2rem);
      font-weight:800;line-height:1.12;color:#fff;
      margin-bottom:20px;
    }
    .hero-title .accent { color:var(--gold); position:relative; }
    .hero-title .accent::after {
      content:'';position:absolute;bottom:-4px;left:0;right:0;height:3px;
      background:linear-gradient(90deg,var(--gold),transparent);border-radius:2px;
    }
    .hero-sub { font-size:1.1rem;color:rgba(255,255,255,0.65);max-width:520px;line-height:1.8;margin-bottom:36px; }
    .hero-btn-group { display:flex;flex-wrap:wrap;gap:14px;margin-bottom:48px; }
    .btn-gold {
      background:linear-gradient(135deg,var(--gold),var(--gold-dark));
      color:#0a0f1e;font-weight:700;border-radius:12px;padding:13px 28px;
      font-size:0.95rem;border:none;transition:all 0.25s;display:inline-flex;align-items:center;gap:8px;
    }
    .btn-gold:hover { transform:translateY(-2px);box-shadow:0 8px 30px rgba(245,166,35,0.4);color:#0a0f1e; }
    .btn-ghost {
      background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.15);
      color:rgba(255,255,255,0.85);font-weight:600;border-radius:12px;padding:13px 28px;
      font-size:0.95rem;transition:all 0.25s;display:inline-flex;align-items:center;gap:8px;
    }
    .btn-ghost:hover { background:rgba(255,255,255,0.12);color:#fff;border-color:rgba(255,255,255,0.3); }
    /* Stats grid */
    .stat-grid { display:grid;grid-template-columns:repeat(2,1fr);gap:12px;max-width:340px; }
    .stat-tile {
      background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);
      border-radius:14px;padding:18px;
      animation:fadeInUp 0.6s ease both;
    }
    .stat-tile:nth-child(1){animation-delay:0.1s}
    .stat-tile:nth-child(2){animation-delay:0.2s}
    .stat-tile:nth-child(3){animation-delay:0.3s}
    .stat-tile:nth-child(4){animation-delay:0.4s}
    .stat-tile .num { font-family:'Playfair Display',serif;font-size:1.9rem;font-weight:800;color:#fff; }
    .stat-tile .lbl { font-size:0.72rem;color:rgba(255,255,255,0.5);margin-top:2px; }
    .stat-tile .ico { font-size:1.2rem;margin-bottom:8px; }
    /* Section */
    .pub-section { padding:90px 0; }
    .section-label {
      display:inline-block;background:rgba(245,166,35,0.1);border:1px solid rgba(245,166,35,0.25);
      border-radius:99px;padding:4px 16px;font-size:0.7rem;font-weight:700;
      letter-spacing:0.12em;text-transform:uppercase;color:var(--gold);margin-bottom:14px;
    }
    .section-title { font-family:'Playfair Display',serif;font-size:clamp(1.8rem,3vw,2.8rem);font-weight:800;color:#fff;line-height:1.2; }
    .section-line { width:48px;height:3px;background:linear-gradient(90deg,var(--gold),transparent);border-radius:2px;margin:14px 0; }
    /* Feature card */
    .feat-card {
      background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);
      border-radius:18px;padding:28px;transition:all 0.3s;height:100%;
    }
    .feat-card:hover { background:rgba(245,166,35,0.05);border-color:rgba(245,166,35,0.2);transform:translateY(-4px); }
    .feat-icon {
      width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;
      font-size:1.3rem;margin-bottom:16px;
    }
    /* Gallery */
    .gal-item {
      border-radius:14px;overflow:hidden;position:relative;cursor:pointer;
      aspect-ratio:1;background:var(--navy-3);
    }
    .gal-item img { width:100%;height:100%;object-fit:cover;transition:transform 0.4s; }
    .gal-item:hover img { transform:scale(1.08); }
    .gal-overlay {
      position:absolute;inset:0;background:rgba(245,166,35,0.7);display:flex;
      align-items:center;justify-content:center;opacity:0;transition:opacity 0.3s;
      color:var(--navy);font-size:2rem;
    }
    .gal-item:hover .gal-overlay { opacity:1; }
    /* Notice */
    .notice-item {
      padding:16px 20px;border-radius:12px;
      background:rgba(255,255,255,0.03);border-left:3px solid var(--gold);
      border-bottom:1px solid var(--border);transition:all 0.2s;margin-bottom:10px;
    }
    .notice-item:hover { background:rgba(245,166,35,0.05);transform:translateX(4px); }
    /* Testimonial */
    .test-card {
      background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);
      border-radius:18px;padding:28px;height:100%;
      border-top:3px solid var(--gold);transition:all 0.3s;
    }
    .test-card:hover { background:rgba(245,166,35,0.05);transform:translateY(-4px); }
    /* CTA */
    .cta-section {
      background:linear-gradient(135deg,#0d1a3a,#0a2060);
      border-top:1px solid rgba(245,166,35,0.15);
      border-bottom:1px solid rgba(245,166,35,0.15);
      padding:80px 0;position:relative;overflow:hidden;
    }
    .cta-section::before {
      content:'';position:absolute;width:600px;height:600px;border-radius:50%;
      background:radial-gradient(circle,rgba(245,166,35,0.07),transparent 70%);
      top:-200px;right:-200px;
    }
    /* Footer */
    .site-footer { background:#060a12;border-top:1px solid rgba(245,166,35,0.1);padding:60px 0 28px; }
    .footer-link { color:rgba(255,255,255,0.45);font-size:0.85rem;transition:color 0.2s;display:block;margin-bottom:8px; }
    .footer-link:hover { color:var(--gold); }
    /* Lightbox */
    #lightbox { display:none;position:fixed;inset:0;background:rgba(0,0,0,0.95);z-index:9999;align-items:center;justify-content:center;flex-direction:column; }
    #lightbox.open { display:flex; }
    #lb-close { position:absolute;top:24px;right:32px;color:#fff;font-size:2rem;cursor:pointer;opacity:0.6;transition:opacity 0.2s; }
    #lb-close:hover { opacity:1; }
    #lb-img { max-width:90vw;max-height:85vh;border-radius:12px;object-fit:contain; }
    /* Scroll */
    @keyframes fadeInUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
    @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-12px)} }
    .scroll-hint { position:absolute;bottom:32px;left:50%;transform:translateX(-50%);text-align:center;z-index:2; }
    .scroll-hint i { color:rgba(255,255,255,0.3);font-size:1.4rem;animation:float 2s ease-in-out infinite; }
    @media(max-width:767px) { .stat-grid{grid-template-columns:repeat(2,1fr);max-width:100%;} }
  </style>
</head>
<body>

<!-- ════ NAV ════ -->
<nav id="pubNav" class="pub-nav" style="background:transparent;">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between">
      <!-- Brand -->
      <a href="<?= SITE_URL ?>/" class="d-flex align-items-center gap-2 text-decoration-none">
        <?php if (!empty($s['site_logo'])): ?>
          <img src="<?= UPLOADS_URL.'/'.htmlspecialchars($s['site_logo'],ENT_QUOTES) ?>" height="38" class="rounded-2">
        <?php else: ?>
          <div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,var(--gold),var(--gold-dark));display:flex;align-items:center;justify-content:center;font-size:1.1rem;">🎓</div>
        <?php endif; ?>
        <span style="font-family:'Playfair Display',serif;font-weight:700;color:#fff;font-size:1.1rem;"><?= $site_name ?></span>
      </a>
      <!-- Desktop nav -->
      <div class="d-none d-lg-flex align-items-center gap-1">
        <?php foreach (['#about'=>'About','#features'=>'Why Us','#gallery'=>'Gallery','#notices'=>'Notices','#contact'=>'Contact'] as $href => $label): ?>
        <a href="<?= $href ?>" class="pub-nav-link"><?= $label ?></a>
        <?php endforeach; ?>
        <a href="<?= SITE_URL ?>/auth/login.php"
           style="margin-left:8px;padding:8px 18px;border:1px solid rgba(255,255,255,0.2);border-radius:10px;color:#fff;font-size:0.85rem;font-weight:600;transition:all 0.2s;"
           onmouseover="this.style.background='rgba(255,255,255,0.08)'"
           onmouseout="this.style.background='transparent'">Login</a>
        <a href="<?= SITE_URL ?>/public/admission.php" class="btn-gold ms-2" style="padding:9px 20px;font-size:0.85rem;border-radius:10px;">
          <i class="bi bi-pencil-square"></i> Apply Now
        </a>
      </div>
      <!-- Mobile hamburger -->
      <button class="d-lg-none" onclick="toggleMobileNav()"
              style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);border-radius:10px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;">
        <i class="bi bi-list" id="burgerIcon"></i>
      </button>
    </div>
    <!-- Mobile menu -->
    <div id="mobileNav" style="display:none;padding:16px 0 8px;border-top:1px solid rgba(255,255,255,0.08);margin-top:12px;">
      <?php foreach (['#about'=>'About','#features'=>'Why Us','#gallery'=>'Gallery','#notices'=>'Notices','#contact'=>'Contact'] as $href => $label): ?>
      <a href="<?= $href ?>" class="d-block py-2" style="color:rgba(255,255,255,0.7);font-size:0.9rem;" onclick="toggleMobileNav()"><?= $label ?></a>
      <?php endforeach; ?>
      <div class="d-flex gap-2 mt-3">
        <a href="<?= SITE_URL ?>/auth/login.php" class="btn-ghost" style="padding:9px 20px;font-size:0.85rem;border-radius:10px;text-decoration:none;">Login</a>
        <a href="<?= SITE_URL ?>/public/admission.php" class="btn-gold" style="padding:9px 20px;font-size:0.85rem;border-radius:10px;text-decoration:none;">Apply Now</a>
      </div>
    </div>
  </div>
</nav>

<!-- ════ HERO ════ -->
<section class="hero">
  <?php if ($hero_img): ?>
  <div class="hero-bg" style="background-image:url('<?= $hero_img ?>')"></div>
  <?php endif; ?>
  <div class="hero-overlay"></div>
  <div class="hero-orb orb1"></div>
  <div class="hero-orb orb2"></div>

  <div class="container hero-content py-5">
    <div class="row align-items-center g-5 py-5">
      <div class="col-lg-6" style="animation:fadeInUp 0.7s ease both;">
        <div class="hero-badge">
          <i class="bi bi-stars"></i>
          <?= lp($s,'site_tagline','Excellence in Education') ?>
        </div>
        <h1 class="hero-title">
          <?php
          $title = $s['lp_hero_title'] ?? 'Welcome to Our School';
          $words = explode(' ', $title);
          $last  = array_pop($words);
          echo htmlspecialchars(implode(' ', $words), ENT_QUOTES) . ' <span class="accent">' . htmlspecialchars($last, ENT_QUOTES) . '</span>';
          ?>
        </h1>
        <p class="hero-sub">
          <?= lp($s,'lp_hero_subtitle','Empowering students with knowledge, values, and skills for a brighter future.') ?>
        </p>
        <div class="hero-btn-group">
          <a href="<?= SITE_URL ?>/public/admission.php" class="btn-gold">
            <i class="bi bi-pencil-square"></i><?= lp($s,'lp_hero_btn1_text','Apply for Admission') ?>
          </a>
          <a href="<?= SITE_URL ?>/public/admission-status.php" class="btn-ghost">
            <i class="bi bi-search"></i><?= lp($s,'lp_hero_btn2_text','Track Application') ?>
          </a>
        </div>
        <!-- Portal links -->
        <div style="display:flex;flex-wrap:wrap;gap:10px;">
          <?php foreach (['student'=>['Person Circle','bi-person-circle'],'teacher'=>['Teacher','bi-person-badge'],'parent'=>['Parent','bi-people']] as $r=>[$l,$i]): ?>
          <a href="<?= SITE_URL ?>/auth/login.php"
             style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:8px;padding:6px 14px;color:rgba(255,255,255,0.65);font-size:0.78rem;text-decoration:none;transition:all 0.2s;"
             onmouseover="this.style.background='rgba(255,255,255,0.1)';this.style.color='#fff'"
             onmouseout="this.style.background='rgba(255,255,255,0.06)';this.style.color='rgba(255,255,255,0.65)'">
            <i class="bi <?= $i ?>"></i><?= ucfirst($r) ?> Login
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <?php if (($s['lp_show_stats'] ?? '1') === '1'): ?>
      <div class="col-lg-6 d-flex justify-content-center justify-content-lg-end" style="animation:fadeInUp 0.9s ease both;">
        <div class="stat-grid">
          <?php $statDefs=[
            ['lp_stat_students','1200+','lp_stat_label1','Students','bi-people-fill','var(--gold)'],
            ['lp_stat_teachers','80+','lp_stat_label2','Teachers','bi-person-badge-fill','#4f8ef7'],
            ['lp_stat_years','25+','lp_stat_label3','Years','bi-award-fill','#22c55e'],
            ['lp_stat_success','98%','lp_stat_label4','Pass Rate','bi-graph-up-arrow','#7b5ea7'],
          ];
          foreach ($statDefs as [$nk,$nd,$lk,$ld,$icon,$color]):
          ?>
          <div class="stat-tile">
            <div class="ico"><i class="bi <?= $icon ?>" style="color:<?= $color ?>"></i></div>
            <div class="num"><?= lp($s,$nk,$nd) ?></div>
            <div class="lbl"><?= lp($s,$lk,$ld) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="scroll-hint"><i class="bi bi-chevron-double-down"></i></div>
</section>

<!-- ════ ABOUT ════ -->
<?php if (($s['lp_show_about'] ?? '1') === '1'): ?>
<section id="about" class="pub-section" style="background:var(--navy-2);">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-5">
        <div style="position:relative;">
          <?php if ($about_img): ?>
            <img src="<?= $about_img ?>" class="img-fluid"
                 style="border-radius:20px;width:100%;height:420px;object-fit:cover;">
          <?php else: ?>
            <div style="border-radius:20px;height:420px;background:linear-gradient(135deg,var(--navy-3),var(--navy-4));display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-building" style="font-size:5rem;color:rgba(245,166,35,0.2);"></i>
            </div>
          <?php endif; ?>
          <!-- Floating badge -->
          <div style="position:absolute;bottom:24px;left:-20px;background:linear-gradient(135deg,var(--gold),var(--gold-dark));border-radius:14px;padding:16px 22px;box-shadow:0 8px 30px rgba(245,166,35,0.4);text-align:center;min-width:110px;">
            <div style="font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:800;color:var(--navy);"><?= lp($s,'lp_stat_years','25+') ?></div>
            <div style="font-size:0.72rem;color:rgba(10,15,30,0.75);font-weight:600;">Years of Excellence</div>
          </div>
        </div>
      </div>
      <div class="col-lg-7">
        <div class="section-label">About Us</div>
        <h2 class="section-title"><?= lp($s,'lp_about_title','About Our School') ?></h2>
        <div class="section-line mb-4"></div>
        <p style="color:var(--text-muted);font-size:1.05rem;line-height:1.9;margin-bottom:28px;">
          <?= nl2br(lp($s,'lp_about_text','We are committed to providing a nurturing, inclusive learning environment where every student can discover their potential.')) ?>
        </p>
        <!-- Checkpoints -->
        <div class="row g-3 mb-32">
          <?php foreach (['Experienced Faculty','Modern Facilities','Holistic Curriculum','Safe Campus'] as $pt): ?>
          <div class="col-6">
            <div style="display:flex;align-items:center;gap:8px;font-size:0.875rem;color:#e8eaf6;">
              <i class="bi bi-check-circle-fill" style="color:var(--gold);flex-shrink:0;"></i><?= $pt ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div style="margin-top:24px;display:flex;gap:12px;flex-wrap:wrap;">
          <a href="<?= SITE_URL ?>/public/admission.php" class="btn-gold" style="border-radius:10px;font-size:0.9rem;">
            <i class="bi bi-pencil-square"></i> Apply Now
          </a>
          <a href="#contact" class="btn-ghost" style="border-radius:10px;font-size:0.9rem;text-decoration:none;">
            <i class="bi bi-telephone"></i> Contact Us
          </a>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ════ FEATURES ════ -->
<section id="features" class="pub-section" style="background:var(--navy);">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-label">Why Choose Us</div>
      <h2 class="section-title">What Makes Us Different</h2>
      <div class="section-line mx-auto"></div>
    </div>
    <div class="row g-4">
      <?php foreach ([
        ['bi-award-fill','var(--gold)','Academic Excellence','Consistently outstanding results driven by dedicated faculty and personalised learning.'],
        ['bi-shield-check-fill','#22c55e','Safe & Secure','CCTV-monitored, gated campus ensuring student safety at all times.'],
        ['bi-laptop-fill','#4f8ef7','Smart Classrooms','Digital boards, ERP-powered portals, modern science and computer labs.'],
        ['bi-trophy-fill','#fbbf24','Sports & Arts','Comprehensive sports, music, art, and cultural activity programmes.'],
        ['bi-people-fill','#f87171','Expert Faculty','Qualified, passionate educators committed to every student\'s success.'],
        ['bi-heart-fill','#7b5ea7','Values & Character','Ethics, discipline, and community responsibility at our core.'],
      ] as [$icon,$color,$title,$desc]): ?>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="feat-card">
          <div class="feat-icon" style="background:<?= $color ?>1a;color:<?= $color ?>;">
            <i class="bi <?= $icon ?>"></i>
          </div>
          <h5 style="font-family:'Playfair Display',serif;color:#fff;margin-bottom:8px;"><?= $title ?></h5>
          <p style="color:var(--text-muted);font-size:0.875rem;margin:0;line-height:1.7;"><?= $desc ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ════ GALLERY ════ -->
<?php if (!empty($gallery)): ?>
<section id="gallery" class="pub-section" style="background:var(--navy-2);">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-label">Our Campus</div>
      <h2 class="section-title">Photo Gallery</h2>
      <div class="section-line mx-auto"></div>
    </div>
    <div class="row g-3">
      <?php foreach ($gallery as $img): ?>
      <div class="col-6 col-md-4 col-lg-3">
        <div class="gal-item" onclick="openLightbox('<?= UPLOADS_URL.'/'.htmlspecialchars($img['image'],ENT_QUOTES) ?>', '<?= htmlspecialchars($img['title']??'',ENT_QUOTES) ?>')">
          <img src="<?= UPLOADS_URL.'/'.htmlspecialchars($img['image'],ENT_QUOTES) ?>"
               alt="<?= htmlspecialchars($img['title']??'Gallery',ENT_QUOTES) ?>" loading="lazy">
          <div class="gal-overlay"><i class="bi bi-zoom-in"></i></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ════ NOTICES ════ -->
<?php if (!empty($notices)): ?>
<section id="notices" class="pub-section" style="background:var(--navy);">
  <div class="container">
    <div class="row g-5 align-items-start">
      <div class="col-lg-4">
        <div class="section-label">Latest Updates</div>
        <h2 class="section-title">Notices &amp; Events</h2>
        <div class="section-line mb-4"></div>
        <p style="color:var(--text-muted);font-size:0.9rem;line-height:1.8;">
          Stay up-to-date with the latest school announcements, exam schedules, events, and holiday notices.
        </p>
        <a href="<?= SITE_URL ?>/public/admission.php" class="btn-gold mt-3 d-inline-flex" style="border-radius:10px;font-size:0.875rem;text-decoration:none;">
          <i class="bi bi-pencil-square"></i>Apply for Admission
        </a>
      </div>
      <div class="col-lg-8">
        <?php $ncols=['general'=>'var(--gold)','exam'=>'#fbbf24','event'=>'#22c55e','holiday'=>'#ef4444','admission'=>'#4f8ef7'];
        foreach ($notices as $n):
          $ncol = $ncols[$n['category']] ?? 'var(--gold)';
        ?>
        <div class="notice-item" style="border-left-color:<?= $ncol ?>;">
          <div style="display:flex;align-items:flex-start;gap:12px;">
            <div style="flex:1;">
              <span style="font-size:0.68rem;text-transform:uppercase;letter-spacing:0.1em;color:<?= $ncol ?>;font-weight:700;"><?= htmlspecialchars($n['category']) ?></span>
              <h6 style="color:#fff;font-weight:600;margin:4px 0 4px;font-size:0.95rem;"><?= htmlspecialchars($n['title']) ?></h6>
              <?php if ($n['body']): ?>
              <p style="color:var(--text-muted);font-size:0.82rem;margin:0;"><?= htmlspecialchars(mb_substr($n['body'],0,100)) ?><?= mb_strlen($n['body'])>100?'…':'' ?></p>
              <?php endif; ?>
            </div>
            <span style="font-size:0.75rem;color:var(--text-muted);white-space:nowrap;"><?= date('d M',strtotime($n['created_at'])) ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ════ CLASSES ════ -->
<section class="pub-section" style="background:var(--navy-2);">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-label">Academic Programmes</div>
      <h2 class="section-title">Classes We Offer</h2>
      <div class="section-line mx-auto"></div>
    </div>
    <div class="row g-4">
      <?php foreach ([
        ['Primary','Class 1–5',range(1,5),'var(--gold)','bi-book-half'],
        ['Middle','Class 6–8',range(6,8),'#4f8ef7','bi-journal-text'],
        ['Secondary','Class 9–10',range(9,10),'#22c55e','bi-mortarboard'],
        ['Senior','Class 11–12',range(11,12),'#7b5ea7','bi-award'],
      ] as [$group,$range,$classes,$col,$icon]): ?>
      <div class="col-12 col-md-6 col-lg-3">
        <div class="feat-card" style="border-top:3px solid <?= $col ?>;">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
            <i class="bi <?= $icon ?>" style="font-size:1.4rem;color:<?= $col ?>;"></i>
            <div>
              <div style="font-family:'Playfair Display',serif;font-weight:700;color:#fff;font-size:1.1rem;"><?= $group ?></div>
              <div style="font-size:0.72rem;color:var(--text-muted);"><?= $range ?></div>
            </div>
          </div>
          <div style="display:flex;flex-wrap:wrap;gap:6px;">
            <?php foreach ($classes as $c): ?>
            <span style="background:<?= $c ?>1a;color:<?= $col ?>;border:1px solid <?= $col ?>3a;border-radius:6px;padding:3px 10px;font-size:0.75rem;font-weight:600;">
              Class <?= $c ?>
            </span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
      <a href="<?= SITE_URL ?>/public/admission.php" class="btn-gold" style="border-radius:12px;font-size:1rem;padding:13px 32px;">
        <i class="bi bi-pencil-square"></i> Apply for Admission
      </a>
    </div>
  </div>
</section>

<!-- ════ TESTIMONIALS ════ -->
<?php if (!empty($testimonials)): ?>
<section class="pub-section" style="background:var(--navy);">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-label">What They Say</div>
      <h2 class="section-title">Student &amp; Parent Testimonials</h2>
      <div class="section-line mx-auto"></div>
    </div>
    <div class="row g-4">
      <?php foreach ($testimonials as $t): ?>
      <div class="col-12 col-md-6 col-lg-4">
        <div class="test-card">
          <div style="margin-bottom:12px;">
            <?php for ($i=1;$i<=5;$i++): ?>
            <i class="bi bi-star-fill" style="font-size:0.85rem;color:<?= $i<=(int)$t['rating']?'var(--gold)':'rgba(255,255,255,0.15)' ?>;"></i>
            <?php endfor; ?>
          </div>
          <p style="color:rgba(255,255,255,0.65);font-style:italic;font-size:0.9rem;line-height:1.8;margin-bottom:20px;">
            &ldquo;<?= htmlspecialchars($t['message']) ?>&rdquo;
          </p>
          <div style="display:flex;align-items:center;gap:12px;">
            <?php if ($t['photo']): ?>
              <img src="<?= UPLOADS_URL.'/'.htmlspecialchars($t['photo']) ?>" class="rounded-circle" width="44" height="44" style="object-fit:cover;border:2px solid rgba(245,166,35,0.3);">
            <?php else: ?>
              <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold-dark));display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-weight:700;color:var(--navy);flex-shrink:0;"><?= strtoupper(mb_substr($t['name'],0,1)) ?></div>
            <?php endif; ?>
            <div>
              <div style="color:#fff;font-weight:600;font-size:0.875rem;"><?= htmlspecialchars($t['name']) ?></div>
              <div style="color:var(--text-muted);font-size:0.75rem;"><?= htmlspecialchars($t['role']) ?></div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ════ CTA ════ -->
<section class="cta-section">
  <div class="container" style="position:relative;z-index:1;">
    <div class="row align-items-center g-4">
      <div class="col-lg-8">
        <h2 style="font-family:'Playfair Display',serif;font-size:clamp(1.6rem,3vw,2.4rem);color:#fff;margin-bottom:8px;">
          <?= lp($s,'lp_cta_title','Start Your Journey With Us') ?>
        </h2>
        <p style="color:rgba(255,255,255,0.55);margin:0;font-size:1rem;">
          <?= lp($s,'lp_cta_text','Admissions for the new academic session are now open.') ?>
        </p>
      </div>
      <div class="col-lg-4 text-lg-end" style="display:flex;gap:12px;flex-wrap:wrap;justify-content:flex-end;">
        <a href="<?= SITE_URL ?>/public/admission.php" class="btn-gold" style="border-radius:12px;">
          <i class="bi bi-pencil-square"></i> Apply Now
        </a>
        <a href="<?= SITE_URL ?>/public/admission-status.php" class="btn-ghost" style="border-radius:12px;text-decoration:none;">
          <i class="bi bi-search"></i> Track Status
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ════ CONTACT ════ -->
<section id="contact" class="pub-section" style="background:var(--navy-2);">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-label">Get In Touch</div>
      <h2 class="section-title">Contact Us</h2>
      <div class="section-line mx-auto"></div>
    </div>
    <div class="row g-4 justify-content-center">
      <?php foreach ([
        ['bi-geo-alt-fill','var(--gold)','Address', lp($s,'contact_address','123 School Street, City')],
        ['bi-telephone-fill','#22c55e','Phone',  lp($s,'contact_phone','+91 9000000000')],
        ['bi-envelope-fill','#4f8ef7','Email',   lp($s,'contact_email','info@school.com')],
      ] as [$icon,$col,$lbl,$val]): ?>
      <div class="col-12 col-md-4">
        <div class="feat-card text-center">
          <div style="width:56px;height:56px;border-radius:14px;background:<?= $col ?>1a;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <i class="bi <?= $icon ?>" style="font-size:1.4rem;color:<?= $col ?>;"></i>
          </div>
          <h6 style="color:#fff;font-weight:700;margin-bottom:6px;"><?= $lbl ?></h6>
          <p style="color:var(--text-muted);font-size:0.875rem;margin:0;"><?= $val ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ════ FOOTER ════ -->
<footer class="site-footer">
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-lg-4">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
          <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--gold),var(--gold-dark));display:flex;align-items:center;justify-content:center;">🎓</div>
          <span style="font-family:'Playfair Display',serif;font-weight:700;color:#fff;"><?= $site_name ?></span>
        </div>
        <p style="color:rgba(255,255,255,0.35);font-size:0.85rem;line-height:1.7;"><?= lp($s,'site_tagline','Empowering Education') ?></p>
        <p style="color:rgba(255,255,255,0.3);font-size:0.8rem;"><?= lp($s,'contact_address') ?></p>
      </div>
      <div class="col-lg-2 col-6">
        <h6 style="color:#fff;font-size:0.8rem;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:16px;">Quick Links</h6>
        <?php foreach (['Home'=>SITE_URL.'/','About Us'=>SITE_URL.'/public/about.php','Gallery'=>SITE_URL.'/public/gallery-page.php','Notices'=>SITE_URL.'/public/notices-page.php','Contact'=>SITE_URL.'/public/contact.php'] as $lbl=>$url): ?>
        <a href="<?= $url ?>" class="footer-link"><?= $lbl ?></a>
        <?php endforeach; ?>
      </div>
      <div class="col-lg-2 col-6">
        <h6 style="color:#fff;font-size:0.8rem;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:16px;">Portals</h6>
        <a href="<?= SITE_URL ?>/auth/login.php" class="footer-link">Student Login</a>
        <a href="<?= SITE_URL ?>/auth/login.php" class="footer-link">Teacher Login</a>
        <a href="<?= SITE_URL ?>/auth/login.php" class="footer-link">Parent Login</a>
        <a href="<?= SITE_URL ?>/public/admission.php" class="footer-link">Apply Now</a>
        <a href="<?= SITE_URL ?>/public/admission-status.php" class="footer-link">Track Status</a>
      </div>
      <div class="col-lg-4">
        <h6 style="color:#fff;font-size:0.8rem;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:16px;">Contact</h6>
        <p style="color:rgba(255,255,255,0.35);font-size:0.85rem;margin-bottom:6px;"><i class="bi bi-telephone me-2" style="color:var(--gold);"></i><?= lp($s,'contact_phone') ?></p>
        <p style="color:rgba(255,255,255,0.35);font-size:0.85rem;"><i class="bi bi-envelope me-2" style="color:var(--gold);"></i><?= lp($s,'contact_email') ?></p>
      </div>
    </div>
    <div style="border-top:1px solid rgba(245,166,35,0.1);padding-top:24px;text-align:center;">
      <p style="color:rgba(255,255,255,0.2);font-size:0.78rem;margin:0;"><?= lp($s,'footer_text','© '.date('Y').' School ERP. All rights reserved.') ?></p>
    </div>
  </div>
</footer>

<!-- Lightbox -->
<div id="lightbox"><span id="lb-close" onclick="closeLb()">&times;</span><img id="lb-img" src="" alt=""></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Navbar scroll
window.addEventListener('scroll', () => document.getElementById('pubNav').classList.toggle('scrolled', scrollY > 60));
// Mobile nav
function toggleMobileNav() {
  const m = document.getElementById('mobileNav');
  m.style.display = m.style.display === 'none' ? 'block' : 'none';
}
// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(a => a.addEventListener('click', e => {
  const t = document.querySelector(a.getAttribute('href'));
  if (t) { e.preventDefault(); t.scrollIntoView({behavior:'smooth'}); document.getElementById('mobileNav').style.display='none'; }
}));
// Lightbox
function openLightbox(src, cap) {
  document.getElementById('lb-img').src = src;
  document.getElementById('lightbox').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeLb() { document.getElementById('lightbox').classList.remove('open'); document.body.style.overflow=''; }
document.getElementById('lightbox').addEventListener('click', e => { if(e.target===e.currentTarget) closeLb(); });
document.addEventListener('keydown', e => { if(e.key==='Escape') closeLb(); });
</script>
</body>
</html>
