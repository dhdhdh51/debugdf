<?php
/**
 * student/results.php
 * Student portal — view published results & print own report card
 * No redirect to admin. Completely self-contained.
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('student');

$student = get_student_by_user_id((int)$_SESSION['user_id']);
if (!$student) {
    set_flash('error', 'Student record not found.');
    redirect(SITE_URL . '/auth/login.php');
}

// ── Print/report-card mode ────────────────────────────────
$print_exam = sanitize_int($_GET['report'] ?? 0);
if ($print_exam) {
    // Load exam
    $stmt = $pdo->prepare(
        "SELECT e.*, c.name as class_name
         FROM exams e LEFT JOIN classes c ON e.class_id=c.id WHERE e.id=? LIMIT 1"
    );
    $stmt->execute([$print_exam]);
    $exam = $stmt->fetch();

    // Verify result is published for this student
    $stmt = $pdo->prepare(
        "SELECT * FROM results WHERE exam_id=? AND student_id=? AND published=1 LIMIT 1"
    );
    $stmt->execute([$print_exam, $student['id']]);
    $result = $stmt->fetch();

    if (!$exam || !$result) {
        set_flash('error', 'Report card not available.');
        redirect(SITE_URL . '/student/results.php');
    }

    // Load marks
    $stmt = $pdo->prepare(
        "SELECT m.*, sub.name as subject_name, sub.code as subject_code
         FROM marks m JOIN subjects sub ON m.subject_id=sub.id
         WHERE m.exam_id=? AND m.student_id=?
         ORDER BY sub.name"
    );
    $stmt->execute([$print_exam, $student['id']]);
    $marks = $stmt->fetchAll();

    // Load parent
    $stmt = $pdo->prepare(
        "SELECT p.name as parent_name, p.phone as parent_phone
         FROM parents p JOIN students s ON s.parent_id=p.id WHERE s.id=? LIMIT 1"
    );
    $stmt->execute([$student['id']]);
    $parent_row = $stmt->fetch();

    $site_name = get_setting('site_name', 'School ERP');
    $site_logo = get_setting('site_logo', '');
    $contact   = get_setting('contact_address', '');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width,initial-scale=1">
      <title>Report Card — <?= sanitize($student['name']) ?></title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
      <style>
        body{font-family:Arial,sans-serif;background:#f0f0f0}
        .report-card{max-width:800px;margin:20px auto;background:#fff;border:2px solid #0d6efd;border-radius:8px;overflow:hidden}
        .rc-header{background:linear-gradient(135deg,#0d6efd,#0099ff);color:#fff;padding:20px}
        .rc-title{font-size:22px;font-weight:700;margin:0}
        .rc-sub{font-size:12px;opacity:.85}
        .rc-body{padding:20px}
        .info-table td{padding:4px 8px;font-size:13px}
        .info-table td:first-child{font-weight:600;color:#555;width:120px}
        .marks-table th{background:#f8f9fa;font-size:13px}
        .marks-table td{font-size:13px}
        .grade-pill{display:inline-block;padding:2px 12px;border-radius:20px;font-weight:700;font-size:13px}
        .watermark{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) rotate(-30deg);font-size:80px;color:rgba(13,110,253,.04);pointer-events:none;z-index:0;font-weight:700;white-space:nowrap}
        @media print{
          body{background:#fff!important}
          .no-print{display:none!important}
          .report-card{border:2px solid #333!important;box-shadow:none!important;margin:0!important}
          @page{margin:10mm}
        }
      </style>
    </head>
    <body>
    <div class="no-print text-center py-3 bg-light border-bottom">
      <button onclick="window.print()" class="btn btn-primary me-2">
        <i class="bi bi-printer me-1"></i>Print / Save PDF
      </button>
      <a href="<?= SITE_URL ?>/student/results.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Results
      </a>
    </div>

    <div class="watermark"><?= sanitize($site_name) ?></div>

    <div class="report-card shadow">
      <!-- Header -->
      <div class="rc-header d-flex align-items-center gap-3">
        <?php if ($site_logo): ?>
          <img src="<?= get_upload_url($site_logo) ?>" height="60" class="rounded" alt="Logo">
        <?php else: ?>
          <div style="width:60px;height:60px;background:rgba(255,255,255,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:28px">🏫</div>
        <?php endif; ?>
        <div>
          <div class="rc-title"><?= sanitize($site_name) ?></div>
          <div class="rc-sub"><?= sanitize($contact) ?></div>
          <div class="rc-sub fw-semibold mt-1">STUDENT REPORT CARD — <?= get_setting('academic_year','2025-2026') ?></div>
        </div>
        <?php if ($student['photo']): ?>
        <img src="<?= get_upload_url($student['photo']) ?>" class="ms-auto rounded"
             style="width:70px;height:70px;object-fit:cover;border:3px solid rgba(255,255,255,.5)" alt="Photo">
        <?php endif; ?>
      </div>

      <!-- Body -->
      <div class="rc-body">
        <!-- Student info -->
        <div class="row g-0 mb-4">
          <div class="col-12 col-md-6">
            <table class="info-table w-100">
              <tr><td>Student Name:</td><td class="fw-bold"><?= sanitize($student['name']) ?></td></tr>
              <tr><td>Student ID:</td><td><code><?= sanitize($student['student_id']) ?></code></td></tr>
              <tr><td>Class:</td><td><?= sanitize(($student['class_name']??'') . ($student['section_name']?' — '.$student['section_name']:'')) ?></td></tr>
              <tr><td>Exam:</td><td><?= sanitize($exam['name']) ?> (<?= sanitize($exam['type']) ?>)</td></tr>
            </table>
          </div>
          <div class="col-12 col-md-6">
            <table class="info-table w-100">
              <tr><td>Date of Birth:</td><td><?= format_date($student['dob']) ?></td></tr>
              <tr><td>Gender:</td><td><?= sanitize($student['gender'] ?? '—') ?></td></tr>
              <tr><td>Parent/Guardian:</td><td><?= sanitize($parent_row['parent_name'] ?? '—') ?></td></tr>
              <tr><td>Exam Date:</td><td><?= format_date($exam['start_date']) ?></td></tr>
            </table>
          </div>
        </div>

        <!-- Marks table -->
        <h6 class="fw-bold border-bottom pb-2 mb-3">Subject-wise Performance</h6>
        <div class="table-responsive mb-4">
          <table class="table table-bordered marks-table">
            <thead>
              <tr class="table-primary">
                <th>#</th><th>Subject</th><th>Code</th>
                <th class="text-center">Max</th><th class="text-center">Obtained</th>
                <th class="text-center">%</th><th class="text-center">Grade</th><th>Remarks</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($marks)): ?>
              <tr><td colspan="8" class="text-center text-muted py-3">No marks recorded</td></tr>
              <?php else:
                $pass_pct = get_pass_percentage();
                foreach ($marks as $i => $m):
                  $pct    = $m['max_marks'] > 0 ? round(($m['marks_obtained'] / $m['max_marks']) * 100, 1) : 0;
                  $grade  = calculate_grade($pct);
                  $remark = $pct >= 75 ? 'Excellent' : ($pct >= 60 ? 'Good' : ($pct >= 33 ? 'Average' : 'Needs Improvement'));
              ?>
              <tr>
                <td class="text-center"><?= $i+1 ?></td>
                <td class="fw-semibold"><?= sanitize($m['subject_name']) ?></td>
                <td class="text-muted"><code><?= sanitize($m['subject_code'] ?? '') ?></code></td>
                <td class="text-center"><?= $m['max_marks'] ?></td>
                <td class="text-center fw-bold <?= $pct < $pass_pct ? 'text-danger' : 'text-success' ?>">
                  <?= $m['marks_obtained'] ?>
                </td>
                <td class="text-center"><?= $pct ?>%</td>
                <td class="text-center">
                  <span class="grade-pill bg-<?= get_grade_color($grade) ?> bg-opacity-15 text-<?= get_grade_color($grade) ?> border border-<?= get_grade_color($grade) ?>">
                    <?= $grade ?>
                  </span>
                </td>
                <td class="text-muted small"><?= $remark ?></td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Summary -->
        <div class="row g-3 mb-4">
          <?php foreach ([
            ['Total Marks',    $result['total_marks'].'/'.$result['max_marks'], 'bg-light', ''],
            ['Percentage',     $result['percentage'].'%',                       'bg-primary bg-opacity-10', 'text-primary'],
            ['Overall Grade',  $result['grade'],                                'bg-'.get_grade_color($result['grade']).' bg-opacity-10', 'text-'.get_grade_color($result['grade'])],
            ['Result',         $result['result'],                               'bg-'.($result['result']==='Pass'?'success':'danger').' bg-opacity-10', 'text-'.($result['result']==='Pass'?'success':'danger')],
          ] as [$label,$val,$bg,$tc]): ?>
          <div class="col-6 col-md-3">
            <div class="text-center p-3 <?= $bg ?> rounded">
              <div class="fs-4 fw-bold <?= $tc ?>"><?= $val ?></div>
              <div class="small text-muted"><?= $label ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Signature -->
        <div class="row mt-5 pt-3">
          <div class="col-4 text-center"><div class="border-top pt-2"><small>Class Teacher</small></div></div>
          <div class="col-4 text-center"><div class="border-top pt-2"><small>Examiner</small></div></div>
          <div class="col-4 text-center"><div class="border-top pt-2"><small>Principal</small></div></div>
        </div>

        <div class="text-center mt-4 text-muted small border-top pt-3">
          <?= sanitize(get_setting('footer_text','')) ?> | Generated: <?= date('d M Y H:i') ?>
        </div>
      </div>
    </div>
    <script>
      // auto-print if ?print=1
      if (new URLSearchParams(location.search).get('print') === '1') window.print();
    </script>
    </body>
    </html>
    <?php
    exit; // stop here — no need for dashboard layout
}

// ── Results list page ─────────────────────────────────────
$results = $pdo->prepare(
    "SELECT r.*, e.name as exam_name, e.type, e.start_date
     FROM results r
     JOIN exams e ON r.exam_id = e.id
     WHERE r.student_id = ? AND r.published = 1
     ORDER BY r.created_at DESC"
);
$results->execute([$student['id']]);
$results = $results->fetchAll();

$page_title = 'My Results';
$breadcrumb = [
    ['label' => 'Dashboard', 'url' => SITE_URL . '/student/'],
    ['label' => 'Results', 'active' => true],
];
include INCLUDES_PATH . 'header.php';
?>

<?php if (empty($results)): ?>
<div class="card border-0 shadow-sm">
  <div class="card-body text-center py-5 text-muted">
    <i class="bi bi-award display-4 d-block mb-3"></i>
    <h5>No results published yet</h5>
    <p class="small">Results will appear here once your teacher publishes them.</p>
  </div>
</div>
<?php else: ?>

<!-- Summary strip -->
<div class="row g-3 mb-3">
  <?php
  $best_pct = max(array_column($results, 'percentage'));
  $avg_pct  = round(array_sum(array_column($results, 'percentage')) / count($results), 1);
  $pass_cnt = count(array_filter($results, fn($r) => $r['result'] === 'Pass'));
  $cards = [
    ['Exams Taken',  count($results), 'clipboard2-check', 'primary'],
    ['Best %',       $best_pct.'%',   'trophy',           'warning'],
    ['Average %',    $avg_pct.'%',    'bar-chart',        'info'],
    ['Pass Count',   $pass_cnt,       'check-circle',     'success'],
  ];
  foreach ($cards as [$label, $val, $icon, $color]):
  ?>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm text-center py-3 h-100">
      <i class="bi bi-<?= $icon ?> text-<?= $color ?> fs-2 mb-2 d-block"></i>
      <div class="fs-4 fw-bold text-<?= $color ?>"><?= $val ?></div>
      <div class="small text-muted"><?= $label ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Result cards -->
<?php foreach ($results as $r):
  $subject_marks = $pdo->prepare(
      "SELECT m.marks_obtained, m.max_marks, sub.name as subject_name
       FROM marks m JOIN subjects sub ON m.subject_id = sub.id
       WHERE m.exam_id = ? AND m.student_id = ?"
  );
  $subject_marks->execute([$r['exam_id'], $student['id']]);
  $subject_marks = $subject_marks->fetchAll();
?>
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h6 class="fw-bold mb-0"><?= sanitize($r['exam_name']) ?></h6>
      <small class="text-muted"><?= $r['type'] ?><?= $r['start_date'] ? ' | '.format_date($r['start_date']) : '' ?></small>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <span class="badge bg-<?= get_grade_color($r['grade'] ?? 'F') ?> px-3 py-2">
        Grade: <?= $r['grade'] ?>
      </span>
      <span class="badge bg-<?= $r['result'] === 'Pass' ? 'success' : 'danger' ?> px-3 py-2">
        <?= $r['result'] ?>
      </span>
      <a href="<?= SITE_URL ?>/student/results.php?report=<?= $r['exam_id'] ?>"
         class="btn btn-outline-primary btn-sm">
        <i class="bi bi-printer me-1"></i>Report Card
      </a>
      <a href="<?= SITE_URL ?>/student/results.php?report=<?= $r['exam_id'] ?>&print=1"
         class="btn btn-primary btn-sm" target="_blank">
        <i class="bi bi-download me-1"></i>Print / PDF
      </a>
    </div>
  </div>
  <div class="card-body">
    <div class="row g-3 align-items-center">
      <div class="col-12 col-md-6">
        <div class="d-flex justify-content-between mb-1">
          <small class="fw-semibold">
            Total: <?= $r['total_marks'] ?>/<?= $r['max_marks'] ?>
          </small>
          <small class="fw-bold"><?= $r['percentage'] ?>%</small>
        </div>
        <div class="progress" style="height:10px">
          <div class="progress-bar bg-<?= get_grade_color($r['grade'] ?? 'F') ?>"
               style="width:<?= $r['percentage'] ?>%"></div>
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div class="d-flex flex-wrap gap-1 justify-content-md-end">
          <?php foreach ($subject_marks as $sm):
            $pct   = $sm['max_marks'] > 0 ? round(($sm['marks_obtained'] / $sm['max_marks']) * 100) : 0;
            $grade = calculate_grade($pct);
          ?>
          <span class="badge bg-<?= get_grade_color($grade) ?> bg-opacity-15 text-<?= get_grade_color($grade) ?> border px-2 py-1"
                style="font-size:.7rem">
            <?= sanitize($sm['subject_name']) ?>: <?= $sm['marks_obtained'] ?>
          </span>
          <?php endforeach; ?>
          <?php if (empty($subject_marks)): ?>
          <span class="text-muted small">No subject marks available</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php include INCLUDES_PATH . 'footer.php'; ?>
