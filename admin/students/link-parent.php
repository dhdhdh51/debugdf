<?php
/**
 * admin/students/link-parent.php
 * Link students ↔ parents (view, search, link, unlink)
 * Also bulk-link via admission parent_name/phone match
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('admin');

$page_title = 'Link Students to Parents';
$breadcrumb = [
    ['label' => 'Dashboard',  'url'    => SITE_URL . '/admin/'],
    ['label' => 'Students',   'url'    => SITE_URL . '/admin/students/'],
    ['label' => 'Link Parents', 'active' => true],
];

// ── Handle actions ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();
    $action = sanitize($_POST['action'] ?? '');

    // Link a student to a parent
    if ($action === 'link') {
        $student_id = sanitize_int($_POST['student_id'] ?? 0);
        $parent_id  = sanitize_int($_POST['parent_id']  ?? 0);
        if ($student_id && $parent_id) {
            $pdo->prepare("UPDATE students SET parent_id=? WHERE id=?")
                ->execute([$parent_id, $student_id]);
            set_flash('success', 'Student linked to parent successfully.');
        }
        redirect(SITE_URL . '/admin/students/link-parent.php?' . http_build_query(array_intersect_key($_GET, array_flip(['q_stu','q_par']))));
    }

    // Unlink a student from their parent
    if ($action === 'unlink') {
        $student_id = sanitize_int($_POST['student_id'] ?? 0);
        if ($student_id) {
            $pdo->prepare("UPDATE students SET parent_id=NULL WHERE id=?")->execute([$student_id]);
            set_flash('success', 'Parent link removed.');
        }
        redirect(SITE_URL . '/admin/students/link-parent.php');
    }

    // Quick-create a parent account and link
    if ($action === 'create_and_link') {
        $student_id   = sanitize_int($_POST['student_id']    ?? 0);
        $parent_name  = sanitize($_POST['parent_name']        ?? '');
        $parent_phone = sanitize($_POST['parent_phone']       ?? '');
        $parent_email = sanitize_email($_POST['parent_email'] ?? '');
        $relation     = sanitize($_POST['relation']           ?? 'Father');
        $pwd          = $_POST['password']                    ?? '';

        if ($student_id && $parent_name) {
            $pdo->beginTransaction();
            try {
                $user_id = null;
                if (!empty($parent_email) && !empty($pwd)) {
                    $pdo->prepare(
                        "INSERT INTO users (name,email,password,role) VALUES (?,?,?,'parent')"
                    )->execute([$parent_name, $parent_email, password_hash($pwd, PASSWORD_BCRYPT)]);
                    $user_id = (int)$pdo->lastInsertId();
                }
                $pdo->prepare(
                    "INSERT INTO parents (user_id,name,email,phone,relation,status) VALUES (?,?,?,?,?,'active')"
                )->execute([$user_id, $parent_name, $parent_email ?: null, $parent_phone ?: null, $relation]);
                $new_parent_id = (int)$pdo->lastInsertId();

                $pdo->prepare("UPDATE students SET parent_id=? WHERE id=?")->execute([$new_parent_id, $student_id]);

                // Update user.user_id in students
                if ($user_id) {
                    $pdo->prepare("UPDATE users SET name=? WHERE id=?")->execute([$parent_name, $user_id]);
                }

                $pdo->commit();
                set_flash('success', "Parent '{$parent_name}' created and linked to student.");
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash('error', 'Error: ' . $e->getMessage());
            }
        }
        redirect(SITE_URL . '/admin/students/link-parent.php');
    }

    // Auto-match: try to find parents by name/phone and link automatically
    if ($action === 'auto_match') {
        $matched = 0;
        $students_without_parent = $pdo->query(
            "SELECT s.id, s.name FROM students s WHERE s.parent_id IS NULL AND s.status='active'"
        )->fetchAll();

        foreach ($students_without_parent as $stu) {
            // Try admission record
            $adm = $pdo->prepare(
                "SELECT parent_name, parent_phone, parent_email FROM admissions
                 WHERE name = ? ORDER BY created_at DESC LIMIT 1"
            );
            $adm->execute([$stu['name']]);
            $adm_row = $adm->fetch();

            if ($adm_row && $adm_row['parent_name']) {
                // Find or create parent
                $par_stmt = $pdo->prepare(
                    "SELECT id FROM parents WHERE name=? OR (phone IS NOT NULL AND phone=?) LIMIT 1"
                );
                $par_stmt->execute([$adm_row['parent_name'], $adm_row['parent_phone'] ?? '']);
                $existing_parent = $par_stmt->fetch();

                if ($existing_parent) {
                    $pdo->prepare("UPDATE students SET parent_id=? WHERE id=?")
                        ->execute([$existing_parent['id'], $stu['id']]);
                    $matched++;
                } else {
                    // Create parent record
                    $pdo->prepare(
                        "INSERT INTO parents (name,email,phone,relation,status) VALUES (?,?,?,'Father','active')"
                    )->execute([$adm_row['parent_name'], $adm_row['parent_email'] ?: null, $adm_row['parent_phone'] ?: null]);
                    $new_pid = (int)$pdo->lastInsertId();
                    $pdo->prepare("UPDATE students SET parent_id=? WHERE id=?")->execute([$new_pid, $stu['id']]);
                    $matched++;
                }
            }
        }
        set_flash('success', "Auto-matched {$matched} student(s) to parents.");
        redirect(SITE_URL . '/admin/students/link-parent.php');
    }
}

// ── Filters ───────────────────────────────────────────────
$q_stu  = sanitize($_GET['q_stu']  ?? '');
$q_par  = sanitize($_GET['q_par']  ?? '');
$filter = sanitize($_GET['filter'] ?? 'all'); // all | linked | unlinked

// Students query
$where  = ["s.status='active'"];
$params = [];
if ($q_stu) {
    $where[]  = "(s.name LIKE ? OR s.student_id LIKE ?)";
    $like     = "%{$q_stu}%";
    $params   = array_merge($params, [$like, $like]);
}
if ($filter === 'linked')   $where[] = "s.parent_id IS NOT NULL";
if ($filter === 'unlinked') $where[] = "s.parent_id IS NULL";

$wh = implode(' AND ', $where);
$students = $pdo->prepare(
    "SELECT s.*, c.name as class_name, sec.name as section_name,
            p.id as par_id, p.name as par_name, p.phone as par_phone, p.relation
     FROM students s
     LEFT JOIN classes  c   ON s.class_id   = c.id
     LEFT JOIN sections sec ON s.section_id = sec.id
     LEFT JOIN parents  p   ON s.parent_id  = p.id
     WHERE $wh ORDER BY s.name LIMIT 50"
);
$students->execute($params);
$students = $students->fetchAll();

// Parents for dropdown search
$par_where  = ['1=1'];
$par_params = [];
if ($q_par) {
    $par_where[]  = "(name LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $plike        = "%{$q_par}%";
    $par_params   = [$plike, $plike, $plike];
}
$parents = $pdo->prepare(
    "SELECT p.*, (SELECT COUNT(*) FROM students s WHERE s.parent_id=p.id) as child_count
     FROM parents p WHERE " . implode(' AND ',$par_where) . " ORDER BY p.name LIMIT 30"
);
$parents->execute($par_params);
$parents = $parents->fetchAll();

// Summary counts
$total_students   = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn();
$linked_students  = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE status='active' AND parent_id IS NOT NULL")->fetchColumn();
$unlinked_students= $total_students - $linked_students;

include INCLUDES_PATH . 'header.php';
?>

<style>
.link-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 14px;
    overflow: hidden;
    transition: all 0.2s;
    margin-bottom: 10px;
    animation: fadeInUp 0.3s ease both;
}
.link-card:hover { border-color: rgba(245,166,35,0.2); }
.link-card-inner { padding: 14px 18px; }
.linked-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px; border-radius: 8px;
    font-size: 0.75rem; font-weight: 600;
    background: rgba(34,197,94,0.12);
    color: #4ade80;
    border: 1px solid rgba(34,197,94,0.25);
}
.unlinked-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px; border-radius: 8px;
    font-size: 0.75rem; font-weight: 600;
    background: rgba(239,68,68,0.1);
    color: #f87171;
    border: 1px solid rgba(239,68,68,0.2);
}
.parent-select-row {
    display: flex; align-items: center; gap: 8px;
    flex-wrap: wrap;
}
</style>

<!-- Summary stats -->
<div class="row g-3 mb-4">
    <div class="col-4">
        <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:14px;padding:18px;text-align:center;">
            <div style="font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:800;color:#fff;"><?= $total_students ?></div>
            <div style="font-size:0.75rem;color:var(--text-muted);">Total Students</div>
        </div>
    </div>
    <div class="col-4">
        <div style="background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.2);border-radius:14px;padding:18px;text-align:center;">
            <div style="font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:800;color:#22c55e;"><?= $linked_students ?></div>
            <div style="font-size:0.75rem;color:var(--text-muted);">Linked to Parent</div>
        </div>
    </div>
    <div class="col-4">
        <div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:14px;padding:18px;text-align:center;">
            <div style="font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:800;color:#ef4444;"><?= $unlinked_students ?></div>
            <div style="font-size:0.75rem;color:var(--text-muted);">Not Linked</div>
        </div>
    </div>
</div>

<!-- Auto-match + filter bar -->
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center gap-3 flex-wrap justify-content-between">
            <div>
                <h6 style="color:#fff;margin-bottom:4px;font-family:'Playfair Display',serif;">Auto-Match from Admissions</h6>
                <p style="color:var(--text-muted);font-size:0.82rem;margin:0;">
                    Automatically links students to parents using names/phone from admission records.
                </p>
            </div>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="auto_match">
                <button type="submit" class="btn btn-primary"
                        onclick="return confirm('Auto-match all unlinked students? This will create parent records from admission data.')">
                    <i class="bi bi-magic me-2"></i>Auto-Match Now
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Search and filter -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label">Search Student</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="q_stu" class="form-control"
                           placeholder="Name or Student ID" value="<?= sanitize($q_stu) ?>">
                </div>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">Filter</label>
                <select name="filter" class="form-select">
                    <option value="all"      <?= $filter==='all'?'selected':''?>>All Students</option>
                    <option value="linked"   <?= $filter==='linked'?'selected':''?>>Linked to Parent</option>
                    <option value="unlinked" <?= $filter==='unlinked'?'selected':''?>>Not Linked</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?= SITE_URL ?>/admin/students/link-parent.php" class="btn btn-outline-secondary ms-1">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Students list -->
<div class="row g-3">
    <!-- Left: students -->
    <div class="col-12 col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span style="display:flex;align-items:center;gap:8px;">
                    <i class="bi bi-people-fill" style="color:#4f8ef7;"></i>
                    Students
                    <span style="background:rgba(79,142,247,0.15);color:#93c5fd;border-radius:6px;padding:2px 8px;font-size:0.7rem;"><?= count($students) ?></span>
                </span>
            </div>
            <div class="card-body" style="padding:10px 16px;max-height:700px;overflow-y:auto;">
                <?php if (empty($students)): ?>
                <div style="text-align:center;padding:40px;color:var(--text-muted);">
                    <i class="bi bi-people" style="font-size:2.5rem;display:block;margin-bottom:12px;"></i>
                    No students found
                </div>
                <?php else: foreach ($students as $s): ?>
                <div class="link-card">
                    <div class="link-card-inner">
                        <div style="display:flex;align-items:flex-start;gap:12px;">
                            <!-- Avatar -->
                            <?php if ($s['photo']): ?>
                                <img src="<?= get_upload_url($s['photo']) ?>"
                                     style="width:40px;height:40px;border-radius:10px;object-fit:cover;flex-shrink:0;">
                            <?php else: ?>
                                <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#4f8ef7,#7b5ea7);display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:700;color:#fff;flex-shrink:0;">
                                    <?= strtoupper(substr($s['name'],0,1)) ?>
                                </div>
                            <?php endif; ?>
                            <!-- Info -->
                            <div style="flex:1;min-width:0;">
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;flex-wrap:wrap;">
                                    <strong style="color:#fff;font-size:0.9rem;"><?= sanitize($s['name']) ?></strong>
                                    <code style="font-size:0.72rem;color:#f5a623;"><?= sanitize($s['student_id']) ?></code>
                                    <span style="font-size:0.72rem;color:var(--text-muted);"><?= sanitize(($s['class_name']??'').' '.($s['section_name']??'')) ?></span>
                                </div>
                                <!-- Parent link status -->
                                <?php if ($s['par_id']): ?>
                                <div class="d-flex align-items-center gap-2 flex-wrap mt-2">
                                    <span class="linked-badge">
                                        <i class="bi bi-people-fill"></i>
                                        <?= sanitize($s['par_name']) ?>
                                        <?php if ($s['par_phone']): ?>· <?= sanitize($s['par_phone']) ?><?php endif; ?>
                                        (<?= sanitize($s['relation'] ?? 'Parent') ?>)
                                    </span>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Remove parent link?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action"     value="unlink">
                                        <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" style="font-size:0.72rem;padding:3px 10px;">
                                            <i class="bi bi-x-circle me-1"></i>Unlink
                                        </button>
                                    </form>
                                    <!-- Change parent -->
                                    <button type="button" class="btn btn-outline-secondary btn-sm" style="font-size:0.72rem;padding:3px 10px;"
                                            onclick="showLinkForm(<?= $s['id'] ?>, '<?= addslashes(sanitize($s['name'])) ?>')">
                                        <i class="bi bi-arrow-left-right me-1"></i>Change
                                    </button>
                                </div>
                                <?php else: ?>
                                <div class="d-flex align-items-center gap-2 flex-wrap mt-2">
                                    <span class="unlinked-badge">
                                        <i class="bi bi-person-x-fill"></i>No parent linked
                                    </span>
                                    <button type="button" class="btn btn-primary btn-sm" style="font-size:0.72rem;padding:3px 12px;"
                                            onclick="showLinkForm(<?= $s['id'] ?>, '<?= addslashes(sanitize($s['name'])) ?>')">
                                        <i class="bi bi-link-45deg me-1"></i>Link Parent
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" style="font-size:0.72rem;padding:3px 12px;"
                                            onclick="showCreateForm(<?= $s['id'] ?>, '<?= addslashes(sanitize($s['name'])) ?>')">
                                        <i class="bi bi-person-plus me-1"></i>New Parent
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Right: Parents list -->
    <div class="col-12 col-lg-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span style="display:flex;align-items:center;gap:8px;">
                    <i class="bi bi-people" style="color:#7b5ea7;"></i>
                    All Parents
                    <span style="background:rgba(123,94,167,0.15);color:#c4b5fd;border-radius:6px;padding:2px 8px;font-size:0.7rem;"><?= count($parents) ?></span>
                </span>
                <a href="<?= SITE_URL ?>/admin/parents/add.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-person-plus me-1"></i>Add Parent
                </a>
            </div>
            <!-- Parent search -->
            <div style="padding:10px 16px;border-bottom:1px solid rgba(255,255,255,0.06);">
                <form method="GET">
                    <input type="hidden" name="filter"  value="<?= sanitize($filter) ?>">
                    <input type="hidden" name="q_stu"   value="<?= sanitize($q_stu) ?>">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="q_par" class="form-control"
                               placeholder="Search parents..." value="<?= sanitize($q_par) ?>">
                        <button type="submit" class="btn btn-primary btn-sm">Go</button>
                    </div>
                </form>
            </div>
            <div class="card-body" style="padding:10px 16px;max-height:620px;overflow-y:auto;">
                <?php foreach ($parents as $p): ?>
                <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.05);">
                    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#7b5ea7,#5b3f87);display:flex;align-items:center;justify-content:center;font-size:0.9rem;font-weight:700;color:#fff;flex-shrink:0;">
                        <?= strtoupper(substr($p['name'],0,1)) ?>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:0.85rem;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= sanitize($p['name']) ?></div>
                        <div style="font-size:0.7rem;color:var(--text-muted);">
                            <?= $p['relation'] ?? 'Parent' ?>
                            <?= $p['phone'] ? ' · ' . sanitize($p['phone']) : '' ?>
                        </div>
                    </div>
                    <div style="flex-shrink:0;text-align:right;">
                        <span style="font-size:0.7rem;color:#4ade80;background:rgba(34,197,94,0.12);border-radius:6px;padding:2px 8px;">
                            <?= $p['child_count'] ?> child<?= $p['child_count']!=1?'ren':'' ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($parents)): ?>
                <p style="text-align:center;color:var(--text-muted);padding:24px;margin:0;font-size:0.85rem;">No parents found</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Link to existing parent modal -->
<div class="modal fade" id="linkModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Link Parent — <span id="linkStudentName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action"     value="link">
                    <input type="hidden" name="student_id" id="linkStudentId">

                    <div class="mb-3">
                        <label class="form-label">Select Parent</label>
                        <select name="parent_id" class="form-select" required>
                            <option value="">Search and select parent</option>
                            <?php foreach ($parents as $p): ?>
                            <option value="<?= $p['id'] ?>">
                                <?= sanitize($p['name']) ?>
                                <?= $p['phone'] ? ' — '.$p['phone'] : '' ?>
                                (<?= $p['relation'] ?? 'Parent' ?>)
                                [<?= $p['child_count'] ?> child<?= $p['child_count']!=1?'ren':'' ?>]
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Can't find the parent? Use "New Parent" to create one.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-link-45deg me-2"></i>Link This Parent
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Create new parent + link modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Parent & Link — <span id="createStudentName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action"     value="create_and_link">
                    <input type="hidden" name="student_id" id="createStudentId">

                    <div class="mb-3">
                        <label class="form-label">Parent Full Name *</label>
                        <input type="text" name="parent_name" class="form-control" required placeholder="e.g. Ramesh Kumar">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="parent_phone" class="form-control" placeholder="+91 9000000000">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Relation</label>
                            <select name="relation" class="form-select">
                                <option>Father</option>
                                <option>Mother</option>
                                <option>Guardian</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email (for portal login)</label>
                        <input type="email" name="parent_email" class="form-control" placeholder="parent@email.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Portal Password (optional)</label>
                        <input type="password" name="password" class="form-control" placeholder="Min 6 chars — leave blank = no login">
                        <div class="form-text">Parent needs email + password to log in to the parent portal.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-person-check me-2"></i>Create Parent & Link
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showLinkForm(studentId, studentName) {
    document.getElementById('linkStudentId').value   = studentId;
    document.getElementById('linkStudentName').textContent = studentName;
    new bootstrap.Modal(document.getElementById('linkModal')).show();
}
function showCreateForm(studentId, studentName) {
    document.getElementById('createStudentId').value   = studentId;
    document.getElementById('createStudentName').textContent = studentName;
    new bootstrap.Modal(document.getElementById('createModal')).show();
}
</script>

<?php include INCLUDES_PATH . 'footer.php'; ?>
