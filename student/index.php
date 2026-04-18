<?php
/**
 * student/index.php — Ultra Premium Student Dashboard
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('student');

$student = get_student_by_user_id((int)$_SESSION['user_id']);
if (!$student) {
    set_flash('error', 'Student record not found. Contact your administrator.');
    redirect(SITE_URL . '/auth/logout.php');
}

$page_title = 'My Dashboard';
$breadcrumb = [['label' => 'Dashboard', 'active' => true]];

// Attendance summary (all time + this month)
$att_all   = attendance_summary((int)$student['id']);
$att_month = attendance_summary((int)$student['id'], date('Y-m'));

// Latest 3 published results
$results_stmt = $pdo->prepare(
    "SELECT r.*, e.name as exam_name, e.type
     FROM results r JOIN exams e ON r.exam_id = e.id
     WHERE r.student_id = ? AND r.published = 1
     ORDER BY r.created_at DESC LIMIT 3"
);
$results_stmt->execute([$student['id']]);
$results = $results_stmt->fetchAll();

// Fee summary
$fee_stmt = $pdo->prepare(
    "SELECT
        COALESCE(SUM(amount),0)                                            AS total,
        COALESCE(SUM(CASE WHEN status='paid'    THEN amount ELSE 0 END),0) AS paid,
        COALESCE(SUM(CASE WHEN status='pending' THEN amount ELSE 0 END),0) AS pending
     FROM fees WHERE student_id = ?"
);
$fee_stmt->execute([$student['id']]);
$fees = $fee_stmt->fetch();

// Upcoming exams
$exams_stmt = $pdo->prepare(
    "SELECT e.* FROM exams e
     WHERE (e.class_id = ? OR e.class_id IS NULL)
       AND e.status IN ('upcoming','ongoing')
     ORDER BY e.start_date ASC LIMIT 3"
);
$exams_stmt->execute([$student['class_id'] ?? 0]);
$upcoming_exams = $exams_stmt->fetchAll();

// Notifications
$notifs = get_notifications(5);

// Parent info
$parent = null;
if ($student['parent_id']) {
    $ps = $pdo->prepare("SELECT * FROM parents WHERE id = ?");
    $ps->execute([$student['parent_id']]);
    $parent = $ps->fetch();
}

// Subject marks (latest exam)
$latest_marks = [];
if (!empty($results)) {
    $ms = $pdo->prepare(
        "SELECT m.marks_obtained, m.max_marks, sub.name as subject_name, sub.code
         FROM marks m JOIN subjects sub ON m.subject_id = sub.id
         WHERE m.exam_id = ? AND m.student_id = ?
         ORDER BY sub.name"
    );
    $ms->execute([$results[0]['exam_id'], $student['id']]);
    $latest_marks = $ms->fetchAll();
}

include INCLUDES_PATH . 'header.php';
?>

<style>
.stu-hero {
    background: linear-gradient(135deg, rgba(79,142,247,0.15) 0%, rgba(123,94,167,0.1) 100%);
    border: 1px solid rgba(79,142,247,0.2);
    border-radius: 20px;
    padding: 28px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    animation: fadeInUp 0.5s ease both;
}
.stu-hero::before {
    content: '';
    position: absolute;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(79,142,247,0.15), transparent 70%);
    top: -60px; right: -40px;
}
.stu-avatar {
    width: 72px; height: 72px;
    border-radius: 18px;
    object-fit: cover;
    border: 2px solid rgba(245,166,35,0.4);
    flex-shrink: 0;
}
.stu-avatar-ph {
    width: 72px; height: 72px;
    border-radius: 18px;
    background: linear-gradient(135deg, #4f8ef7, #7b5ea7);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Playfair Display', serif;
    font-size: 1.8rem; font-weight: 800; color: #fff;
    flex-shrink: 0;
    border: 2px solid rgba(245,166,35,0.3);
}
.metric-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 16px;
    padding: 20px;
    text-align: center;
    transition: all 0.25s;
    animation: fadeInUp 0.4s ease both;
}
.metric-card:hover {
    background: rgba(245,166,35,0.05);
    border-color: rgba(245,166,35,0.2);
    transform: translateY(-3px);
}
.metric-num {
    font-family: 'Playfair Display', serif;
    font-size: 2rem; font-weight: 800; color: #fff;
    line-height: 1;
}
.metric-lbl { font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; }
.metric-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    margin: 0 auto 12px;
}
.result-row {
    display: flex; align-items: center;
    padding: 14px 0;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    gap: 14px;
    animation: fadeInUp 0.4s ease both;
}
.result-row:last-child { border-bottom: none; }
.result-bar {
    flex: 1;
    height: 6px;
    background: rgba(255,255,255,0.06);
    border-radius: 99px;
    overflow: hidden;
}
.result-bar-fill {
    height: 100%;
    border-radius: 99px;
    transition: width 1s cubic-bezier(0.4,0,0.2,1);
}
.subject-pill {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px;
    border-radius: 8px;
    font-size: 0.78rem; font-weight: 600;
    margin: 3px;
}
.exam-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 14px;
    padding: 16px;
    display: flex; align-items: center; gap: 14px;
    margin-bottom: 10px;
    transition: all 0.2s;
}
.exam-card:hover { background: rgba(79,142,247,0.08); border-color: rgba(79,142,247,0.25); }
.att-ring {
    width: 80px; height: 80px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Playfair Display', serif;
    font-size: 1.3rem; font-weight: 800;
    position: relative;
    flex-shrink: 0;
}
</style>

<!-- Hero welcome banner -->
<div class="stu-hero">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <?php if ($student['photo']): ?>
            <img src="<?= get_upload_url($student['photo']) ?>" class="stu-avatar" alt="Photo">
        <?php else: ?>
            <div class="stu-avatar-ph"><?= strtoupper(substr($student['name'], 0, 1)) ?></div>
        <?php endif; ?>
        <div class="flex-grow-1">
            <div style="font-size:0.75rem;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:4px;">
                Welcome back
            </div>
            <h3 style="font-family:'Playfair Display',serif;font-weight:800;color:#fff;margin:0 0 4px;">
                <?= sanitize($student['name']) ?>
            </h3>
            <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                <span style="background:rgba(245,166,35,0.15);border:1px solid rgba(245,166,35,0.3);border-radius:6px;padding:3px 10px;font-size:0.75rem;color:#f5a623;font-weight:600;">
                    <?= sanitize($student['student_id']) ?>
                </span>
                <span style="background:rgba(79,142,247,0.15);border:1px solid rgba(79,142,247,0.25);border-radius:6px;padding:3px 10px;font-size:0.75rem;color:#93c5fd;">
                    <?= sanitize(($student['class_name'] ?? '') . ($student['section_name'] ? ' — Sec '.$student['section_name'] : '')) ?>
                </span>
                <?php if ($student['blood_group']): ?>
                <span style="background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.25);border-radius:6px;padding:3px 10px;font-size:0.75rem;color:#f87171;">
                    🩸 <?= sanitize($student['blood_group']) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= SITE_URL ?>/student/profile.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-pencil me-1"></i>Edit Profile
            </a>
            <a href="<?= SITE_URL ?>/student/id-card.php" class="btn btn-outline-secondary btn-sm" target="_blank">
                <i class="bi bi-card-text me-1"></i>ID Card
            </a>
        </div>
    </div>
</div>

<!-- Metric cards -->
<div class="row g-3 mb-4">
    <!-- Attendance -->
    <div class="col-6 col-lg-3">
        <div class="metric-card" style="animation-delay:0.05s;">
            <div class="metric-icon" style="background:rgba(34,197,94,0.15);color:#22c55e;">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div class="metric-num" style="color:#22c55e;"><?= $att_all['percentage'] ?? 0 ?>%</div>
            <div class="metric-lbl">Overall Attendance</div>
            <div style="font-size:0.7rem;color:rgba(255,255,255,0.3);margin-top:4px;">
                <?= $att_all['present'] ?? 0 ?> present / <?= $att_all['total'] ?? 0 ?> days
            </div>
        </div>
    </div>
    <!-- This month attendance -->
    <div class="col-6 col-lg-3">
        <div class="metric-card" style="animation-delay:0.1s;">
            <div class="metric-icon" style="background:rgba(79,142,247,0.15);color:#4f8ef7;">
                <i class="bi bi-calendar3"></i>
            </div>
            <div class="metric-num" style="color:#4f8ef7;"><?= $att_month['percentage'] ?? 0 ?>%</div>
            <div class="metric-lbl">This Month</div>
            <div style="font-size:0.7rem;color:rgba(255,255,255,0.3);margin-top:4px;">
                <?= date('F') ?> <?= date('Y') ?>
            </div>
        </div>
    </div>
    <!-- Latest grade -->
    <div class="col-6 col-lg-3">
        <div class="metric-card" style="animation-delay:0.15s;">
            <div class="metric-icon" style="background:rgba(245,166,35,0.15);color:#f5a623;">
                <i class="bi bi-award"></i>
            </div>
            <?php if (!empty($results)): ?>
            <div class="metric-num" style="color:#f5a623;"><?= $results[0]['grade'] ?></div>
            <div class="metric-lbl">Latest Grade</div>
            <div style="font-size:0.7rem;color:rgba(255,255,255,0.3);margin-top:4px;">
                <?= sanitize(mb_substr($results[0]['exam_name'], 0, 20)) ?>
            </div>
            <?php else: ?>
            <div class="metric-num" style="color:#f5a623;">—</div>
            <div class="metric-lbl">No Results Yet</div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Fee pending -->
    <div class="col-6 col-lg-3">
        <div class="metric-card" style="animation-delay:0.2s;<?= $fees['pending'] > 0 ? 'border-color:rgba(239,68,68,0.25);' : '' ?>">
            <div class="metric-icon" style="background:rgba(239,68,68,0.15);color:#ef4444;">
                <i class="bi bi-receipt"></i>
            </div>
            <div class="metric-num" style="font-size:1.4rem;color:<?= $fees['pending'] > 0 ? '#ef4444' : '#22c55e' ?>;">
                <?= currency_format((float)$fees['pending']) ?>
            </div>
            <div class="metric-lbl">Fee Pending</div>
            <?php if ($fees['pending'] > 0): ?>
            <a href="<?= SITE_URL ?>/student/fees.php" class="btn btn-danger btn-sm mt-2" style="font-size:0.72rem;padding:4px 12px;">
                Pay Now
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Main content grid -->
<div class="row g-3 mb-3">

    <!-- Results section -->
    <div class="col-12 col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span style="display:flex;align-items:center;gap:8px;">
                    <i class="bi bi-award" style="color:#f5a623;"></i>
                    Academic Results
                </span>
                <a href="<?= SITE_URL ?>/student/results.php" class="btn btn-outline-primary btn-sm">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($results)): ?>
                <div style="text-align:center;padding:40px 0;color:var(--text-muted);">
                    <i class="bi bi-clipboard2-x" style="font-size:2.5rem;display:block;margin-bottom:12px;"></i>
                    No published results yet
                </div>
                <?php else: foreach ($results as $r):
                    $col = match(true) {
                        $r['percentage'] >= 75 => ['#22c55e','rgba(34,197,94,0.15)'],
                        $r['percentage'] >= 50 => ['#f5a623','rgba(245,166,35,0.15)'],
                        default                => ['#ef4444','rgba(239,68,68,0.15)'],
                    };
                ?>
                <div class="result-row">
                    <div style="flex-shrink:0;width:44px;height:44px;border-radius:12px;background:<?= $col[1] ?>;display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:800;color:<?= $col[0] ?>;">
                        <?= $r['grade'] ?>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:600;color:#fff;font-size:0.875rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= sanitize($r['exam_name']) ?>
                        </div>
                        <div style="font-size:0.72rem;color:var(--text-muted);"><?= $r['type'] ?></div>
                        <div class="result-bar mt-2">
                            <div class="result-bar-fill" style="width:<?= $r['percentage'] ?>%;background:<?= $col[0] ?>;"></div>
                        </div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <div style="font-weight:700;color:<?= $col[0] ?>;font-family:'Playfair Display',serif;"><?= $r['percentage'] ?>%</div>
                        <div style="font-size:0.7rem;color:var(--text-muted);"><?= $r['total_marks'] ?>/<?= $r['max_marks'] ?></div>
                        <span class="badge bg-<?= $r['result']==='Pass'?'success':'danger' ?>" style="font-size:0.65rem;margin-top:2px;"><?= $r['result'] ?></span>
                    </div>
                    <a href="<?= SITE_URL ?>/student/results.php?report=<?= $r['exam_id'] ?>"
                       class="btn btn-outline-primary btn-sm" target="_blank" style="flex-shrink:0;">
                        <i class="bi bi-printer"></i>
                    </a>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Right column -->
    <div class="col-12 col-lg-5">

        <!-- Attendance visual -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-calendar-check" style="color:#22c55e;margin-right:8px;"></i>Attendance Overview</div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-4">
                    <!-- Circular indicator -->
                    <div style="position:relative;flex-shrink:0;">
                        <svg width="80" height="80" viewBox="0 0 80 80">
                            <circle cx="40" cy="40" r="34" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="8"/>
                            <circle cx="40" cy="40" r="34" fill="none"
                                    stroke="<?= ($att_all['percentage']??0)>=75 ? '#22c55e' : (($att_all['percentage']??0)>=60 ? '#f5a623' : '#ef4444') ?>"
                                    stroke-width="8"
                                    stroke-linecap="round"
                                    stroke-dasharray="<?= round(2*3.14159*34 * ($att_all['percentage']??0) / 100, 1) ?> <?= round(2*3.14159*34, 1) ?>"
                                    stroke-dashoffset="<?= round(2*3.14159*34 * 0.25, 1) ?>"
                                    transform="rotate(-90 40 40)"/>
                        </svg>
                        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-size:0.95rem;font-weight:800;color:#fff;">
                            <?= $att_all['percentage'] ?? 0 ?>%
                        </div>
                    </div>
                    <div style="flex:1;">
                        <?php foreach ([
                            ['Present', $att_all['present']??0,  '#22c55e'],
                            ['Absent',  $att_all['absent']??0,   '#ef4444'],
                            ['Late',    $att_all['late']??0,     '#f5a623'],
                        ] as [$lbl,$val,$col]): ?>
                        <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                            <span style="font-size:0.8rem;color:var(--text-muted);display:flex;align-items:center;gap:6px;">
                                <span style="width:8px;height:8px;border-radius:50%;background:<?= $col ?>;display:inline-block;flex-shrink:0;"></span>
                                <?= $lbl ?>
                            </span>
                            <strong style="font-size:0.8rem;color:<?= $col ?>;"><?= $val ?></strong>
                        </div>
                        <?php endforeach; ?>
                        <?php if (($att_all['percentage']??0) < 75): ?>
                        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:8px;padding:8px;font-size:0.72rem;color:#f87171;margin-top:8px;">
                            ⚠ Below 75% — attend regularly
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming exams -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-clipboard2-data" style="color:#4f8ef7;margin-right:8px;"></i>Upcoming Exams</span>
            </div>
            <div class="card-body" style="padding:14px;">
                <?php if (empty($upcoming_exams)): ?>
                <p style="color:var(--text-muted);font-size:0.85rem;text-align:center;padding:16px 0;margin:0;">No upcoming exams</p>
                <?php else: foreach ($upcoming_exams as $ex):
                    $status_color = $ex['status']==='ongoing' ? '#22c55e' : '#4f8ef7';
                ?>
                <div class="exam-card">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(79,142,247,0.15);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;">📝</div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:600;font-size:0.85rem;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= sanitize($ex['name']) ?></div>
                        <div style="font-size:0.72rem;color:var(--text-muted);"><?= $ex['type'] ?><?= $ex['start_date'] ? ' · '.format_date($ex['start_date']) : '' ?></div>
                    </div>
                    <span style="background:<?= $status_color ?>1a;color:<?= $status_color ?>;border:1px solid <?= $status_color ?>3a;border-radius:6px;padding:2px 8px;font-size:0.68rem;font-weight:600;flex-shrink:0;text-transform:capitalize;">
                        <?= $ex['status'] ?>
                    </span>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Parent info -->
        <?php if ($parent): ?>
        <div class="card">
            <div class="card-header"><i class="bi bi-people" style="color:#7b5ea7;margin-right:8px;"></i>Parent / Guardian</div>
            <div class="card-body">
                <div style="display:flex;align-items:center;gap:14px;">
                    <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#7b5ea7,#5b3f87);display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;">👤</div>
                    <div>
                        <div style="font-weight:600;color:#fff;"><?= sanitize($parent['name']) ?></div>
                        <div style="font-size:0.78rem;color:var(--text-muted);"><?= sanitize($parent['relation'] ?? 'Parent') ?></div>
                    </div>
                </div>
                <?php if ($parent['phone']): ?>
                <div style="margin-top:12px;padding:10px 14px;background:rgba(255,255,255,0.03);border-radius:10px;font-size:0.82rem;color:var(--text-muted);">
                    <i class="bi bi-telephone me-2"></i><?= sanitize($parent['phone']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Latest marks by subject -->
<?php if (!empty($latest_marks)): ?>
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-bar-chart-line" style="color:#f5a623;margin-right:8px;"></i>
        Latest Exam — Subject Performance
        <span style="font-size:0.78rem;color:var(--text-muted);font-weight:400;margin-left:8px;">
            <?= sanitize($results[0]['exam_name'] ?? '') ?>
        </span>
    </div>
    <div class="card-body">
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            <?php foreach ($latest_marks as $m):
                $pct   = $m['max_marks'] > 0 ? round(($m['marks_obtained'] / $m['max_marks']) * 100) : 0;
                $grade = calculate_grade($pct);
                $col   = match(true) {
                    $pct >= 75 => ['#22c55e','rgba(34,197,94,0.15)','rgba(34,197,94,0.25)'],
                    $pct >= 50 => ['#f5a623','rgba(245,166,35,0.12)','rgba(245,166,35,0.25)'],
                    default    => ['#ef4444','rgba(239,68,68,0.12)','rgba(239,68,68,0.25)'],
                };
            ?>
            <div class="subject-pill" style="background:<?= $col[1] ?>;border:1px solid <?= $col[2] ?>;color:<?= $col[0] ?>;">
                <i class="bi bi-book" style="font-size:0.8rem;"></i>
                <?= sanitize($m['subject_name']) ?>
                <strong><?= $m['marks_obtained'] ?>/<?= $m['max_marks'] ?></strong>
                <span style="background:<?= $col[2] ?>;border-radius:4px;padding:1px 5px;font-size:0.65rem;"><?= $grade ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Notifications -->
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-bell" style="color:#4f8ef7;margin-right:8px;"></i>Recent Notifications</span>
        <a href="<?= SITE_URL ?>/notifications/mark-all.php" style="font-size:0.75rem;color:var(--gold);">Mark all read</a>
    </div>
    <div class="card-body" style="padding:8px 0;">
        <?php if (empty($notifs)): ?>
        <p style="text-align:center;color:var(--text-muted);padding:24px;margin:0;font-size:0.85rem;">No notifications</p>
        <?php else: foreach ($notifs as $n):
            $ni = ['success'=>'check-circle','warning'=>'exclamation-triangle','danger'=>'x-circle','info'=>'info-circle'][$n['type']] ?? 'info-circle';
            $nc = ['success'=>'#22c55e','warning'=>'#f5a623','danger'=>'#ef4444','info'=>'#4f8ef7'][$n['type']] ?? '#4f8ef7';
        ?>
        <div style="display:flex;align-items:flex-start;gap:12px;padding:12px 20px;border-bottom:1px solid rgba(255,255,255,0.04);<?= !$n['is_read'] ? 'background:rgba(255,255,255,0.02);' : '' ?>">
            <i class="bi bi-<?= $ni ?>" style="color:<?= $nc ?>;font-size:0.9rem;margin-top:2px;flex-shrink:0;"></i>
            <div style="flex:1;min-width:0;">
                <div style="font-size:0.82rem;font-weight:<?= !$n['is_read'] ? '600' : '400' ?>;color:#fff;"><?= sanitize($n['title']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);"><?= sanitize(mb_substr($n['message'],0,80)) ?></div>
            </div>
            <div style="font-size:0.68rem;color:rgba(255,255,255,0.25);white-space:nowrap;flex-shrink:0;"><?= date('d M',strtotime($n['created_at'])) ?></div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
