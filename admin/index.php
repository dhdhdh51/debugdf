<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('admin');

$page_title = 'Dashboard';
$breadcrumb = [['label'=>'Dashboard','active'=>true]];

$stats = get_dashboard_stats();

$recent_admissions = $pdo->query("SELECT a.*,c.name as class_name FROM admissions a LEFT JOIN classes c ON a.class_applying=c.id ORDER BY a.created_at DESC LIMIT 5")->fetchAll();
$recent_students   = $pdo->query("SELECT s.*,c.name as class_name FROM students s LEFT JOIN classes c ON s.class_id=c.id ORDER BY s.created_at DESC LIMIT 5")->fetchAll();
$fee_chart         = $pdo->query("SELECT DATE_FORMAT(created_at,'%b') as month, SUM(amount) as total FROM fees WHERE status='paid' AND YEAR(created_at)=YEAR(NOW()) GROUP BY MONTH(created_at) ORDER BY MONTH(created_at)")->fetchAll();
$today_present     = (int)$pdo->query("SELECT COUNT(*) FROM attendance WHERE date=CURDATE() AND status='Present'")->fetchColumn();
$today_absent      = (int)$pdo->query("SELECT COUNT(*) FROM attendance WHERE date=CURDATE() AND status='Absent'")->fetchColumn();

include INCLUDES_PATH . 'header.php';
?>

<!-- Stat cards -->
<div class="row g-3 mb-4">
  <?php $cards = [
    ['Students',           $stats['total_students'],   'people-fill',    'gold',  SITE_URL.'/admin/students/'],
    ['Teachers',           $stats['total_teachers'],   'person-badge',   'blue',  SITE_URL.'/admin/teachers/'],
    ['Parents',            $stats['total_parents'],    'people',         'green', SITE_URL.'/admin/parents/'],
    ['Pending Admissions', $stats['total_admissions'], 'file-earmark-person','purple',SITE_URL.'/admin/admissions/'],
  ];
  foreach ($cards as [$label,$val,$icon,$color,$link]): ?>
  <div class="col-6 col-lg-3">
    <a href="<?= $link ?>" class="text-decoration-none">
      <div class="stat-card">
        <div class="stat-icon <?= $color ?>">
          <i class="bi bi-<?= $icon ?>"></i>
        </div>
        <div>
          <div class="stat-value"><?= number_format($val) ?></div>
          <div class="stat-label"><?= $label ?></div>
        </div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>

  <!-- Fee collected -->
  <div class="col-6 col-lg-3">
    <div class="stat-card" style="background:linear-gradient(135deg,rgba(34,197,94,0.15),rgba(34,197,94,0.05));border-color:rgba(34,197,94,0.2);">
      <div class="stat-icon green"><i class="bi bi-cash-stack"></i></div>
      <div>
        <div class="stat-value" style="font-size:1.3rem;"><?= currency_format($stats['total_fees_paid']) ?></div>
        <div class="stat-label">Fees Collected</div>
      </div>
    </div>
  </div>
  <!-- Fee pending -->
  <div class="col-6 col-lg-3">
    <div class="stat-card" style="background:linear-gradient(135deg,rgba(239,68,68,0.15),rgba(239,68,68,0.05));border-color:rgba(239,68,68,0.2);">
      <div class="stat-icon red"><i class="bi bi-exclamation-circle"></i></div>
      <div>
        <div class="stat-value" style="font-size:1.3rem;"><?= currency_format($stats['total_fees_due']) ?></div>
        <div class="stat-label">Fees Pending</div>
      </div>
    </div>
  </div>
  <!-- Today attendance -->
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon teal"><i class="bi bi-calendar-check"></i></div>
      <div>
        <div class="stat-value">
          <?= $today_present ?>
          <span style="font-size:1rem;color:var(--text-muted);font-weight:400;">/<?= $today_present+$today_absent ?></span>
        </div>
        <div class="stat-label">Present Today</div>
      </div>
    </div>
  </div>
</div>

<!-- Charts row -->
<div class="row g-3 mb-4">
  <div class="col-12 col-lg-7">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-bar-chart" style="color:var(--gold);"></i>
        Monthly Fee Collection <?= date('Y') ?>
      </div>
      <div class="card-body">
        <canvas id="feeChart" height="200"></canvas>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-5">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-pie-chart" style="color:#22c55e;"></i>
        Today's Attendance
      </div>
      <div class="card-body d-flex flex-column align-items-center justify-content-center">
        <canvas id="attChart" height="170"></canvas>
        <div class="d-flex gap-4 mt-3">
          <div class="text-center">
            <div style="font-family:'Playfair Display',serif;font-size:1.6rem;font-weight:700;color:#22c55e;"><?= $today_present ?></div>
            <div style="font-size:0.75rem;color:var(--text-muted);">Present</div>
          </div>
          <div class="text-center">
            <div style="font-family:'Playfair Display',serif;font-size:1.6rem;font-weight:700;color:#ef4444;"><?= $today_absent ?></div>
            <div style="font-size:0.75rem;color:var(--text-muted);">Absent</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Tables row -->
