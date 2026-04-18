<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'mailer.php';

if (is_logged_in()) redirect(SITE_URL . '/' . get_user_role() . '/');
if (isset($_GET['restart'])) { session_unset(); redirect(SITE_URL.'/auth/forgot-password.php'); }

$step    = $_SESSION['otp_step'] ?? 1;
$message = '';
$error   = '';
$site_name = get_setting('site_name', 'School ERP');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();
    if ($step === 1) {
        $email = sanitize_email($_POST['email'] ?? '');
        if (!validate_email($email)) { $error = 'Enter a valid email.'; }
        else {
            $stmt = $pdo->prepare("SELECT id,name FROM users WHERE email=? AND status='active' LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                $otp = generate_otp(); save_otp($email, $otp);
                $sent = SchoolMailer::sendOTP($email,$user['name'],$otp);
                $_SESSION['otp_email'] = $email; $_SESSION['otp_step'] = 2; $step = 2;
                $message = $sent ? 'OTP sent to your email. Valid for '.OTP_EXPIRY_MINUTES.' minutes.' : 'OTP: '.$otp.' (email service not configured)';
            } else { $error = 'Email not found.'; }
        }
    } elseif ($step === 2) {
        $otp = trim($_POST['otp'] ?? ''); $email = $_SESSION['otp_email'] ?? '';
        if (verify_otp($email,$otp)) { $_SESSION['otp_step'] = 3; $_SESSION['otp_verified'] = true; $step = 3; $message = 'OTP verified! Set your new password.'; }
        else { $error = 'Invalid or expired OTP.'; }
    } elseif ($step === 3) {
        if (!isset($_SESSION['otp_verified'])) { $step=1; $error='Session expired.'; }
        else {
            $email=$_SESSION['otp_email']??''; $pwd=$_POST['password']??''; $cpwd=$_POST['confirm_password']??'';
            if (strlen($pwd)<8) { $error='Min 8 characters.'; }
            elseif ($pwd!==$cpwd) { $error='Passwords do not match.'; }
            else {
                $pdo->prepare("UPDATE users SET password=? WHERE email=?")->execute([password_hash($pwd,PASSWORD_BCRYPT),$email]);
                unset($_SESSION['otp_step'],$_SESSION['otp_email'],$_SESSION['otp_verified']);
                set_flash('success','Password reset! Please login.'); redirect(SITE_URL.'/auth/login.php');
            }
        }
    }
}
$step_labels=['Email','OTP','Reset'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forgot Password — <?= sanitize($site_name) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= ASSETS_URL ?>/css/style.css" rel="stylesheet">
  <style>
    body { min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--navy);padding:24px 16px; }
    body::before { content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(245,166,35,0.025) 1px,transparent 1px),linear-gradient(90deg,rgba(245,166,35,0.025) 1px,transparent 1px);background-size:50px 50px;pointer-events:none; }
    .fp-card { width:100%;max-width:440px;background:var(--navy-2);border:1px solid rgba(245,166,35,0.15);border-radius:24px;padding:40px 36px;box-shadow:0 24px 64px rgba(0,0,0,0.5);position:relative;z-index:1; }
  </style>
