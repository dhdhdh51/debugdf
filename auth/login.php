<?php
/**
 * auth/login.php — Ultra Premium Login
 * Admin login is at /admin/login.php (separate, not shown here)
 * This page serves: student, teacher, parent only
 */
require_once dirname(__DIR__) . '/config/config.php';

if (is_logged_in()) {
    $r = get_user_role();
    redirect(SITE_URL . '/' . ($r === 'admin' ? 'admin' : $r) . '/');
}

$error   = '';
$timeout = isset($_GET['timeout']);
$roles   = ['student' => ['Person Circle', 'bi-person-circle'],
             'teacher' => ['Person Badge',  'bi-person-badge'],
             'parent'  => ['People',        'bi-people']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();
    $email    = sanitize_email($_POST['email']    ?? '');
    $password = $_POST['password']                ?? '';
    $role     = sanitize($_POST['role']           ?? '');

    if (!in_array($role, ['student','teacher','parent'])) {
        $error = 'Invalid role selected.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT * FROM users WHERE email=? AND role=? AND status='active' LIMIT 1"
        );
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['user_name']     = $user['name'];
            $_SESSION['user_email']    = $user['email'];
            $_SESSION['user_role']     = $user['role'];
            $_SESSION['last_activity'] = time();
            $pdo->prepare("UPDATE users SET last_login=NOW() WHERE id=?")->execute([$user['id']]);
            redirect(SITE_URL . '/' . $user['role'] . '/');
        } else {
            $error = 'Invalid credentials or account not active.';
        }
    }
}

$site_name = get_setting('site_name', 'School ERP');
$site_logo = get_setting('site_logo', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — <?= sanitize($site_name) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= ASSETS_URL ?>/css/style.css" rel="stylesheet">
</head>
<body class="auth-page">

<!-- Left decorative panel -->
<div class="auth-left d-none d-lg-flex flex-column align-items-center justify-content-center col-lg-6 p-5">
  <div style="position:relative;z-index:1;text-align:center;max-width:380px;">
    <!-- Floating orbs -->
    <div style="position:absolute;width:200px;height:200px;border-radius:50%;background:radial-gradient(circle,rgba(245,166,35,0.15),transparent 70%);top:-60px;left:-40px;"></div>
    <div style="position:absolute;width:150px;height:150px;border-radius:50%;background:radial-gradient(circle,rgba(79,142,247,0.12),transparent 70%);bottom:-40px;right:-20px;"></div>

    <?php if ($site_logo): ?>
      <img src="<?= get_upload_url($site_logo) ?>" alt="Logo" height="64" class="mb-4"
           style="border-radius:16px;box-shadow:0 8px 32px rgba(245,166,35,0.3);">
    <?php else: ?>
      <div style="width:72px;height:72px;border-radius:20px;background:linear-gradient(135deg,#f5a623,#c47f0a);display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 24px;box-shadow:0 8px 32px rgba(245,166,35,0.35);">🎓</div>
    <?php endif; ?>

    <h1 style="font-family:'Playfair Display',serif;font-size:2.2rem;font-weight:800;color:#fff;margin-bottom:8px;">
      <?= sanitize($site_name) ?>
    </h1>
    <p style="color:rgba(255,255,255,0.6);font-size:1rem;margin-bottom:40px;">
      <?= sanitize(get_setting('site_tagline','Empowering Education Through Excellence')) ?>
    </p>

    <!-- Feature cards -->
    <div class="row g-3">
      <?php foreach ([
        ['bi-people-fill',    '#f5a623', 'Student Portal',  'Grades, fees & attendance'],
        ['bi-person-badge',   '#4f8ef7', 'Teacher Portal',  'Marks, reports & classes'],
        ['bi-shield-lock',    '#22c55e', 'Secure Access',   'End-to-end encrypted'],
        ['bi-graph-up-arrow', '#7b5ea7', 'Live Reports',    'Real-time insights'],
      ] as [$icon, $color, $title, $sub]): ?>
      <div class="col-6">
        <div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);border-radius:14px;padding:16px;text-align:left;">
          <i class="bi <?= $icon ?>" style="font-size:1.4rem;color:<?= $color ?>;display:block;margin-bottom:8px;"></i>
          <div style="font-size:0.85rem;font-weight:600;color:#fff;"><?= $title ?></div>
          <div style="font-size:0.75rem;color:rgba(255,255,255,0.5);"><?= $sub ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Right: Login form -->
