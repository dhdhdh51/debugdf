<?php
/**
 * public/admission.php  (UPDATED)
 * Shows admission fee note/amount if admin has enabled it.
 * Fee invoice is created automatically on approval — not at application stage.
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'mailer.php';

$site_name = get_setting('site_name', 'School ERP');
$classes   = get_classes();
$errors    = [];
$success   = '';
$app_id    = '';

// Admission fee settings
$adm_fee_on     = get_setting('admission_fee_enabled', '0') === '1';
$adm_fee_amount = (float) get_setting('admission_fee_amount', '0');
$adm_fee_type   = get_setting('admission_fee_type', 'Admission Fee');
$adm_fee_note   = get_setting('admission_fee_note', '');
$currency_sym   = get_setting('currency_symbol', '₹');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();

    $name         = sanitize($_POST['name']             ?? '');
    $email        = sanitize_email($_POST['email']      ?? '');
    $phone        = sanitize($_POST['phone']            ?? '');
    $dob          = sanitize($_POST['dob']              ?? '');
    $gender       = sanitize($_POST['gender']           ?? '');
    $class_app    = sanitize_int($_POST['class_applying'] ?? 0);
    $prev_school  = sanitize($_POST['previous_school']  ?? '');
    $address      = sanitize($_POST['address']          ?? '');
    $parent_name  = sanitize($_POST['parent_name']      ?? '');
    $parent_phone = sanitize($_POST['parent_phone']     ?? '');
    $parent_email = sanitize_email($_POST['parent_email'] ?? '');

    if (empty($name))        $errors[] = 'Full name is required.';
    if (empty($phone))       $errors[] = 'Phone number is required.';
    if (empty($parent_name)) $errors[] = 'Parent / Guardian name is required.';
    if (!$class_app)         $errors[] = 'Please select a class.';
    if (!empty($email) && !validate_email($email)) $errors[] = 'Invalid email address.';

    // Document upload
    $document = null;
    if (!empty($_FILES['document']['name'])) {
        $document = upload_file($_FILES['document'], 'admissions', ALLOWED_DOCS);
        if ($document === false) $errors[] = 'Invalid document. Allowed: PDF, DOC, JPG, PNG (max 5 MB).';
    }

    if (empty($errors)) {
        $app_id = generate_application_id();

        $pdo->prepare(
            "INSERT INTO admissions
             (application_id, name, email, phone, dob, gender, class_applying,
              previous_school, address, parent_name, parent_phone, parent_email,
              document, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
        )->execute([
            $app_id, $name, $email ?: null, $phone, $dob ?: null, $gender ?: null,
            $class_app ?: null, $prev_school ?: null, $address ?: null,
            $parent_name, $parent_phone ?: null, $parent_email ?: null, $document
        ]);

        // Confirmation email to applicant
        if (!empty($email)) {
            SchoolMailer::sendAdmissionConfirmation($email, $name, $app_id);
        }

        // Notify admin
        $admin_email = get_setting('contact_email', '');
        if ($admin_email) {
            $class_name = $pdo->query(
                "SELECT name FROM classes WHERE id=" . (int)$class_app
            )->fetchColumn() ?: 'N/A';

            SchoolMailer::sendCustomNotification(
                $admin_email, 'Admin',
                'New Admission Application',
                "New application received from {$name} (App ID: {$app_id}) for {$class_name}."
            );
        }

        // In-app notification for all admins
        $admins = $pdo->query(
            "SELECT id FROM users WHERE role='admin'"
        )->fetchAll(PDO::FETCH_COLUMN);
        foreach ($admins as $uid) {
            create_notification(
                (int)$uid, 'admin',
                'New Admission Application',
                "From {$name} (App ID: {$app_id})",
                'info'
            );
        }

        $success = $app_id;
        $_POST   = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Online Admission — <?= sanitize($site_name) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= ASSETS_URL ?>/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= SITE_URL ?>">
      <i class="bi bi-mortarboard-fill me-2"></i><?= sanitize($site_name) ?>
    </a>
    <div class="ms-auto d-flex gap-2">
      <a href="<?= SITE_URL ?>/public/admission-status.php" class="btn btn-outline-light btn-sm">
        Track Application
      </a>
      <a href="<?= SITE_URL ?>/auth/login.php" class="btn btn-light btn-sm">Login</a>
    </div>
  </div>
</nav>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">

      <!-- Page header -->
      <div class="text-center mb-4">
        <h2 class="fw-bold">Online Admission Form</h2>
        <p class="text-muted">
          Fill in the details to apply for admission at <?= sanitize($site_name) ?>
        </p>
        <a href="<?= SITE_URL ?>/public/admission-status.php" class="text-primary small">
          Already applied? Track your application &rarr;
        </a>
      </div>

      <!-- Admission fee notice (shown when fee is enabled) -->
      <?php if ($adm_fee_on && $adm_fee_amount > 0): ?>
      <div class="alert alert-info d-flex gap-3 align-items-start mb-4">
        <i class="bi bi-cash-stack fs-3 text-info flex-shrink-0 mt-1"></i>
        <div>
          <strong><?= sanitize($adm_fee_type) ?>: <?= $currency_sym . number_format($adm_fee_amount, 2) ?></strong><br>
          <?php if ($adm_fee_note): ?>
          <span class="small"><?= sanitize($adm_fee_note) ?></span>
          <?php else: ?>
          <span class="small">
            A fee invoice of
            <strong><?= $currency_sym . number_format($adm_fee_amount, 2) ?></strong>
            will be generated automatically upon approval of your application.
            Payment details will be shared via email.
          </span>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Success card -->
      <?php if ($success): ?>
      <div class="card border-0 shadow-sm bg-success text-white mb-4">
        <div class="card-body text-center py-4">
          <i class="bi bi-check-circle-fill display-4 mb-3 d-block"></i>
          <h4 class="fw-bold">Application Submitted Successfully!</h4>
          <p class="mb-1">Your Application ID is:</p>
          <div class="display-6 fw-bold letter-spacing-4 mb-3"><?= sanitize($success) ?></div>
          <p class="opacity-75 small">
            Save this ID to track your application status.
            <?= ($adm_fee_on && $adm_fee_amount > 0)
              ? 'A fee invoice will be emailed to you upon approval.'
              : '' ?>
            A confirmation email has been sent (if email was provided).
          </p>
          <div class="d-flex gap-2 justify-content-center mt-2">
            <a href="<?= SITE_URL ?>/public/admission-status.php?id=<?= urlencode($success) ?>"
               class="btn btn-light fw-semibold">
              <i class="bi bi-search me-1"></i>Track Application
            </a>
            <a href="<?= SITE_URL ?>/public/admission.php" class="btn btn-outline-light">
              New Application
            </a>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Errors -->
      <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <!-- Form -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 fw-semibold py-3">
          <i class="bi bi-file-earmark-person me-2 text-primary"></i>Student Information
        </div>
        <div class="card-body">
          <form method="POST" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>

            <!-- Student Details -->
            <div class="row g-3">
              <div class="col-12">
                <h6 class="fw-semibold text-primary border-bottom pb-2">
                  <i class="bi bi-person me-2"></i>Student Details
                </h6>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="<?= sanitize($_POST['name'] ?? '') ?>" required
                       placeholder="Student's full name">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Email Address</label>
                <input type="email" name="email" class="form-control"
                       value="<?= sanitize($_POST['email'] ?? '') ?>"
                       placeholder="student@email.com">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Phone <span class="text-danger">*</span></label>
                <input type="tel" name="phone" class="form-control"
                       value="<?= sanitize($_POST['phone'] ?? '') ?>" required
                       placeholder="+91 9000000000">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Date of Birth</label>
                <input type="date" name="dob" class="form-control"
                       value="<?= sanitize($_POST['dob'] ?? '') ?>" max="<?= date('Y-m-d') ?>">
              </div>

              <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Gender</label>
                <select name="gender" class="form-select">
                  <option value="">Select</option>
                  <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                  <option value="<?= $g ?>" <?= ($_POST['gender'] ?? '') === $g ? 'selected' : '' ?>>
                    <?= $g ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">
                  Class Applying For <span class="text-danger">*</span>
                </label>
                <select name="class_applying" class="form-select" required>
                  <option value="">Select Class</option>
                  <?php foreach ($classes as $cl): ?>
                  <option value="<?= $cl['id'] ?>"
                    <?= (sanitize_int($_POST['class_applying'] ?? 0)) == $cl['id'] ? 'selected' : '' ?>>
                    <?= sanitize($cl['name']) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Previous School</label>
                <input type="text" name="previous_school" class="form-control"
                       value="<?= sanitize($_POST['previous_school'] ?? '') ?>"
                       placeholder="Name of previous school">
              </div>

              <div class="col-12">
                <label class="form-label fw-semibold">Address</label>
                <textarea name="address" class="form-control" rows="2"
                          placeholder="Full residential address"><?= sanitize($_POST['address'] ?? '') ?></textarea>
              </div>
            </div>

            <!-- Parent Details -->
            <div class="row g-3 mt-2">
              <div class="col-12">
                <h6 class="fw-semibold text-primary border-bottom pb-2">
                  <i class="bi bi-people me-2"></i>Parent / Guardian Details
                </h6>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">
                  Parent / Guardian Name <span class="text-danger">*</span>
                </label>
                <input type="text" name="parent_name" class="form-control"
                       value="<?= sanitize($_POST['parent_name'] ?? '') ?>" required>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Parent Phone</label>
                <input type="tel" name="parent_phone" class="form-control"
                       value="<?= sanitize($_POST['parent_phone'] ?? '') ?>">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Parent Email</label>
                <input type="email" name="parent_email" class="form-control"
                       value="<?= sanitize($_POST['parent_email'] ?? '') ?>">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Supporting Document</label>
                <input type="file" name="document" class="form-control"
                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                <div class="form-text">Birth certificate, mark sheet, etc. (PDF/JPG, max 5 MB)</div>
              </div>
            </div>

            <!-- Fee notice inside form (when enabled) -->
            <?php if ($adm_fee_on && $adm_fee_amount > 0): ?>
            <div class="alert alert-warning mt-3 py-2 small">
              <i class="bi bi-exclamation-triangle me-1"></i>
              By submitting this form, you acknowledge that a
              <strong><?= sanitize($adm_fee_type) ?></strong> of
              <strong><?= $currency_sym . number_format($adm_fee_amount, 2) ?></strong>
              will be charged upon approval of your application.
              <?= $adm_fee_note ? nl2br(sanitize($adm_fee_note)) : '' ?>
            </div>
            <?php endif; ?>

            <!-- Terms -->
            <div class="form-check mt-3">
              <input class="form-check-input" type="checkbox" id="terms" required>
              <label class="form-check-label small" for="terms">
                I confirm that all information provided is accurate and I agree to the school's
                terms and conditions<?= $adm_fee_on ? ', including the applicable admission fee.' : '.' ?>
              </label>
            </div>

            <button type="submit" class="btn btn-primary w-100 mt-3 py-2 fw-semibold">
              <i class="bi bi-send me-2"></i>Submit Application
            </button>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<footer class="text-center py-3 text-muted small bg-white border-top mt-4">
  <?= sanitize(get_setting('footer_text', '')) ?> &nbsp;|&nbsp;
  <a href="<?= SITE_URL ?>/auth/login.php" class="text-muted">Login</a>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
