<?php
/**
 * admin/settings/admission-fee.php
 * Toggle whether a fee is charged at admission, set amount & fee type.
 * Linked from Admin → Settings tab.
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('admin');

$page_title = 'Admission Fee Settings';
$breadcrumb = [
    ['label' => 'Dashboard', 'url' => SITE_URL . '/admin/'],
    ['label' => 'Settings',  'url' => SITE_URL . '/admin/settings/'],
    ['label' => 'Admission Fee', 'active' => true],
];

$upsert = $pdo->prepare(
    "INSERT INTO settings (setting_key, setting_value)
     VALUES (?, ?)
     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();

    // Checkbox — unchecked = not posted
    $enabled    = isset($_POST['admission_fee_enabled']) ? '1' : '0';
    $amount     = sanitize_float($_POST['admission_fee_amount']   ?? 0);
    $fee_type   = sanitize($_POST['admission_fee_type']           ?? 'Admission Fee');
    $due_days   = sanitize_int($_POST['admission_fee_due_days']   ?? 30);
    $note       = sanitize($_POST['admission_fee_note']           ?? '');

    $upsert->execute(['admission_fee_enabled',  $enabled]);
    $upsert->execute(['admission_fee_amount',   (string)$amount]);
    $upsert->execute(['admission_fee_type',     $fee_type]);
    $upsert->execute(['admission_fee_due_days', (string)$due_days]);
    $upsert->execute(['admission_fee_note',     $note]);

    set_flash('success', 'Admission fee settings saved.');
    redirect(SITE_URL . '/admin/settings/admission-fee.php');
}

// Load current values
$s = [];
$rows = $pdo->query(
    "SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'admission_fee%'"
)->fetchAll();
foreach ($rows as $r) { $s[$r['setting_key']] = $r['setting_value']; }

function sf(array $s, string $k, string $d = ''): string {
    return htmlspecialchars($s[$k] ?? $d, ENT_QUOTES, 'UTF-8');
}

// Recent applications with fee status
$recent_apps = $pdo->query(
    "SELECT a.application_id, a.name, a.status as app_status, a.created_at,
            f.id as fee_id, f.amount, f.status as fee_status, f.invoice_no
     FROM admissions a
     LEFT JOIN fees f ON f.student_id IS NULL AND f.invoice_no LIKE CONCAT('ADM-', a.application_id, '%')
     ORDER BY a.created_at DESC LIMIT 10"
)->fetchAll();

include INCLUDES_PATH . 'header.php';
?>

<!-- Current status banner -->
<div class="alert alert-<?= ($s['admission_fee_enabled'] ?? '0') === '1' ? 'success' : 'secondary' ?> d-flex align-items-center gap-3 mb-4">
  <i class="bi bi-<?= ($s['admission_fee_enabled'] ?? '0') === '1' ? 'toggle-on fs-2 text-success' : 'toggle-off fs-2' ?>"></i>
  <div>
    <strong>Admission Fee is currently
      <?= ($s['admission_fee_enabled'] ?? '0') === '1' ? 'ENABLED' : 'DISABLED' ?>
    </strong><br>
    <small class="opacity-75">
      <?php if (($s['admission_fee_enabled'] ?? '0') === '1'): ?>
        Applicants will be charged <?= get_setting('currency_symbol','₹') . number_format((float)($s['admission_fee_amount'] ?? 0), 2) ?>
        as "<?= sf($s, 'admission_fee_type', 'Admission Fee') ?>" when their admission is
        <strong>approved</strong>.
      <?php else: ?>
        No fee is charged to applicants. Toggle ON below to enable.
      <?php endif; ?>
    </small>
  </div>
</div>

<div class="row g-4">
  <!-- Settings form -->
  <div class="col-12 col-lg-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 fw-semibold">
        <i class="bi bi-sliders me-2 text-primary"></i>Admission Fee Configuration
      </div>
      <div class="card-body">
        <form method="POST">
          <?= csrf_field() ?>

          <!-- Toggle -->
          <div class="form-check form-switch mb-4">
            <input class="form-check-input" type="checkbox" role="switch"
                   name="admission_fee_enabled" id="feeEnabled" style="width:3em;height:1.5em;"
                   <?= ($s['admission_fee_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold fs-6 ms-2" for="feeEnabled">
              Enable Admission Fee
            </label>
            <div class="form-text">When ON, a fee invoice is auto-created for every <strong>approved</strong> applicant.</div>
          </div>

          <div id="feeFields" <?= ($s['admission_fee_enabled'] ?? '0') !== '1' ? 'style="opacity:.5;pointer-events:none"' : '' ?>>

            <div class="mb-3">
              <label class="form-label fw-semibold">Fee Type Label</label>
              <input type="text" name="admission_fee_type" class="form-control"
                     value="<?= sf($s, 'admission_fee_type', 'Admission Fee') ?>"
                     placeholder="e.g. Registration Fee, Admission Fee">
              <div class="form-text">This appears as the fee type in the student's invoice.</div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Amount (<?= get_setting('currency_symbol','₹') ?>)</label>
              <div class="input-group">
                <span class="input-group-text"><?= get_setting('currency_symbol','₹') ?></span>
                <input type="number" name="admission_fee_amount" class="form-control"
                       value="<?= sf($s, 'admission_fee_amount', '500') ?>"
                       min="0" step="0.01" placeholder="500.00">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Payment Due (days after approval)</label>
              <div class="input-group">
                <input type="number" name="admission_fee_due_days" class="form-control"
                       value="<?= sf($s, 'admission_fee_due_days', '30') ?>"
                       min="1" max="365">
                <span class="input-group-text">days</span>
              </div>
              <div class="form-text">Due date = approval date + this many days.</div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Note / Instructions (shown on admission form)</label>
              <textarea name="admission_fee_note" class="form-control" rows="3"
                        placeholder="e.g. Please pay the registration fee within 30 days of approval to confirm your seat."><?= sf($s, 'admission_fee_note') ?></textarea>
            </div>
          </div><!-- /#feeFields -->

          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save me-2"></i>Save Settings
          </button>
          <a href="<?= SITE_URL ?>/admin/settings/" class="btn btn-outline-secondary ms-2">
            Back to Settings
          </a>
        </form>
      </div>
    </div>
  </div>

  <!-- Info / how it works -->
  <div class="col-12 col-lg-6">
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white border-0 fw-semibold">
        <i class="bi bi-info-circle me-2 text-info"></i>How It Works
      </div>
      <div class="card-body">
        <ol class="mb-0 lh-lg">
          <li>Toggle <strong>Enable Admission Fee</strong> to ON.</li>
          <li>Set the fee amount and label (e.g. "Registration Fee").</li>
          <li>When a student <strong>applies</strong> online, the admission form shows a note about the fee.</li>
          <li>When admin <strong>approves</strong> an admission, a fee invoice is automatically created for the student.</li>
          <li>The invoice appears in <strong>Admin → Fees</strong> and in the student's fee portal.</li>
          <li>Student can pay online via PayU or the school can mark it paid manually.</li>
        </ol>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 fw-semibold d-flex justify-content-between">
        <span><i class="bi bi-file-earmark-person me-2 text-warning"></i>Recent Admissions</span>
        <a href="<?= SITE_URL ?>/admin/admissions/" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th>App ID</th><th>Name</th><th>Status</th><th>Fee</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // Fresh query for display
              $recent = $pdo->query(
                  "SELECT a.application_id, a.name, a.status, a.created_at
                   FROM admissions a ORDER BY a.created_at DESC LIMIT 8"
              )->fetchAll();
              if (empty($recent)): ?>
              <tr><td colspan="4" class="text-center text-muted py-3">No admissions yet</td></tr>
              <?php else: foreach ($recent as $ap):
                // Check if a fee was auto-created for this application
                // We tag fees with meta in remarks: 'ADM:<application_id>'
                $fee_chk = $pdo->prepare(
                    "SELECT id, status, amount FROM fees WHERE invoice_no LIKE ? LIMIT 1"
                );
                $fee_chk->execute(['ADM-'.$ap['application_id'].'%']);
                $adm_fee = $fee_chk->fetch();
              ?>
              <tr>
                <td><code class="text-warning small"><?= sanitize($ap['application_id']) ?></code></td>
                <td class="fw-semibold small"><?= sanitize($ap['name']) ?></td>
                <td>
                  <span class="badge bg-<?= match($ap['status']){'approved'=>'success','rejected'=>'danger',default=>'warning'} ?> small">
                    <?= ucfirst($ap['status']) ?>
                  </span>
                </td>
                <td>
                  <?php if ($adm_fee): ?>
                    <span class="badge bg-<?= $adm_fee['status']==='paid'?'success':'warning' ?> small">
                      <?= get_setting('currency_symbol','₹') . number_format((float)$adm_fee['amount'],0) ?>
                      — <?= ucfirst($adm_fee['status']) ?>
                    </span>
                  <?php elseif ($ap['status'] === 'approved'): ?>
                    <span class="text-muted small">—</span>
                  <?php else: ?>
                    <span class="text-muted small">Pending</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Toggle fields opacity
document.getElementById('feeEnabled').addEventListener('change', function() {
  const fields = document.getElementById('feeFields');
  fields.style.opacity       = this.checked ? '1' : '0.5';
  fields.style.pointerEvents = this.checked ? '' : 'none';
});
</script>

<?php include INCLUDES_PATH . 'footer.php'; ?>
