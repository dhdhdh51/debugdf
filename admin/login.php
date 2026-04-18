<?php
/**
 * admin/login.php — Admin-only login (separate URL, not linked from public site)
 */
require_once dirname(__DIR__) . '/config/config.php';

if (is_logged_in() && get_user_role() === 'admin') {
    redirect(SITE_URL . '/admin/');
}
if (is_logged_in()) {
    redirect(SITE_URL . '/' . get_user_role() . '/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();
    $email    = sanitize_email($_POST['email']    ?? '');
    $password = $_POST['password']                ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT * FROM users WHERE email=? AND role='admin' AND status='active' LIMIT 1"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['user_name']     = $user['name'];
            $_SESSION['user_email']    = $user['email'];
            $_SESSION['user_role']     = 'admin';
            $_SESSION['last_activity'] = time();
            $pdo->prepare("UPDATE users SET last_login=NOW() WHERE id=?")->execute([$user['id']]);
            redirect(SITE_URL . '/admin/');
        } else {
            $error = 'Invalid admin credentials.';
        }
    }
}

$site_name = get_setting('site_name', 'School ERP');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — <?= sanitize($site_name) ?></title>
  <!-- Block search indexing for admin login -->
  <meta name="robots" content="noindex, nofollow">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= ASSETS_URL ?>/css/style.css" rel="stylesheet">
  <style>
    body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--navy); }
    .admin-login-card {
      width: 100%; max-width: 420px;
      background: var(--navy-2);
      border: 1px solid rgba(245,166,35,0.2);
      border-radius: 24px;
      padding: 40px 36px;
      box-shadow: 0 24px 64px rgba(0,0,0,0.5), 0 0 0 1px rgba(245,166,35,0.1);
    }
    .shield-icon {
      width: 64px; height: 64px;
      border-radius: 18px;
      background: linear-gradient(135deg, rgba(239,68,68,0.2), rgba(239,68,68,0.05));
      border: 1px solid rgba(239,68,68,0.3);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.8rem;
      margin: 0 auto 20px;
    }
    /* Subtle grid pattern */
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background-image:
        linear-gradient(rgba(245,166,35,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(245,166,35,0.03) 1px, transparent 1px);
      background-size: 40px 40px;
      pointer-events: none;
    }
  </style>
</head>
<body>
<div class="admin-login-card">
  <div class="shield-icon">🛡️</div>
  <h3 style="font-family:'Playfair Display',serif;font-weight:800;color:#fff;text-align:center;margin-bottom:4px;">
    Admin Access
  </h3>
  <p style="color:rgba(255,255,255,0.4);font-size:0.82rem;text-align:center;margin-bottom:28px;">
    Restricted area — authorised personnel only
  </p>

  <?php if ($error): ?>
  <div class="alert alert-danger mb-3" style="font-size:0.85rem;">
    <i class="bi bi-shield-exclamation me-2"></i><?= sanitize($error) ?>
  </div>
  <?php endif; ?>

  <form method="POST">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label">Admin Email</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
        <input type="email" name="email" class="form-control"
               value="<?= sanitize($_POST['email'] ?? '') ?>"
               placeholder="admin@school.com" required autofocus>
      </div>
    </div>
    <div class="mb-4">
      <label class="form-label">Password</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
        <input type="password" name="password" id="pwd" class="form-control"
               placeholder="Admin password" required>
        <button type="button" class="btn btn-secondary" id="togglePwd">
          <i class="bi bi-eye"></i>
        </button>
      </div>
    </div>
    <button type="submit" class="btn btn-danger w-100 btn-lg" style="background:linear-gradient(135deg,#ef4444,#dc2626);">
      <i class="bi bi-shield-lock me-2"></i>Access Admin Panel
    </button>
  </form>

  <div class="text-center mt-4">
    <a href="<?= SITE_URL ?>/auth/login.php"
       style="color:rgba(255,255,255,0.3);font-size:0.78rem;">
      ← Back to main login
    </a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('togglePwd')?.addEventListener('click', function () {
  const pw = document.getElementById('pwd');
  const ic = this.querySelector('i');
  pw.type = pw.type === 'password' ? 'text' : 'password';
  ic.className = pw.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
});
</script>
</body>
</html>
