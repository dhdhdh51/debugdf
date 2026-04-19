<?php
/**
 * public/admission.php
 * Online admission form — fee invoice created immediately on submission.
 * Documents: Aadhar card, birth certificate, transfer cert, passport photo.
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'mailer.php';

$site_name = get_setting('site_name', 'School ERP');
$classes   = get_classes();
$errors    = [];
$success   = '';
$app_id    = '';
$fee_id    = null;

// Admission fee settings
$adm_fee_on     = get_setting('admission_fee_enabled', '0') === '1';
$adm_fee_amount = (float) get_setting('admission_fee_amount', '0');
$adm_fee_type   = get_setting('admission_fee_type', 'Admission Fee');
$adm_fee_note   = get_setting('admission_fee_note', '');
$adm_fee_days   = (int) get_setting('admission_fee_due_days', '30');
$currency_sym   = get_setting('currency_symbol', '₹');
$payu_key       = get_setting('payu_merchant_key', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();

    $name         = sanitize($_POST['name']              ?? '');
    $email        = sanitize_email($_POST['email']       ?? '');
    $phone        = sanitize($_POST['phone']             ?? '');
    $dob          = sanitize($_POST['dob']               ?? '');
    $gender       = sanitize($_POST['gender']            ?? '');
    $class_app    = sanitize_int($_POST['class_applying']?? 0);
    $prev_school  = sanitize($_POST['previous_school']   ?? '');
    $address      = sanitize($_POST['address']           ?? '');
    $parent_name  = sanitize($_POST['parent_name']       ?? '');
    $parent_phone = sanitize($_POST['parent_phone']      ?? '');
    $parent_email = sanitize_email($_POST['parent_email']?? '');

    // Validations
    if (empty($name))        $errors[] = 'Full name is required.';
    if (empty($phone))       $errors[] = 'Phone number is required.';
    if (empty($parent_name)) $errors[] = 'Parent / Guardian name is required.';
    if (!$class_app)         $errors[] = 'Please select a class.';
    if (!empty($email) && !validate_email($email)) $errors[] = 'Invalid student email address.';
    if (!empty($parent_email) && !validate_email($parent_email)) $errors[] = 'Invalid parent email address.';

    // ── Document uploads ──────────────────────────────────────
    $doc_aadhar       = null;
    $doc_birth_cert   = null;
    $doc_transfer_cert= null;
    $doc_photo        = null;

    // Aadhar card — required
    if (empty($_FILES['doc_aadhar']['name'])) {
        $errors[] = 'Aadhar card is required.';
    } else {
        $doc_aadhar = upload_file($_FILES['doc_aadhar'], 'admissions', ALLOWED_DOCS);
        if ($doc_aadhar === false) $errors[] = 'Aadhar card: invalid file. Allowed PDF/JPG/PNG, max 5 MB.';
    }

    // Passport photo — required (image only)
    if (empty($_FILES['doc_photo']['name'])) {
        $errors[] = 'Passport photo is required.';
    } else {
        $doc_photo = upload_file($_FILES['doc_photo'], 'admissions', ALLOWED_IMAGES);
        if ($doc_photo === false) $errors[] = 'Passport photo: invalid file. Allowed JPG/PNG/WEBP, max 5 MB.';
    }

    // Birth certificate — optional
    if (!empty($_FILES['doc_birth_cert']['name'])) {
        $doc_birth_cert = upload_file($_FILES['doc_birth_cert'], 'admissions', ALLOWED_DOCS);
        if ($doc_birth_cert === false) $errors[] = 'Birth certificate: invalid file. Allowed PDF/JPG/PNG, max 5 MB.';
    }

    // Transfer certificate — optional
    if (!empty($_FILES['doc_transfer_cert']['name'])) {
        $doc_transfer_cert = upload_file($_FILES['doc_transfer_cert'], 'admissions', ALLOWED_DOCS);
        if ($doc_transfer_cert === false) $errors[] = 'Transfer certificate: invalid file. Allowed PDF/JPG/PNG, max 5 MB.';
    }

    if (empty($errors)) {
        $app_id = generate_application_id();

        $pdo->prepare(
            "INSERT INTO admissions
             (application_id, name, email, phone, dob, gender, class_applying,
              previous_school, address, parent_name, parent_phone, parent_email,
              doc_aadhar, doc_birth_cert, doc_transfer_cert, doc_photo, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
        )->execute([
            $app_id, $name, $email ?: null, $phone,
            $dob ?: null, $gender ?: null, $class_app ?: null,
            $prev_school ?: null, $address ?: null,
            $parent_name, $parent_phone ?: null, $parent_email ?: null,
            $doc_aadhar, $doc_birth_cert, $doc_transfer_cert, $doc_photo,
        ]);

        // ── Create fee invoice immediately on submission ───────
        if ($adm_fee_on && $adm_fee_amount > 0) {
            $due_date   = date('Y-m-d', strtotime("+{$adm_fee_days} days"));
            $invoice_no = 'ADM-' . $app_id;
            try {
                $pdo->prepare(
                    "INSERT INTO fees (student_id, fee_type, amount, due_date, status, invoice_no)
                     VALUES (NULL, ?, ?, ?, 'pending', ?)"
                )->execute([$adm_fee_type . ' [' . $name . ']', $adm_fee_amount, $due_date, $invoice_no]);
                $fee_id = (int) $pdo->lastInsertId();
            } catch (PDOException $e) {
                error_log('[Admission Fee] Could not create fee on submission: ' . $e->getMessage());
            }
        }

        // Confirmation email to applicant
        if (!empty($email)) {
            SchoolMailer::sendAdmissionConfirmation($email, $name, $app_id);
        }

        // Notify admin by email
        $admin_email = get_setting('contact_email', '');
        if ($admin_email) {
            $class_name = $pdo->query(
                "SELECT name FROM classes WHERE id=" . (int)$class_app
            )->fetchColumn() ?: 'N/A';
            SchoolMailer::sendCustomNotification(
                $admin_email, 'Admin',
                'New Admission Application',
                "New application from {$name} (App ID: {$app_id}) for {$class_name}."
            );
        }

        // In-app notification for admins
        $admins = $pdo->query("SELECT id FROM users WHERE role='admin'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($admins as $uid) {
            create_notification((int)$uid, 'admin', 'New Admission Application',
                "From {$name} (App ID: {$app_id})", 'info');
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
  <style>
    body { background: #f0f2f5; }
    .doc-upload-box {
      border: 2px dashed #ced4da;
      border-radius: 10px;
      padding: 14px 16px;
      background: #f8f9fa;
      transition: border-color .2s, background .2s;
      cursor: pointer;
    }
    .doc-upload-box:hover { border-color: #1a6dcc; background: #e8f1fc; }
    .doc-upload-box input[type=file] { display: none; }
    .doc-upload-box .doc-label { font-weight: 600; font-size: .875rem; color: #212529; }
    .doc-upload-box .doc-hint  { font-size: .75rem; color: #6c757d; margin-top: 2px; }
    .doc-upload-box .doc-name  { font-size: .8rem; color: #1a6dcc; margin-top: 6px; font-weight: 500; word-break: break-all; }
    .doc-required::after { content: ' *'; color: #dc2626; }
    .section-divider { font-size: .8rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .08em; color: #1a6dcc; border-bottom: 2px solid #dbeafe;
      padding-bottom: 6px; margin-bottom: 16px; }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= SITE_URL ?>">
      <i class="bi bi-mortarboard-fill me-2"></i><?= sanitize($site_name) ?>
    </a>
    <div class="ms-auto d-flex gap-2">
      <a href="<?= SITE_URL ?>/public/admission-status.php" class="btn btn-outline-light btn-sm">
        <i class="bi bi-search me-1"></i>Track Application
      </a>
      <a href="<?= SITE_URL ?>/auth/login.php" class="btn btn-light btn-sm">Login</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-9">

      <!-- Page header -->
      <div class="text-center mb-4">
        <h2 class="fw-bold">Online Admission Form</h2>
        <p class="text-muted mb-1">
          Fill in all details and upload required documents to apply for admission at <?= sanitize($site_name) ?>
        </p>
        <a href="<?= SITE_URL ?>/public/admission-status.php" class="text-primary small">
          Already applied? Track your application &rarr;
        </a>
      </div>

      <!-- Admission fee notice -->
      <?php if ($adm_fee_on && $adm_fee_amount > 0): ?>
      <div class="alert alert-warning d-flex gap-3 align-items-start mb-4">
        <i class="bi bi-cash-stack fs-3 text-warning flex-shrink-0 mt-1"></i>
        <div>
          <strong><?= sanitize($adm_fee_type) ?>: <?= $currency_sym . number_format($adm_fee_amount, 2) ?></strong><br>
          <span class="small">
            <?php if ($adm_fee_note): ?>
              <?= nl2br(sanitize($adm_fee_note)) ?>
            <?php else: ?>
              A <?= sanitize($adm_fee_type) ?> of <strong><?= $currency_sym . number_format($adm_fee_amount, 2) ?></strong>
              is due within <?= $adm_fee_days ?> days of submission. An invoice will be generated with your application.
            <?php endif; ?>
          </span>
        </div>
      </div>
      <?php endif; ?>

      <!-- ── SUCCESS ── -->
      <?php if ($success): ?>
      <div class="card border-0 shadow mb-4 overflow-hidden">
        <div class="card-body bg-success text-white text-center py-4">
          <i class="bi bi-check-circle-fill display-4 mb-3 d-block"></i>
          <h4 class="fw-bold mb-1">Application Submitted Successfully!</h4>
          <p class="mb-1 opacity-75">Your Application ID is:</p>
          <div class="display-5 fw-bold mb-3"><?= sanitize($success) ?></div>
          <p class="small opacity-75">
            Save this ID to track your application. A confirmation email has been sent (if email provided).
          </p>
        </div>
        <?php if ($adm_fee_on && $adm_fee_amount > 0): ?>
        <div class="card-body border-top bg-white">
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="flex-grow-1">
              <div class="fw-semibold">
                <i class="bi bi-receipt me-2 text-warning"></i>
                <?= sanitize($adm_fee_type) ?> Invoice: <span class="text-primary">ADM-<?= sanitize($success) ?></span>
              </div>
              <div class="small text-muted mt-1">
                Amount: <strong><?= $currency_sym . number_format($adm_fee_amount, 2) ?></strong>
                &nbsp;·&nbsp; Due within <?= $adm_fee_days ?> days
                &nbsp;·&nbsp; Status: <span class="badge bg-warning text-dark">Pending</span>
              </div>
              <?php if ($adm_fee_note): ?>
              <div class="small text-muted mt-1"><?= nl2br(sanitize($adm_fee_note)) ?></div>
              <?php endif; ?>
            </div>
            <?php if ($fee_id && !empty($payu_key)): ?>
            <a href="<?= SITE_URL ?>/payment/payu.php?fee_id=<?= $fee_id ?>"
               class="btn btn-warning fw-semibold">
              <i class="bi bi-credit-card me-1"></i>Pay Now
            </a>
            <?php else: ?>
            <div class="text-muted small">
              <i class="bi bi-info-circle me-1"></i>Contact school to pay this fee.
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
        <div class="card-footer bg-light border-0 d-flex gap-2 justify-content-center py-3">
          <a href="<?= SITE_URL ?>/public/admission-status.php?id=<?= urlencode($success) ?>"
             class="btn btn-primary">
            <i class="bi bi-search me-1"></i>Track Application
          </a>
          <a href="<?= SITE_URL ?>/public/admission.php" class="btn btn-outline-secondary">
            New Application
          </a>
        </div>
      </div>
      <?php endif; ?>

      <!-- Errors -->
      <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <strong><i class="bi bi-exclamation-triangle me-1"></i>Please fix the following:</strong>
        <ul class="mb-0 mt-1">
          <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <!-- ── FORM ── -->
      <?php if (!$success): ?>
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
          <form method="POST" enctype="multipart/form-data" novalidate id="admissionForm">
            <?= csrf_field() ?>

            <!-- ═══ Student Details ═══ -->
            <div class="section-divider"><i class="bi bi-person me-2"></i>Student Details</div>
            <div class="row g-3 mb-4">

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="<?= sanitize($_POST['name'] ?? '') ?>" required placeholder="Student's full name">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Student Email</label>
                <input type="email" name="email" class="form-control"
                       value="<?= sanitize($_POST['email'] ?? '') ?>" placeholder="student@email.com">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Phone <span class="text-danger">*</span></label>
                <input type="tel" name="phone" class="form-control"
                       value="<?= sanitize($_POST['phone'] ?? '') ?>" required placeholder="+91 9000000000">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Date of Birth</label>
                <input type="date" name="dob" class="form-control"
                       value="<?= sanitize($_POST['dob'] ?? '') ?>" max="<?= date('Y-m-d') ?>">
              </div>

              <div class="col-6 col-md-4">
                <label class="form-label fw-semibold">Gender</label>
                <select name="gender" class="form-select">
                  <option value="">Select</option>
                  <?php foreach (['Male','Female','Other'] as $g): ?>
                  <option value="<?= $g ?>" <?= ($_POST['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-12 col-md-8">
                <label class="form-label fw-semibold">Class Applying For <span class="text-danger">*</span></label>
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
                       placeholder="Name of previous school (if any)">
              </div>

              <div class="col-12">
                <label class="form-label fw-semibold">Residential Address</label>
                <textarea name="address" class="form-control" rows="2"
                          placeholder="Full residential address"><?= sanitize($_POST['address'] ?? '') ?></textarea>
              </div>
            </div>

            <!-- ═══ Parent / Guardian Details ═══ -->
            <div class="section-divider"><i class="bi bi-people me-2"></i>Parent / Guardian Details</div>
            <div class="row g-3 mb-4">

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Parent / Guardian Name <span class="text-danger">*</span></label>
                <input type="text" name="parent_name" class="form-control"
                       value="<?= sanitize($_POST['parent_name'] ?? '') ?>" required>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Parent Phone</label>
                <input type="tel" name="parent_phone" class="form-control"
                       value="<?= sanitize($_POST['parent_phone'] ?? '') ?>" placeholder="+91 9000000000">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Parent Email</label>
                <input type="email" name="parent_email" class="form-control"
                       value="<?= sanitize($_POST['parent_email'] ?? '') ?>" placeholder="parent@email.com">
              </div>
            </div>

            <!-- ═══ Document Uploads ═══ -->
            <div class="section-divider"><i class="bi bi-folder2-open me-2"></i>Document Uploads</div>
            <div class="row g-3 mb-4">

              <!-- Aadhar Card — required -->
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold doc-required">Aadhar Card</label>
                <div class="doc-upload-box" onclick="document.getElementById('inp_aadhar').click()">
                  <input type="file" id="inp_aadhar" name="doc_aadhar"
                         accept=".pdf,.jpg,.jpeg,.png" onchange="showFileName(this,'lbl_aadhar')">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-credit-card-2-front fs-4 text-primary"></i>
                    <div>
                      <div class="doc-label">Upload Aadhar Card</div>
                      <div class="doc-hint">PDF, JPG or PNG · Max 5 MB</div>
                    </div>
                    <i class="bi bi-upload ms-auto text-muted"></i>
                  </div>
                  <div class="doc-name" id="lbl_aadhar"></div>
                </div>
              </div>

              <!-- Passport Photo — required -->
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold doc-required">Passport Size Photo</label>
                <div class="doc-upload-box" onclick="document.getElementById('inp_photo').click()">
                  <input type="file" id="inp_photo" name="doc_photo"
                         accept=".jpg,.jpeg,.png,.webp" onchange="showFileName(this,'lbl_photo')">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-bounding-box fs-4 text-success"></i>
                    <div>
                      <div class="doc-label">Upload Passport Photo</div>
                      <div class="doc-hint">JPG, PNG or WEBP · Max 5 MB · White background preferred</div>
                    </div>
                    <i class="bi bi-upload ms-auto text-muted"></i>
                  </div>
                  <div class="doc-name" id="lbl_photo"></div>
                </div>
              </div>

              <!-- Birth Certificate — optional -->
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Birth Certificate <span class="text-muted fw-normal">(optional)</span></label>
                <div class="doc-upload-box" onclick="document.getElementById('inp_birth').click()">
                  <input type="file" id="inp_birth" name="doc_birth_cert"
                         accept=".pdf,.jpg,.jpeg,.png" onchange="showFileName(this,'lbl_birth')">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-text fs-4 text-info"></i>
                    <div>
                      <div class="doc-label">Upload Birth Certificate</div>
                      <div class="doc-hint">PDF, JPG or PNG · Max 5 MB</div>
                    </div>
                    <i class="bi bi-upload ms-auto text-muted"></i>
                  </div>
                  <div class="doc-name" id="lbl_birth"></div>
                </div>
              </div>

              <!-- Transfer / Leaving Certificate — optional -->
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Transfer / Leaving Certificate <span class="text-muted fw-normal">(optional)</span></label>
                <div class="doc-upload-box" onclick="document.getElementById('inp_tc').click()">
                  <input type="file" id="inp_tc" name="doc_transfer_cert"
                         accept=".pdf,.jpg,.jpeg,.png" onchange="showFileName(this,'lbl_tc')">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-check fs-4 text-warning"></i>
                    <div>
                      <div class="doc-label">Upload TC / Previous Mark Sheet</div>
                      <div class="doc-hint">PDF, JPG or PNG · Max 5 MB</div>
                    </div>
                    <i class="bi bi-upload ms-auto text-muted"></i>
                  </div>
                  <div class="doc-name" id="lbl_tc"></div>
                </div>
              </div>

            </div>

            <!-- Fee acknowledgement inside form -->
            <?php if ($adm_fee_on && $adm_fee_amount > 0): ?>
            <div class="alert alert-warning py-2 small mb-3">
              <i class="bi bi-exclamation-triangle me-1"></i>
              By submitting, you acknowledge that a
              <strong><?= sanitize($adm_fee_type) ?></strong> of
              <strong><?= $currency_sym . number_format($adm_fee_amount, 2) ?></strong>
              is payable within <?= $adm_fee_days ?> days of submission.
              <?= $adm_fee_note ? nl2br(sanitize($adm_fee_note)) : '' ?>
            </div>
            <?php endif; ?>

            <!-- Terms -->
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" id="terms" required>
              <label class="form-check-label small" for="terms">
                I confirm that all information and documents provided are accurate and I agree
                to the school's terms and conditions<?= $adm_fee_on ? ', including the applicable admission fee.' : '.' ?>
              </label>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
              <i class="bi bi-send me-2"></i>Submit Application
            </button>
          </form>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<footer class="text-center py-3 text-muted small bg-white border-top mt-4">
  <?= sanitize(get_setting('footer_text', '')) ?> &nbsp;|&nbsp;
  <a href="<?= SITE_URL ?>/auth/login.php" class="text-muted">Login</a>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showFileName(input, labelId) {
  const label = document.getElementById(labelId);
  if (input.files && input.files[0]) {
    label.textContent = '✓ ' + input.files[0].name;
    input.closest('.doc-upload-box').style.borderColor = '#1a6dcc';
    input.closest('.doc-upload-box').style.background  = '#e8f1fc';
  } else {
    label.textContent = '';
    input.closest('.doc-upload-box').style.borderColor = '';
    input.closest('.doc-upload-box').style.background  = '';
  }
}

// Terms checkbox validation
document.getElementById('admissionForm').addEventListener('submit', function(e) {
  if (!document.getElementById('terms').checked) {
    e.preventDefault();
    alert('Please agree to the terms and conditions to submit.');
  }
});
</script>
</body>
</html>
