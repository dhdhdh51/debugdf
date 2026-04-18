<?php
/**
 * admin/admissions/index.php  (UPDATED)
 * — When admission_fee_enabled = 1 and admin approves an application,
 *   a fee invoice is auto-created for the student (if a student record exists).
 * — If no student record yet, the fee will be created when student is added.
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
require_once INCLUDES_PATH . 'mailer.php';
auth_guard('admin');

$page_title = 'Admissions';
$breadcrumb = [
    ['label' => 'Dashboard', 'url' => SITE_URL . '/admin/'],
    ['label' => 'Admissions', 'active' => true],
];

// ── Handle status update ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    csrf_protect();

    $aid     = sanitize_int($_POST['admission_id'] ?? 0);
    $status  = sanitize($_POST['status']           ?? '');
    $remarks = sanitize($_POST['remarks']          ?? '');

    if ($aid && in_array($status, ['approved', 'rejected', 'pending'])) {

        $pdo->prepare(
            "UPDATE admissions SET status=?, remarks=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?"
        )->execute([$status, $remarks, $_SESSION['user_id'], $aid]);

        // Fetch admission
        $stmt = $pdo->prepare("SELECT * FROM admissions WHERE id=?");
        $stmt->execute([$aid]);
        $adm = $stmt->fetch();

        // Send status email
        if ($adm && $adm['email']) {
            SchoolMailer::sendAdmissionStatus($adm['email'], $adm['name'], $status, $remarks);
        }

        // ── Auto-create fee invoice on approval ───────────────
        if ($status === 'approved' && $adm) {
            $fee_enabled = get_setting('admission_fee_enabled', '0');
            if ($fee_enabled === '1') {
                $fee_amount   = (float) get_setting('admission_fee_amount', '0');
                $fee_type     = get_setting('admission_fee_type', 'Admission Fee');
                $due_days     = (int)   get_setting('admission_fee_due_days', '30');

                if ($fee_amount > 0) {
                    // Check if fee already created for this application
                    $dup_check = $pdo->prepare(
                        "SELECT id FROM fees WHERE invoice_no LIKE ? LIMIT 1"
                    );
                    $dup_check->execute(['ADM-' . $adm['application_id'] . '%']);

                    if (!$dup_check->fetch()) {
                        // Try to find matching student record
                        $stu_stmt = $pdo->prepare(
                            "SELECT id, user_id FROM students WHERE name = ? AND status='active' ORDER BY created_at DESC LIMIT 1"
                        );
                        $stu_stmt->execute([$adm['name']]);
                        $stu = $stu_stmt->fetch();

                        $due_date   = date('Y-m-d', strtotime("+{$due_days} days"));
                        $invoice_no = 'ADM-' . $adm['application_id'] . '-' . date('His');

                        if ($stu) {
                            // Student exists — create proper fee record
                            $pdo->prepare(
                                "INSERT INTO fees (student_id, fee_type, amount, due_date, status, invoice_no, created_by)
                                 VALUES (?, ?, ?, ?, 'pending', ?, ?)"
                            )->execute([
                                $stu['id'], $fee_type, $fee_amount,
                                $due_date, $invoice_no, $_SESSION['user_id']
                            ]);

                            // In-app notification for student
                            create_notification(
                                $stu['user_id'] ?? null,
                                'student',
                                'Admission Fee Invoice',
                                "Invoice {$invoice_no} for {$fee_type}: " . currency_format($fee_amount),
                                'warning'
                            );

                            // Email student
                            $email_to = $stu['email'] ?? $adm['email'] ?? '';
                            if ($email_to) {
                                SchoolMailer::sendFeeInvoice(
                                    $email_to, $adm['name'],
                                    $invoice_no, $fee_amount, $due_date
                                );
                            }
                        } else {
                            // No student record yet — store as a placeholder with student_id = 0
                            // We use a temporary workaround: store in fees table with student_id=NULL
                            // and tag via invoice_no so admin can re-assign later.
                            // NOTE: This requires a nullable student_id in fees table (see SQL migration).
                            try {
                                $pdo->prepare(
                                    "INSERT INTO fees (student_id, fee_type, amount, due_date, status, invoice_no, created_by)
                                     VALUES (NULL, ?, ?, ?, 'pending', ?, ?)"
                                )->execute([
                                    $fee_type . ' [' . $adm['name'] . ']',
                                    $fee_amount, $due_date, $invoice_no, $_SESSION['user_id']
                                ]);
                            } catch (PDOException $e) {
                                // student_id NOT NULL constraint — skip placeholder, admin assigns manually
                                error_log('[Admission Fee] Could not create placeholder fee: ' . $e->getMessage());
                            }
                        }
                    }
                }
            }
        }
        // ── end auto-fee ──────────────────────────────────────

        set_flash('success', "Admission status updated to '{$status}'.");
    }
    redirect(SITE_URL . '/admin/admissions/');
}

// ── Filters ───────────────────────────────────────────────
$filter   = sanitize($_GET['status'] ?? '');
$search   = sanitize($_GET['q']      ?? '');
$per_page = 15;
$page_num = sanitize_int($_GET['page'] ?? 1);

$where  = ['1=1'];
$params = [];
if ($filter) {
    $where[]  = "a.status=?";
    $params[] = $filter;
}
if ($search) {
    $where[]  = "(a.name LIKE ? OR a.application_id LIKE ? OR a.email LIKE ?)";
    $like     = "%{$search}%";
    $params   = array_merge($params, [$like, $like, $like]);
}
$wh = implode(' AND ', $where);

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM admissions a WHERE $wh");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();
$pag   = paginate($total, $per_page, $page_num);

$stmt = $pdo->prepare(
    "SELECT a.*, c.name as class_name
     FROM admissions a
     LEFT JOIN classes c ON a.class_applying = c.id
     WHERE $wh ORDER BY a.created_at DESC
     LIMIT ? OFFSET ?"
);
$params[] = $per_page;
$params[] = $pag['offset'];
$stmt->execute($params);
$admissions = $stmt->fetchAll();

// Count by status
$counts = $pdo->query(
    "SELECT status, COUNT(*) as cnt FROM admissions GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

// Admission fee banner
$adm_fee_on     = get_setting('admission_fee_enabled', '0') === '1';
$adm_fee_amount = (float) get_setting('admission_fee_amount', '0');
$adm_fee_type   = get_setting('admission_fee_type', 'Admission Fee');

include INCLUDES_PATH . 'header.php';
?>

<!-- Admission fee status bar -->
<div class="alert alert-<?= $adm_fee_on ? 'success' : 'secondary' ?> d-flex align-items-center gap-2 py-2 mb-3">
  <i class="bi bi-<?= $adm_fee_on ? 'cash-stack text-success' : 'cash-stack' ?>"></i>
  <?php if ($adm_fee_on): ?>
    <span>
      <strong>Admission Fee Active:</strong>
      <?= get_setting('currency_symbol','₹') . number_format($adm_fee_amount, 2) ?>
      as "<?= sanitize($adm_fee_type) ?>" — auto-invoiced on approval.
    </span>
  <?php else: ?>
    <span>Admission fee is <strong>disabled</strong>.</span>
  <?php endif; ?>
  <a href="<?= SITE_URL ?>/admin/settings/admission-fee.php" class="btn btn-sm btn-outline-<?= $adm_fee_on ? 'success' : 'secondary' ?> ms-auto">
    <i class="bi bi-gear me-1"></i>Configure
  </a>
</div>

<!-- Status filter pills -->
<div class="d-flex gap-2 flex-wrap mb-3">
  <a href="<?= SITE_URL ?>/admin/admissions/"
     class="btn btn-sm btn-<?= $filter === '' ? 'primary' : 'outline-primary' ?>">
    All (<?= array_sum($counts) ?>)
  </a>
  <a href="?status=pending"
     class="btn btn-sm btn-<?= $filter === 'pending'  ? 'warning'  : 'outline-warning' ?>">
    Pending (<?= $counts['pending']  ?? 0 ?>)
  </a>
  <a href="?status=approved"
     class="btn btn-sm btn-<?= $filter === 'approved' ? 'success'  : 'outline-success' ?>">
    Approved (<?= $counts['approved'] ?? 0 ?>)
  </a>
  <a href="?status=rejected"
     class="btn btn-sm btn-<?= $filter === 'rejected' ? 'danger'   : 'outline-danger' ?>">
    Rejected (<?= $counts['rejected'] ?? 0 ?>)
  </a>
</div>

<!-- Search bar -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body py-2">
    <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
      <input type="hidden" name="status" value="<?= sanitize($filter) ?>">
      <div class="input-group input-group-sm flex-grow-1" style="max-width:300px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" name="q" class="form-control" placeholder="Search..." value="<?= sanitize($search) ?>">
      </div>
      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <a href="<?= SITE_URL ?>/admin/admissions/" class="btn btn-outline-secondary btn-sm">Clear</a>
    </form>
  </div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th><th>App ID</th><th>Name</th><th>Class</th>
            <th>Parent</th><th>Applied</th><th>Status</th><th>Fee</th><th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($admissions)): ?>
          <tr>
            <td colspan="9" class="text-center py-5 text-muted">
              <i class="bi bi-file-earmark-person display-6 d-block mb-2"></i>No admissions found.
            </td>
          </tr>
          <?php else: foreach ($admissions as $i => $a):
            // Check if fee was auto-created
            $fee_q = $pdo->prepare("SELECT status, amount FROM fees WHERE invoice_no LIKE ? LIMIT 1");
            $fee_q->execute(['ADM-'.$a['application_id'].'%']);
            $adm_fee_row = $fee_q->fetch();
          ?>
          <tr>
            <td class="text-muted small"><?= $pag['offset'] + $i + 1 ?></td>
            <td><code class="text-warning"><?= sanitize($a['application_id']) ?></code></td>
            <td>
              <div class="fw-semibold"><?= sanitize($a['name']) ?></div>
              <div class="small text-muted"><?= sanitize($a['email'] ?? '') ?></div>
            </td>
            <td><?= sanitize($a['class_name'] ?? '—') ?></td>
            <td>
              <div><?= sanitize($a['parent_name'] ?? '—') ?></div>
              <div class="small text-muted"><?= sanitize($a['parent_phone'] ?? '') ?></div>
            </td>
            <td class="small text-muted"><?= format_date($a['created_at']) ?></td>
            <td>
              <span class="badge bg-<?= match($a['status']) {
                'approved' => 'success', 'rejected' => 'danger', default => 'warning'
              } ?>">
                <?= ucfirst($a['status']) ?>
              </span>
            </td>
            <td>
              <?php if ($adm_fee_row): ?>
                <span class="badge bg-<?= $adm_fee_row['status'] === 'paid' ? 'success' : 'warning' ?> small">
                  <?= get_setting('currency_symbol','₹') . number_format((float)$adm_fee_row['amount'],0) ?>
                  — <?= ucfirst($adm_fee_row['status']) ?>
                </span>
              <?php elseif ($a['status'] === 'approved' && $adm_fee_on): ?>
                <span class="text-muted small">No student record</span>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <button class="btn btn-outline-primary btn-sm"
                      onclick="openReview(<?= $a['id'] ?>, '<?= addslashes(sanitize($a['name'])) ?>', '<?= $a['status'] ?>', '<?= addslashes(sanitize($a['remarks'] ?? '')) ?>')">
                <i class="bi bi-eye me-1"></i>Review
              </button>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?= pagination_links($pag, SITE_URL . '/admin/admissions/?status=' . $filter . '&q=' . urlencode($search)) ?>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Review Admission — <span id="modalName"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php if ($adm_fee_on && $adm_fee_amount > 0): ?>
        <div class="alert alert-info py-2 small mb-3">
          <i class="bi bi-cash-stack me-1"></i>
          Approving will auto-create a
          <strong><?= sanitize($adm_fee_type) ?></strong> invoice of
          <strong><?= get_setting('currency_symbol','₹') . number_format($adm_fee_amount, 2) ?></strong>
          for this student.
        </div>
        <?php endif; ?>
        <form method="POST" id="reviewForm">
          <?= csrf_field() ?>
          <input type="hidden" name="update_status"  value="1">
          <input type="hidden" name="admission_id"   id="modal_admission_id">

          <div class="mb-3">
            <label class="form-label fw-semibold">Decision</label>
            <div class="d-flex gap-3">
              <?php foreach (['approved', 'rejected', 'pending'] as $st): ?>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="status"
                       id="st_<?= $st ?>" value="<?= $st ?>">
                <label class="form-check-label" for="st_<?= $st ?>"><?= ucfirst($st) ?></label>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Remarks</label>
            <textarea name="remarks" id="modal_remarks" class="form-control" rows="3"
                      placeholder="Reason for decision..."></textarea>
          </div>

          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-check-circle me-2"></i>Submit Decision
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function openReview(id, name, status, remarks) {
  document.getElementById('modal_admission_id').value = id;
  document.getElementById('modalName').textContent    = name;
  document.getElementById('modal_remarks').value      = remarks;
  const radio = document.getElementById('st_' + status);
  if (radio) radio.checked = true;
  new bootstrap.Modal(document.getElementById('reviewModal')).show();
}
</script>

<?php include INCLUDES_PATH . 'footer.php'; ?>