</head>
<body>
<div class="fp-card">
  <div class="text-center mb-4">
    <div style="width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,rgba(245,166,35,0.2),rgba(245,166,35,0.05));border:1px solid rgba(245,166,35,0.3);display:flex;align-items:center;justify-content:center;font-size:1.6rem;margin:0 auto 16px;">🔐</div>
    <h3 style="font-family:'Playfair Display',serif;color:#fff;margin-bottom:4px;">Forgot Password</h3>
    <p style="color:rgba(255,255,255,0.4);font-size:0.85rem;"><?= sanitize($site_name) ?></p>
  </div>

  <!-- Steps -->
  <div class="d-flex align-items-center gap-2 mb-4">
    <?php for($i=1;$i<=3;$i++): ?>
    <div class="step-dot <?= $i < $step ? 'done' : ($i === $step ? 'active' : '') ?>">
      <?= $i < $step ? '<i class="bi bi-check"></i>' : $i ?>
    </div>
    <?php if($i<3): ?><div style="flex:1;height:2px;background:<?= $i<$step?'var(--gold)':'rgba(255,255,255,0.1)' ?>;border-radius:1px;transition:background 0.3s;"></div><?php endif; ?>
    <?php endfor; ?>
  </div>
  <div class="d-flex justify-content-between mb-4" style="font-size:0.7rem;color:var(--text-muted);margin-top:-12px;">
    <?php foreach ($step_labels as $sl): ?>
    <span><?= $sl ?></span>
    <?php endforeach; ?>
  </div>

  <?php if ($error):   ?><div class="alert alert-danger"  style="font-size:0.85rem;"><i class="bi bi-exclamation-triangle me-2"></i><?= sanitize($error) ?></div><?php endif; ?>
  <?php if ($message): ?><div class="alert alert-success" style="font-size:0.85rem;"><i class="bi bi-check-circle me-2"></i><?= sanitize($message) ?></div><?php endif; ?>

  <form method="POST">
    <?= csrf_field() ?>
    <?php if ($step === 1): ?>
    <div class="mb-4">
      <label class="form-label">Registered Email</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
        <input type="email" name="email" class="form-control" placeholder="your@email.com" required autofocus value="<?= sanitize($_POST['email']??'') ?>">
      </div>
    </div>
    <button type="submit" class="btn btn-primary w-100 btn-lg"><i class="bi bi-send me-2"></i>Send OTP</button>
    <?php elseif ($step === 2): ?>
    <p style="color:var(--text-muted);font-size:0.82rem;margin-bottom:16px;">OTP sent to: <strong style="color:var(--gold);"><?= sanitize($_SESSION['otp_email']??'') ?></strong></p>
    <div class="mb-4">
      <label class="form-label">Enter 6-digit OTP</label>
      <input type="text" name="otp" class="form-control otp-input" maxlength="6" placeholder="000000" required autofocus inputmode="numeric" pattern="\d{6}">
      <div class="form-text">Valid for <?= OTP_EXPIRY_MINUTES ?> minutes.</div>
    </div>
    <button type="submit" class="btn btn-primary w-100 btn-lg"><i class="bi bi-check-circle me-2"></i>Verify OTP</button>
    <div class="text-center mt-3"><a href="<?= SITE_URL ?>/auth/forgot-password.php?restart=1" style="font-size:0.78rem;color:var(--text-muted);">Resend OTP</a></div>
    <?php elseif ($step === 3): ?>
    <div class="mb-3">
      <label class="form-label">New Password</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-lock"></i></span>
        <input type="password" name="password" id="pwd" class="form-control" placeholder="Min 8 characters" required minlength="8">
        <button type="button" class="btn btn-secondary" id="tp1"><i class="bi bi-eye"></i></button>
      </div>
    </div>
    <div class="mb-4">
      <label class="form-label">Confirm Password</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
      </div>
    </div>
    <button type="submit" class="btn btn-primary w-100 btn-lg"><i class="bi bi-check-lg me-2"></i>Reset Password</button>
    <?php endif; ?>
  </form>

  <div class="text-center mt-4">
    <a href="<?= SITE_URL ?>/auth/login.php" style="font-size:0.78rem;color:rgba(255,255,255,0.3);">
      <i class="bi bi-arrow-left me-1"></i>Back to Login
    </a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('tp1')?.addEventListener('click', function() {
  const p=document.getElementById('pwd'), i=this.querySelector('i');
  p.type=p.type==='password'?'text':'password';
  i.className=p.type==='password'?'bi bi-eye':'bi bi-eye-slash';
});
document.querySelector('[name="otp"]')?.addEventListener('input', function() {
  if (this.value.length===6) this.form.submit();
});
</script>
</body>
</html>
