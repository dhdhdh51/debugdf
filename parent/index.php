<?php
/**
 * parent/index.php — Ultra Premium Parent Dashboard
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('parent');

$parent = get_parent_by_user_id((int)$_SESSION['user_id']);
if (!$parent) {
    set_flash('error', 'Parent record not found. Contact administrator.');
    redirect(SITE_URL . '/auth/logout.php');
}

$page_title = 'Parent Dashboard';
$breadcrumb = [['label' => 'Dashboard', 'active' => true]];

// All children of this parent
$children_stmt = $pdo->prepare(
    "SELECT s.*, c.name as class_name, sec.name as section_name
     FROM students s
     LEFT JOIN classes  c   ON s.class_id   = c.id
     LEFT JOIN sections sec ON s.section_id = sec.id
     WHERE s.parent_id = ? AND s.status = 'active'
     ORDER BY s.name"
);
$children_stmt->execute([$parent['id']]);
$children = $children_stmt->fetchAll();

// For each child, gather data
$child_data = [];
foreach ($children as $child) {
    $att = attendance_summary((int)$child['id']);

    // Fee
    $fs = $pdo->prepare("SELECT COALESCE(SUM(amount),0) as t, COALESCE(SUM(CASE WHEN status='pending' THEN amount ELSE 0 END),0) as p FROM fees WHERE student_id=?");
    $fs->execute([$child['id']]);
    $fee = $fs->fetch();

    // Latest result
    $lr = $pdo->prepare("SELECT r.*, e.name as exam_name FROM results r JOIN exams e ON r.exam_id=e.id WHERE r.student_id=? AND r.published=1 ORDER BY r.created_at DESC LIMIT 1");
    $lr->execute([$child['id']]);
    $latest_result = $lr->fetch();

    // Today's attendance
    $ta = $pdo->prepare("SELECT status FROM attendance WHERE student_id=? AND date=CURDATE() LIMIT 1");
    $ta->execute([$child['id']]);
    $today_att = $ta->fetchColumn();

    $child_data[$child['id']] = [
        'att'           => $att,
        'fee'           => $fee,
        'latest_result' => $latest_result,
        'today_att'     => $today_att ?: null,
    ];
}

// All fee invoices for all children
$child_ids = array_column($children, 'id');
$all_fees = [];
if (!empty($child_ids)) {
    $in = implode(',', array_fill(0, count($child_ids), '?'));
    $af = $pdo->prepare(
        "SELECT f.*, s.name as student_name FROM fees f
         JOIN students s ON f.student_id=s.id
         WHERE f.student_id IN ($in) ORDER BY f.created_at DESC LIMIT 10"
    );
    $af->execute($child_ids);
    $all_fees = $af->fetchAll();
}

$notifs = get_notifications(6);

include INCLUDES_PATH . 'header.php';
?>

<style>
.par-hero {
    background: linear-gradient(135deg, rgba(123,94,167,0.15), rgba(79,142,247,0.08));
    border: 1px solid rgba(123,94,167,0.25);
    border-radius: 20px; padding: 26px; margin-bottom: 24px;
    animation: fadeInUp 0.4s ease both;
    position: relative; overflow: hidden;
}
.par-hero::before {
    content:'';position:absolute;width:200px;height:200px;border-radius:50%;
    background:radial-gradient(circle,rgba(123,94,167,0.15),transparent 70%);
    top:-60px;right:-40px;
}
.child-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 20px; padding: 0;
    overflow: hidden;
    transition: all 0.3s;
    animation: fadeInUp 0.5s ease both;
}
.child-card:hover {
    border-color: rgba(245,166,35,0.25);
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    transform: translateY(-4px);
}
.child-card-header {
    padding: 20px;
    background: linear-gradient(135deg, rgba(79,142,247,0.1), rgba(123,94,167,0.08));
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.child-card-body { padding: 18px 20px; }
.child-stat {
    text-align: center;
    padding: 14px 8px;
    background: rgba(255,255,255,0.03);
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.05);
}
.child-stat .num {
    font-family: 'Playfair Display', serif;
    font-size: 1.4rem; font-weight: 800; color: #fff; line-height: 1;
}
.child-stat .lbl { font-size: 0.68rem; color: var(--text-muted); margin-top: 3px; }
.att-ring-sm {
    width: 54px; height: 54px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 50%;
    position: relative;
    flex-shrink: 0;
}
.today-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px; border-radius: 6px;
    font-size: 0.72rem; font-weight: 600;
    flex-shrink: 0;
}
</style>

<!-- Parent hero -->
<div class="par-hero">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div style="width:64px;height:64px;border-radius:18px;background:linear-gradient(135deg,#7b5ea7,#5b3f87);display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-size:1.6rem;font-weight:800;color:#fff;flex-shrink:0;border:2px solid rgba(123,94,167,0.4);">
            <?= strtoupper(substr($parent['name'],0,1)) ?>
        </div>
        <div class="flex-grow-1">
            <div style="font-size:0.72rem;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:4px;">
                <?= sanitize($parent['relation'] ?? 'Parent') ?>
            </div>
            <h3 style="font-family:'Playfair Display',serif;font-weight:800;color:#fff;margin:0 0 6px;">
                <?= sanitize($parent['name']) ?>
            </h3>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                <?php if ($parent['phone']): ?>
                <span style="font-size:0.78rem;color:rgba(255,255,255,0.5);">
                    <i class="bi bi-telephone me-1"></i><?= sanitize($parent['phone']) ?>
                </span>
                <?php endif; ?>
                <?php if ($parent['email']): ?>
                <span style="font-size:0.78rem;color:rgba(255,255,255,0.5);">
                    <i class="bi bi-envelope me-1"></i><?= sanitize($parent['email']) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-family:'Playfair Display',serif;font-size:2rem;font-weight:800;color:#f5a623;"><?= count($children) ?></div>
            <div style="font-size:0.75rem;color:var(--text-muted);">Child<?= count($children) !== 1 ? 'ren' : '' ?> enrolled</div>
        </div>
    </div>
</div>

<?php if (empty($children)): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-people" style="font-size:3rem;color:var(--text-muted);display:block;margin-bottom:16px;"></i>
        <h5 style="color:#fff;">No children linked to your account</h5>
        <p style="color:var(--text-muted);font-size:0.875rem;">Please contact the school administrator to link your children.</p>
    </div>
</div>
<?php else: ?>

<!-- Children cards -->
<h6 style="font-family:'Playfair Display',serif;color:rgba(255,255,255,0.5);font-size:0.75rem;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:14px;">
    My Children
</h6>
<div class="row g-4 mb-4">
    <?php foreach ($children as $i => $child):
        $cd         = $child_data[$child['id']];
        $att        = $cd['att'];
        $fee        = $cd['fee'];
        $lr         = $cd['latest_result'];
        $today_att  = $cd['today_att'];

        $att_col = match(true) {
            ($att['percentage'] ?? 0) >= 75 => '#22c55e',
            ($att['percentage'] ?? 0) >= 60 => '#f5a623',
            default                         => '#ef4444',
        };
        $today_col = match($today_att) {
            'Present' => ['#22c55e','rgba(34,197,94,0.15)'],
            'Absent'  => ['#ef4444','rgba(239,68,68,0.15)'],
            'Late'    => ['#f5a623','rgba(245,166,35,0.15)'],
            default   => ['rgba(255,255,255,0.3)','rgba(255,255,255,0.05)'],
        };
    ?>
    <div class="col-12 col-lg-6">
        <div class="child-card" style="animation-delay:<?= $i * 0.1 ?>s;">
            <!-- Card header -->
            <div class="child-card-header">
                <div style="display:flex;align-items:center;gap:14px;">
                    <?php if ($child['photo']): ?>
                        <img src="<?= get_upload_url($child['photo']) ?>"
                             style="width:56px;height:56px;border-radius:14px;object-fit:cover;border:2px solid rgba(245,166,35,0.3);flex-shrink:0;">
                    <?php else: ?>
                        <div style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,#4f8ef7,#7b5ea7);display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:800;color:#fff;flex-shrink:0;">
                            <?= strtoupper(substr($child['name'],0,1)) ?>
                        </div>
                    <?php endif; ?>
                    <div style="flex:1;min-width:0;">
                        <h5 style="font-family:'Playfair Display',serif;color:#fff;margin:0 0 4px;font-size:1.1rem;">
                            <?= sanitize($child['name']) ?>
                        </h5>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
                            <code style="font-size:0.72rem;color:#f5a623;background:rgba(245,166,35,0.1);border:1px solid rgba(245,166,35,0.2);border-radius:4px;padding:2px 6px;">
                                <?= sanitize($child['student_id']) ?>
                            </code>
                            <span style="font-size:0.72rem;color:rgba(255,255,255,0.5);">
                                <?= sanitize(($child['class_name']??'') . ($child['section_name'] ? ' — '.$child['section_name'] : '')) ?>
                            </span>
                        </div>
                    </div>
                    <!-- Today attendance badge -->
                    <div class="today-badge" style="background:<?= $today_col[1] ?>;color:<?= $today_col[0] ?>;border:1px solid <?= $today_col[0] ?>3a;">
                        <i class="bi bi-circle-fill" style="font-size:0.45rem;"></i>
                        <?= $today_att ?? 'No Record' ?>
                    </div>
                </div>
            </div>

            <!-- Stats row -->
            <div class="child-card-body">
                <div class="row g-2 mb-3">
                    <!-- Attendance -->
                    <div class="col-4">
                        <div class="child-stat">
                            <div class="num" style="color:<?= $att_col ?>;"><?= $att['percentage'] ?? 0 ?>%</div>
                            <div class="lbl">Attendance</div>
                        </div>
                    </div>
                    <!-- Latest grade -->
                    <div class="col-4">
                        <div class="child-stat">
                            <div class="num" style="color:#f5a623;"><?= $lr ? $lr['grade'] : '—' ?></div>
                            <div class="lbl"><?= $lr ? 'Latest Grade' : 'No Result' ?></div>
                        </div>
                    </div>
                    <!-- Fee pending -->
                    <div class="col-4">
                        <div class="child-stat">
                            <div class="num" style="font-size:1rem;color:<?= $fee['p']>0?'#ef4444':'#22c55e' ?>;">
                                <?= get_setting('currency_symbol','₹') . number_format((float)$fee['p'], 0) ?>
                            </div>
                            <div class="lbl">Fee Due</div>
                        </div>
                    </div>
                </div>

                <!-- Attendance bar -->
                <div style="margin-bottom:14px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                        <span style="font-size:0.72rem;color:var(--text-muted);">Attendance</span>
                        <span style="font-size:0.72rem;color:<?= $att_col ?>;font-weight:600;"><?= $att['percentage']??0 ?>%</span>
                    </div>
                    <div style="height:6px;background:rgba(255,255,255,0.06);border-radius:99px;overflow:hidden;">
                        <div style="height:100%;width:<?= $att['percentage']??0 ?>%;background:<?= $att_col ?>;border-radius:99px;transition:width 1s;"></div>
                    </div>
                    <div style="display:flex;gap:12px;margin-top:6px;">
                        <span style="font-size:0.68rem;color:#22c55e;"><i class="bi bi-check-circle"></i> <?= $att['present']??0 ?> Present</span>
                        <span style="font-size:0.68rem;color:#ef4444;"><i class="bi bi-x-circle"></i> <?= $att['absent']??0 ?> Absent</span>
                        <span style="font-size:0.68rem;color:#f5a623;"><i class="bi bi-dash-circle"></i> <?= $att['late']??0 ?> Late</span>
                    </div>
                </div>

                <!-- Latest result bar -->
                <?php if ($lr): ?>
                <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:10px 14px;margin-bottom:14px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <span style="font-size:0.75rem;color:var(--text-muted);">
                            <?= sanitize(mb_substr($lr['exam_name'],0,28)) ?>
                        </span>
                        <div style="display:flex;gap:6px;align-items:center;">
                            <span class="badge bg-<?= $lr['result']==='Pass'?'success':'danger' ?>" style="font-size:0.65rem;"><?= $lr['result'] ?></span>
                            <span style="font-family:'Playfair Display',serif;font-size:1rem;font-weight:800;color:#f5a623;"><?= $lr['grade'] ?></span>
                        </div>
                    </div>
                    <div style="height:5px;background:rgba(255,255,255,0.06);border-radius:99px;overflow:hidden;">
                        <div style="height:100%;width:<?= $lr['percentage'] ?>%;background:<?= $lr['percentage']>=75?'#22c55e':($lr['percentage']>=50?'#f5a623':'#ef4444') ?>;border-radius:99px;"></div>
                    </div>
                    <div style="font-size:0.68rem;color:var(--text-muted);margin-top:4px;"><?= $lr['percentage'] ?>% · <?= $lr['total_marks'] ?>/<?= $lr['max_marks'] ?></div>
                </div>
                <?php endif; ?>

                <!-- Actions -->
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="<?= SITE_URL ?>/parent/child.php?id=<?= $child['id'] ?>" class="btn btn-outline-primary btn-sm flex-fill">
                        <i class="bi bi-eye me-1"></i>Full Details
                    </a>
                    <a href="<?= SITE_URL ?>/parent/fees.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-receipt me-1"></i>Fees
                    </a>
                    <?php if ($lr): ?>
                    <a href="<?= SITE_URL ?>/admin/results/report-card.php?exam_id=<?= $lr['exam_id'] ?>&student_id=<?= $child['id'] ?>"
                       target="_blank" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-printer"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Fee invoices + Notifications -->
<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span style="display:flex;align-items:center;gap:8px;">
                    <i class="bi bi-receipt" style="color:#f5a623;"></i>Recent Fee Invoices
                </span>
                <a href="<?= SITE_URL ?>/parent/fees.php" class="btn btn-outline-primary btn-sm">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Invoice</th><th>Student</th><th>Amount</th><th>Due</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_fees)): ?>
                        <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:24px;">No fee records</td></tr>
                        <?php else: foreach ($all_fees as $f): ?>
                        <tr>
                            <td><code><?= sanitize($f['invoice_no']) ?></code></td>
                            <td style="font-size:0.82rem;font-weight:600;"><?= sanitize($f['student_name']) ?></td>
                            <td style="font-weight:700;color:<?= $f['status']==='paid'?'#22c55e':'#ef4444' ?>;">
                                <?= currency_format((float)$f['amount']) ?>
                            </td>
                            <td style="font-size:0.78rem;color:<?= $f['due_date']&&$f['due_date']<date('Y-m-d')&&$f['status']==='pending'?'#ef4444':'var(--text-muted)' ?>;">
                                <?= format_date($f['due_date']) ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= match($f['status']){'paid'=>'success','overdue'=>'danger',default=>'warning'} ?>">
                                    <?= ucfirst($f['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-bell" style="color:#4f8ef7;margin-right:8px;"></i>School Notifications</div>
            <div class="card-body" style="padding:8px 0;">
                <?php if (empty($notifs)): ?>
                <p style="text-align:center;color:var(--text-muted);padding:24px;margin:0;font-size:0.85rem;">No notifications</p>
                <?php else: foreach ($notifs as $n):
                    $nc = ['success'=>'#22c55e','warning'=>'#f5a623','danger'=>'#ef4444','info'=>'#4f8ef7'][$n['type']] ?? '#4f8ef7';
                ?>
                <div style="padding:12px 18px;border-bottom:1px solid rgba(255,255,255,0.04);<?= !$n['is_read']?'background:rgba(255,255,255,0.02);':'' ?>display:flex;gap:10px;align-items:flex-start;">
                    <i class="bi bi-circle-fill" style="color:<?= $nc ?>;font-size:0.45rem;margin-top:6px;flex-shrink:0;"></i>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:0.82rem;color:#fff;font-weight:<?= !$n['is_read']?'600':'400'?>;"><?= sanitize($n['title']) ?></div>
                        <div style="font-size:0.75rem;color:var(--text-muted);"><?= sanitize(mb_substr($n['message'],0,70)) ?></div>
                        <div style="font-size:0.68rem;color:rgba(255,255,255,0.25);margin-top:2px;"><?= date('d M, h:i A',strtotime($n['created_at'])) ?></div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include INCLUDES_PATH . 'footer.php'; ?>