<div class="auth-form-wrap col-12 col-lg-6">
  <div class="auth-form-inner">

    <!-- Mobile logo -->
    <div class="d-lg-none text-center mb-4">
      <div class="auth-logo-box">🎓</div>
      <h4 style="font-family:'Playfair Display',serif;color:#fff;"><?= sanitize($site_name) ?></h4>
    </div>

    <h2 style="font-family:'Playfair Display',serif;font-weight:800;color:#fff;margin-bottom:4px;">
      Welcome back
    </h2>
    <p style="color:rgba(255,255,255,0.5);font-size:0.9rem;margin-bottom:28px;">
      Sign in to access your portal
    </p>

    <?php if ($timeout): ?>
    <div class="alert alert-warning mb-3">
      <i class="bi bi-clock me-2"></i>Session expired — please sign in again.
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger mb-3">
      <i class="bi bi-exclamation-triangle me-2"></i><?= sanitize($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="">
      <?= csrf_field() ?>

      <!-- Role selector -->
      <div class="mb-4">
        <label class="form-label">Login as</label>
        <div class="d-flex gap-2 flex-wrap">
          <?php foreach ($roles as $rval => [$rlabel, $ricon]):
            $sel = ($_POST['role'] ?? 'student') === $rval;
          ?>
          <label class="role-pill <?= $sel ? 'active' : '' ?>" for="role_<?= $rval ?>">
            <input type="radio" class="d-none" name="role" id="role_<?= $rval ?>"
                   value="<?= $rval ?>" <?= $sel ? 'checked' : '' ?>>
            <i class="bi <?= $ricon ?>"></i> <?= ucfirst($rval) ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Email -->
      <div class="mb-3">
        <label class="form-label" for="email">Email Address</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-envelope"></i></span>
          <input type="email" id="email" name="email" class="form-control"
                 value="<?= sanitize($_POST['email'] ?? '') ?>"
                 placeholder="your@email.com" required autofocus>
        </div>
      </div>

      <!-- Password -->
      <div class="mb-4">
        <div class="d-flex justify-content-between">
          <label class="form-label" for="password">Password</label>
          <a href="<?= SITE_URL ?>/auth/forgot-password.php"
             style="font-size:0.78rem;color:var(--gold);">Forgot password?</a>
        </div>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock"></i></span>
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="Enter your password" required>
          <button class="btn btn-secondary" type="button" id="togglePwd">
            <i class="bi bi-eye"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100 btn-lg">
        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
      </button>
    </form>

    <div class="text-center mt-4" style="color:rgba(255,255,255,0.4);font-size:0.8rem;">
      <a href="<?= SITE_URL ?>/public/admission.php"
         style="color:var(--gold);font-weight:600;">
        <i class="bi bi-pencil-square me-1"></i>Apply for Admission
      </a>
      &nbsp;·&nbsp;
      <a href="<?= SITE_URL ?>/public/admission-status.php"
         style="color:rgba(255,255,255,0.5);">Track Application</a>
      &nbsp;·&nbsp;
      <a href="<?= SITE_URL ?>/admin/login.php"
         style="color:rgba(255,255,255,0.3);font-size:0.75rem;">Admin</a>
    </div>

    <p class="text-center mt-4" style="color:rgba(255,255,255,0.25);font-size:0.75rem;">
      <?= sanitize(get_setting('footer_text','© '.date('Y').' '.$site_name)) ?>
    </p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Password toggle
document.getElementById('togglePwd')?.addEventListener('click', function () {
  const pw = document.getElementById('password');
  const ic = this.querySelector('i');
  pw.type = pw.type === 'password' ? 'text' : 'password';
  ic.className = pw.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
});
// Role pill active
document.querySelectorAll('.role-pill').forEach(label => {
  label.addEventListener('click', () => {
    document.querySelectorAll('.role-pill').forEach(l => l.classList.remove('active'));
    label.classList.add('active');
  });
});
</script>
</body>
</html>
