<?php
/**
 * teacher/index.php — Ultra Premium Teacher Dashboard
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('teacher');

$teacher = get_teacher_by_user_id((int)$_SESSION['user_id']);
if (!$teacher) {
    set_flash('error', 'Teacher record not found. Contact administrator.');
    redirect(SITE_URL . '/auth/logout.php');
}

$page_title = 'Teacher Dashboard';
$breadcrumb = [['label' => 'Dashboard', 'active' => true]];

$class_id = (int)($teacher['class_id'] ?? 0);

// Student count in my class
$student_count = 0;
if ($class_id) {
    $sc = $pdo->prepare("SELECT COUNT(*) FROM students WHERE class_id=? AND status='active'");
    $sc->execute([$class_id]);
    $student_count = (int)$sc->fetchColumn();
}

// Today's attendance for my class
$today_present = $today_absent = $today_late = 0;
if ($class_id) {
    $ta = $pdo->query(
        "SELECT status, COUNT(*) as cnt FROM attendance
         WHERE class_id={$class_id} AND date=CURDATE() GROUP BY status"
    )->fetchAll(PDO::FETCH_KEY_PAIR);
    $today_present = (int)($ta['Present'] ?? 0);
    $today_absent  = (int)($ta['Absent']  ?? 0);
    $today_late    = (int)($ta['Late']    ?? 0);
}

// Students in class (for quick list)
$students = [];
if ($class_id) {
    $ss = $pdo->prepare(
        "SELECT s.id, s.name, s.student_id, s.photo,
                (SELECT status FROM attendance WHERE student_id=s.id AND date=CURDATE() LIMIT 1) as today_att
         FROM students s WHERE s.class_id=? AND s.status='active'
         ORDER BY s.name LIMIT 10"
    );
    $ss->execute([$class_id]);
    $students = $ss->fetchAll();
}

// Recent exams relevant to teacher's class
$exams = $pdo->query(
    "SELECT * FROM exams WHERE (class_id={$class_id} OR class_id IS NULL)
     AND status IN ('upcoming','ongoing')
     ORDER BY start_date ASC LIMIT 5"
)->fetchAll();

// Marks entered by teacher (recent)
$recent_marks = $pdo->query(
    "SELECT COUNT(*) FROM marks m
     JOIN students s ON m.student_id=s.id
     WHERE s.class_id={$class_id}"
)->fetchColumn();

// Attendance this week for class
$week_att = [];
if ($class_id) {
    $wa = $pdo->query(
        "SELECT date, SUM(status='Present') as p, SUM(status='Absent') as a
         FROM attendance WHERE class_id={$class_id}
           AND date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         GROUP BY date ORDER BY date"
    )->fetchAll();
    $week_att = $wa;
}

$notifs = get_notifications(5);

include INCLUDES_PATH . 'header.php';
?>

<style>
.tch-hero {
    background: linear-gradient(135deg, rgba(34,197,94,0.12), rgba(20,184,166,0.08));
    border: 1px solid rgba(34,197,94,0.2);
    border-radius: 20px; padding: 28px; margin-bottom: 24px;
    position: relative; overflow: hidden;
    animation: fadeInUp 0.4s ease both;
}
.tch-hero::before {
    content:'';position:absolute;width:200px;height:200px;border-radius:50%;
    background:radial-gradient(circle,rgba(34,197,94,0.12),transparent 70%);
    top:-60px;right:-40px;
}
.tch-avatar {
    width:72px;height:72px;border-radius:18px;object-fit:cover;
    border:2px solid rgba(34,197,94,0.4);flex-shrink:0;
}
.tch-avatar-ph {
    width:72px;height:72px;border-radius:18px;
    background:linear-gradient(135deg,#22c55e,#16a34a);
    display:flex;align-items:center;justify-content:center;
    font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:800;color:#fff;
    flex-shrink:0;border:2px solid rgba(34,197,94,0.3);
}
.stu-row {
    display:flex;align-items:center;gap:12px;padding:10px 0;
    border-bottom:1px solid rgba(255,255,255,0.05);
    transition:all 0.2s;
}
.stu-row:last-child{border-bottom:none;}
.stu-row:hover{background:rgba(255,255,255,0.02);border-radius:8px;padding-left:8px;}
.att-dot {
    width:8px;height:8px;border-radius:50%;flex-shrink:0;
}
.quick-action {
    background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);
    border-radius:14px;padding:20px;text-align:center;
    text-decoration:none;transition:all 0.25s;display:block;
}
.quick-action:hover {
    background:rgba(34,197,94,0.08);border-color:rgba(34,197,94,0.3);
    transform:translateY(-3px);
}
.quick-action i { font-size:1.6rem;display:block;margin-bottom:10px; }
.quick-action .lbl { font-size:0.82rem;font-weight:600;color:#fff; }
</style>

<!-- Teacher Hero -->
<div class="tch-hero">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <?php if ($teacher['photo']): ?>
            <img src="<?= get_upload_url($teacher['photo']) ?>" class="tch-avatar" alt="">
        <?php else: ?>
            <div class="tch-avatar-ph"><?= strtoupper(substr($teacher['name'], 0, 1)) ?></div>
        <?php endif; ?>
        <div class="flex-grow-1">
            <div style="font-size:0.72rem;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:4px;">Teacher</div>
            <h3 style="font-family:'Playfair Display',serif;font-weight:800;color:#fff;margin:0 0 6px;">
                <?= sanitize($teacher['name']) ?>
            </h3>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                <span style="background:rgba(34,197,94,0.15);border:1px solid rgba(34,197,94,0.3);border-radius:6px;padding:3px 10px;font-size:0.75rem;color:#4ade80;font-weight:600;">
                    <?= sanitize($teacher['teacher_id'] ?? 'N/A') ?>
                </span>
                <?php if ($teacher['subject_name']): ?>
                <span style="background:rgba(245,166,35,0.12);border:1px solid rgba(245,166,35,0.25);border-radius:6px;padding:3px 10px;font-size:0.75rem;color:#f5a623;">
                    📚 <?= sanitize($teacher['subject_name']) ?>
                </span>
                <?php endif; ?>
                <?php if ($teacher['class_name']): ?>
                <span style="background:rgba(79,142,247,0.12);border:1px solid rgba(79,142,247,0.25);border-radius:6px;padding:3px 10px;font-size:0.75rem;color:#93c5fd;">
                    🏫 <?= sanitize($teacher['class_name']) ?>
                </span>
                <?php endif; ?>
                <?php if ($teacher['qualification']): ?>
                <span style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:6px;padding:3px 10px;font-size:0.75rem;color:var(--text-muted);">
                    🎓 <?= sanitize($teacher['qualification']) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <?php $tstats = [
        ['My Students', $student_count,   'people-fill',    '#4f8ef7','rgba(79,142,247,0.15)'],
        ['Present Today',$today_present,  'calendar-check', '#22c55e','rgba(34,197,94,0.15)'],
        ['Absent Today', $today_absent,   'calendar-x',     '#ef4444','rgba(239,68,68,0.15)'],
        ['Marks Entered',$recent_marks,   'pencil-square',  '#f5a623','rgba(245,166,35,0.15)'],
    ];
    foreach ($tstats as $i => [$lbl,$val,$icon,$col,$bg]): ?>
    <div class="col-6 col-lg-3">
        <div class="metric-card" style="background:<?= $bg ?>;border-color:<?= $col ?>3a;animation-delay:<?= $i*0.05 ?>s;">
            <div class="metric-icon" style="background:<?= $col ?>20;color:<?= $col ?>;">
                <i class="bi bi-<?= $icon ?>"></i>
            </div>
            <div class="metric-num" style="color:<?= $col ?>;"><?= number_format($val) ?></div>
            <div class="metric-lbl"><?= $lbl ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Quick actions -->
<div class="row g-3 mb-4">
    <?php $qactions = [
        ['/teacher/attendance.php', 'bi-calendar-check-fill', '#22c55e', 'Mark Attendance', 'Record today\'s roll call'],
        ['/teacher/marks.php',      'bi-pencil-square',       '#f5a623', 'Enter Marks',      'Add exam scores'],
        ['/teacher/report-cards.php','bi-award',              '#4f8ef7', 'Report Cards',     'View & print reports'],
    ];
    foreach ($qactions as [$url,$icon,$col,$title,$sub]): ?>
    <div class="col-12 col-md-4">
        <a href="<?= SITE_URL . $url ?>" class="quick-action" style="border-color:<?= $col ?>20;">
            <i class="bi <?= $icon ?>" style="color:<?= $col ?>;"></i>
            <div class="lbl"><?= $title ?></div>
            <div style="font-size:0.72rem;color:var(--text-muted);margin-top:4px;"><?= $sub ?></div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
    <!-- My students -->
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span style="display:flex;align-items:center;gap:8px;">
                    <i class="bi bi-people-fill" style="color:#4f8ef7;"></i>
                    My Students
                    <span class="badge bg-primary" style="font-size:0.7rem;"><?= $student_count ?></span>
                </span>
                <a href="<?= SITE_URL ?>/teacher/attendance.php" class="btn btn-outline-primary btn-sm">
                    Take Attendance
                </a>
            </div>
            <div class="card-body" style="padding:14px 20px;">
                <?php if (!$class_id): ?>
                <p style="color:var(--text-muted);text-align:center;padding:20px 0;margin:0;font-size:0.85rem;">No class assigned. Contact admin.</p>
                <?php elseif (empty($students)): ?>
                <p style="color:var(--text-muted);text-align:center;padding:20px 0;margin:0;font-size:0.85rem;">No students in your class yet.</p>
                <?php else: foreach ($students as $s):
                    $att_status = $s['today_att'] ?? null;
                    $att_col = match($att_status) {
                        'Present' => '#22c55e',
                        'Absent'  => '#ef4444',
                        'Late'    => '#f5a623',
                        default   => 'rgba(255,255,255,0.2)',
                    };
                ?>
                <div class="stu-row">
                    <?php if ($s['photo']): ?>
                        <img src="<?= get_upload_url($s['photo']) ?>" class="rounded-circle"
                             width="34" height="34" style="object-fit:cover;border:1px solid rgba(255,255,255,0.1);flex-shrink:0;">
                    <?php else: ?>
                        <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#4f8ef7,#7b5ea7);display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;color:#fff;flex-shrink:0;">
                            <?= strtoupper(substr($s['name'],0,1)) ?>
                        </div>
                    <?php endif; ?>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:0.85rem;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= sanitize($s['name']) ?>
                        </div>
                        <div style="font-size:0.7rem;color:var(--text-muted);"><?= sanitize($s['student_id']) ?></div>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;font-size:0.72rem;color:<?= $att_col ?>;flex-shrink:0;">
                        <span class="att-dot" style="background:<?= $att_col ?>;"></span>
                        <?= $att_status ?? 'Not marked' ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if ($student_count > 10): ?>
                <div style="text-align:center;padding-top:10px;">
                    <a href="<?= SITE_URL ?>/teacher/attendance.php" style="font-size:0.78rem;color:var(--gold);">
                        +<?= $student_count - 10 ?> more students →
                    </a>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Exams + Notifications -->
    <div class="col-12 col-lg-6">
        <!-- Upcoming exams -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-clipboard2-data" style="color:#f5a623;margin-right:8px;"></i>Upcoming Exams</div>
            <div class="card-body" style="padding:14px;">
                <?php if (empty($exams)): ?>
                <p style="color:var(--text-muted);text-align:center;padding:16px 0;margin:0;font-size:0.85rem;">No upcoming exams</p>
                <?php else: foreach ($exams as $ex):
                    $sc = $ex['status']==='ongoing' ? '#22c55e' : '#4f8ef7';
                ?>
                <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.05);">
                    <div style="width:36px;height:36px;border-radius:10px;background:rgba(79,142,247,0.12);display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;">📝</div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:0.85rem;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= sanitize($ex['name']) ?></div>
                        <div style="font-size:0.7rem;color:var(--text-muted);"><?= $ex['type'] ?><?= $ex['start_date'] ? ' · '.format_date($ex['start_date']) : '' ?></div>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex-shrink:0;">
                        <span style="background:<?= $sc ?>1a;color:<?= $sc ?>;border-radius:6px;padding:2px 8px;font-size:0.68rem;font-weight:600;text-transform:capitalize;"><?= $ex['status'] ?></span>
                        <a href="<?= SITE_URL ?>/teacher/marks.php?exam_id=<?= $ex['id'] ?>"
                           style="font-size:0.68rem;color:var(--gold);">Enter marks →</a>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Notifications -->
        <div class="card">
            <div class="card-header"><i class="bi bi-bell" style="color:#4f8ef7;margin-right:8px;"></i>Notifications</div>
            <div class="card-body" style="padding:8px 0;">
                <?php if (empty($notifs)): ?>
                <p style="color:var(--text-muted);text-align:center;padding:20px;margin:0;font-size:0.85rem;">No notifications</p>
                <?php else: foreach ($notifs as $n):
                    $nc = ['success'=>'#22c55e','warning'=>'#f5a623','danger'=>'#ef4444','info'=>'#4f8ef7'][$n['type']] ?? '#4f8ef7';
                ?>
                <div style="display:flex;gap:10px;padding:10px 16px;border-bottom:1px solid rgba(255,255,255,0.04);<?= !$n['is_read'] ? 'background:rgba(255,255,255,0.02);' : '' ?>">
                    <i class="bi bi-circle-fill" style="color:<?= $nc ?>;font-size:0.5rem;margin-top:6px;flex-shrink:0;"></i>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:0.8rem;color:#fff;font-weight:<?= !$n['is_read']?'600':'400'?>;"><?= sanitize($n['title']) ?></div>
                        <div style="font-size:0.7rem;color:var(--text-muted);"><?= date('d M, h:i A',strtotime($n['created_at'])) ?></div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* inherit metric-card from student dashboard */
.metric-card { background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:16px;padding:20px;text-align:center;transition:all 0.25s;animation:fadeInUp 0.4s ease both; }
.metric-card:hover { transform:translateY(-3px); }
.metric-num { font-family:'Playfair Display',serif;font-size:2rem;font-weight:800;color:#fff;line-height:1; }
.metric-lbl { font-size:0.75rem;color:var(--text-muted);margin-top:4px; }
.metric-icon { width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;margin:0 auto 12px; }
</style>

<?php include INCLUDES_PATH . 'footer.php'; ?>