<div class="row g-3 mb-4">
  <!-- Recent admissions -->
  <div class="col-12 col-lg-6">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span style="display:flex;align-items:center;gap:8px;">
          <i class="bi bi-file-earmark-person" style="color:var(--gold);"></i>Recent Admissions
        </span>
        <a href="<?= SITE_URL ?>/admin/admissions/" class="btn btn-outline-primary btn-sm">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>App ID</th><th>Name</th><th>Class</th><th>Status</th></tr></thead>
          <tbody>
            <?php if (empty($recent_admissions)): ?>
            <tr><td colspan="4" class="text-center py-4" style="color:var(--text-muted);">No admissions yet</td></tr>
            <?php else: foreach ($recent_admissions as $a): ?>
            <tr>
              <td><code><?= sanitize($a['application_id']) ?></code></td>
              <td style="font-weight:600;"><?= sanitize($a['name']) ?></td>
              <td style="color:var(--text-muted);font-size:0.82rem;"><?= sanitize($a['class_name']??'—') ?></td>
              <td><span class="badge bg-<?= match($a['status']){'approved'=>'success','rejected'=>'danger',default=>'warning'} ?>"><?= ucfirst($a['status']) ?></span></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <!-- Recent students -->
  <div class="col-12 col-lg-6">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span style="display:flex;align-items:center;gap:8px;">
          <i class="bi bi-people-fill" style="color:#4f8ef7;"></i>Recent Students
        </span>
        <a href="<?= SITE_URL ?>/admin/students/" class="btn btn-outline-primary btn-sm">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>ID</th><th>Name</th><th>Class</th><th>Status</th></tr></thead>
          <tbody>
            <?php if (empty($recent_students)): ?>
            <tr><td colspan="4" class="text-center py-4" style="color:var(--text-muted);">No students yet</td></tr>
            <?php else: foreach ($recent_students as $s): ?>
            <tr>
              <td><code><?= sanitize($s['student_id']) ?></code></td>
              <td>
                <?php if ($s['photo']): ?>
                  <img src="<?= get_upload_url($s['photo']) ?>" class="rounded-circle me-1" width="22" height="22" style="object-fit:cover;">
                <?php endif; ?>
                <span style="font-weight:600;"><?= sanitize($s['name']) ?></span>
              </td>
              <td style="color:var(--text-muted);font-size:0.82rem;"><?= sanitize($s['class_name']??'—') ?></td>
              <td><span class="badge bg-<?= $s['status']==='active'?'success':'secondary' ?>"><?= ucfirst($s['status']) ?></span></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Quick actions -->
<div class="card">
  <div class="card-header"><i class="bi bi-lightning" style="color:var(--gold);margin-right:8px;"></i>Quick Actions</div>
  <div class="card-body">
    <div class="d-flex flex-wrap gap-2">
      <?php $quick = [
        ['Add Student','person-plus','/admin/students/add.php'],
        ['Add Teacher','person-badge','/admin/teachers/add.php'],
        ['Add Fee','receipt','/admin/fees/add.php'],
        ['Attendance','calendar-check','/admin/attendance/'],
        ['New Exam','clipboard2-data','/admin/exams/add.php'],
        ['Send Notification','bell','/admin/notifications/'],
        ['Settings','gear','/admin/settings/'],
      ];
      foreach ($quick as [$lbl,$icon,$url]): ?>
      <a href="<?= SITE_URL . $url ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-<?= $icon ?> me-1"></i><?= $lbl ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#8892b0';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';

// Fee bar chart
new Chart(document.getElementById('feeChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($fee_chart,'month')) ?: '["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"]' ?>,
    datasets: [{
      label: 'Collected',
      data: <?= json_encode(array_column($fee_chart,'total')) ?: '[]' ?>,
      backgroundColor: 'rgba(245,166,35,0.5)',
      borderColor: '#f5a623',
      borderWidth: 1,
      borderRadius: 6,
    }]
  },
  options: {
    responsive:true,
    plugins:{ legend:{ display:false } },
    scales:{ y:{ beginAtZero:true, grid:{ color:'rgba(255,255,255,0.05)' } }, x:{ grid:{ display:false } } }
  }
});

// Attendance donut
new Chart(document.getElementById('attChart'), {
  type: 'doughnut',
  data: {
    labels: ['Present','Absent'],
    datasets: [{ data:[<?= $today_present ?>,<?= $today_absent ?>], backgroundColor:['#22c55e','#ef4444'], borderWidth:0 }]
  },
  options: { cutout:'72%', plugins:{ legend:{ position:'bottom' } }, responsive:true }
});
</script>

<?php include INCLUDES_PATH . 'footer.php'; ?>
