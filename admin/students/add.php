<?php
/**
 * admin/students/add.php — Premium + includes parent linking
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
require_once INCLUDES_PATH . 'mailer.php';
auth_guard('admin');

$page_title = 'Add Student';
$breadcrumb = [
    ['label' => 'Dashboard', 'url'  => SITE_URL . '/admin/'],
    ['label' => 'Students',  'url'  => SITE_URL . '/admin/students/'],
    ['label' => 'Add',       'active' => true],
];

$errors  = [];
$classes = get_classes();

// All parents for dropdown
$all_parents = $pdo->query(
    "SELECT id, name, phone, relation FROM parents WHERE status='active' ORDER BY name"
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();

    $name       = sanitize($_POST['name']            ?? '');
    $email      = sanitize_email($_POST['email']     ?? '');
    $phone      = sanitize($_POST['phone']           ?? '');
    $dob        = sanitize($_POST['dob']             ?? '');
    $gender     = sanitize($_POST['gender']          ?? '');
    $blood      = sanitize($_POST['blood_group']     ?? '');
    $address    = sanitize($_POST['address']         ?? '');
    $class_id   = sanitize_int($_POST['class_id']    ?? 0);
    $section_id = sanitize_int($_POST['section_id']  ?? 0);
    $adm_date   = sanitize($_POST['admission_date']  ?? date('Y-m-d'));
    $pwd        = $_POST['password']                 ?? '';
    $status     = sanitize($_POST['status']          ?? 'active');

    // Parent linking options
    $parent_link_mode = sanitize($_POST['parent_link_mode'] ?? 'existing'); // existing | new | none
    $existing_parent_id = sanitize_int($_POST['existing_parent_id'] ?? 0);
    $new_par_name   = sanitize($_POST['new_par_name']   ?? '');
    $new_par_phone  = sanitize($_POST['new_par_phone']  ?? '');
    $new_par_email  = sanitize_email($_POST['new_par_email'] ?? '');
    $new_par_rel    = sanitize($_POST['new_par_rel']    ?? 'Father');
    $new_par_pwd    = $_POST['new_par_pwd']             ?? '';

    // Validations
    if (empty($name))     $errors[] = 'Student name is required.';
    if (!$class_id)       $errors[] = 'Class is required.';
    if (!empty($email) && !validate_email($email)) $errors[] = 'Invalid email.';
    if ($parent_link_mode === 'new' && empty($new_par_name)) $errors[] = 'Parent name is required.';

    // Check email unique
    if (!empty($email)) {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $chk->execute([$email]);
        if ($chk->fetch()) $errors[] = 'Email already exists in system.';
    }

    // Photo upload
    $photo = null;
    if (!empty($_FILES['photo']['name'])) {
        $photo = upload_file($_FILES['photo'], 'students', ALLOWED_IMAGES);
        if ($photo === false) $errors[] = 'Invalid photo file. Allowed: JPG, PNG, GIF.';
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            $student_id = generate_student_id();
            $user_id    = null;

            // Create student portal account
            if (!empty($email) && !empty($pwd)) {
                $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,'student')")
                    ->execute([$name, $email, password_hash($pwd, PASSWORD_BCRYPT)]);
                $user_id = (int)$pdo->lastInsertId();
            }

            // Determine parent_id
            $parent_id = null;
            if ($parent_link_mode === 'existing' && $existing_parent_id) {
                $parent_id = $existing_parent_id;
            } elseif ($parent_link_mode === 'new' && $new_par_name) {
                // Create new parent
                $par_user_id = null;
                if (!empty($new_par_email) && !empty($new_par_pwd)) {
                    $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,'parent')")
                        ->execute([$new_par_name, $new_par_email, password_hash($new_par_pwd, PASSWORD_BCRYPT)]);
                    $par_user_id = (int)$pdo->lastInsertId();
                }
                $pdo->prepare(
                    "INSERT INTO parents (user_id,name,email,phone,relation,status) VALUES (?,?,?,?,?,'active')"
                )->execute([$par_user_id, $new_par_name, $new_par_email ?: null, $new_par_phone ?: null, $new_par_rel]);
                $parent_id = (int)$pdo->lastInsertId();

                // Send parent welcome email
                if ($par_user_id && !empty($new_par_email) && !empty($new_par_pwd)) {
                    SchoolMailer::sendWelcome($new_par_email, $new_par_name, 'parent', $new_par_pwd);
                }
            }

            // Insert student
            $pdo->prepare(
                "INSERT INTO students
                 (user_id,student_id,name,email,phone,dob,gender,blood_group,
                  address,class_id,section_id,parent_id,photo,admission_date,status)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
            )->execute([
                $user_id, $student_id, $name, $email ?: null, $phone ?: null,
                $dob ?: null, $gender ?: null, $blood ?: null, $address ?: null,
                $class_id, $section_id ?: null, $parent_id, $photo,
                $adm_date, $status
            ]);
            $student_db_id = (int)$pdo->lastInsertId();

            $pdo->commit();

            // Notifications
            if ($user_id) {
                create_notification($user_id,'student','Welcome!',
                    "Your student account ({$student_id}) is ready.",'success');
                if (!empty($email)) SchoolMailer::sendWelcome($email, $name, 'student', $pwd);
            }

            set_flash('success', "Student '{$name}' added. ID: {$student_id}");
            redirect(SITE_URL . '/admin/students/view.php?id=' . $student_db_id);

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

include INCLUDES_PATH . 'header.php';
?>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <div class="row g-3">
        <!-- Student Info card -->
        <div class="col-12 col-lg-8">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-person-plus" style="color:#4f8ef7;margin-right:8px;"></i>Student Information
                </div>
                <div class="card-body">
                    <!-- Photo -->
                    <div class="text-center mb-4">
                        <div class="photo-upload-wrap mx-auto">
                            <img id="photoPreview" src="<?= ASSETS_URL ?>/images/avatar.png"
                                 class="stu-avatar" alt="Photo" style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.1);">
                            <label class="photo-upload-btn" for="photo">
                                <i class="bi bi-camera-fill"></i>
                            </label>
                        </div>
                        <input type="file" id="photo" name="photo" class="d-none" accept="image/*">
                        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:6px;">Click photo to change</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Full Name <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="name" class="form-control"
                                   value="<?= sanitize($_POST['name'] ?? '') ?>" required placeholder="Student's full name">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Email (for portal login)</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= sanitize($_POST['email'] ?? '') ?>" placeholder="student@email.com">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control"
                                   value="<?= sanitize($_POST['phone'] ?? '') ?>" placeholder="+91 9000000000">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="dob" class="form-control"
                                   value="<?= sanitize($_POST['dob'] ?? '') ?>" max="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select</option>
                                <?php foreach (['Male','Female','Other'] as $g): ?>
                                <option value="<?= $g ?>" <?= ($_POST['gender']??'')===$g?'selected':'' ?>><?= $g ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">Blood Group</label>
                            <select name="blood_group" class="form-select">
                                <option value="">Select</option>
                                <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                                <option value="<?= $bg ?>" <?= ($_POST['blood_group']??'')===$bg?'selected':'' ?>><?= $bg ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Class <span style="color:#ef4444;">*</span></label>
                            <select name="class_id" class="form-select" id="classSelect" required>
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $cl): ?>
                                <option value="<?= $cl['id'] ?>" <?= (sanitize_int($_POST['class_id']??0))==$cl['id']?'selected':'' ?>><?= sanitize($cl['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Section</label>
                            <select name="section_id" class="form-select" id="sectionSelect">
                                <option value="">Select Section</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Admission Date</label>
                            <input type="date" name="admission_date" class="form-control"
                                   value="<?= sanitize($_POST['admission_date'] ?? date('Y-m-d')) ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active"   <?= ($_POST['status']??'active')==='active'?'selected':'' ?>>Active</option>
                                <option value="inactive" <?= ($_POST['status']??'')==='inactive'?'selected':'' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"><?= sanitize($_POST['address']??'') ?></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Portal Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Min 6 chars (for student login)">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Parent linking -->
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header" style="background:linear-gradient(135deg,rgba(123,94,167,0.15),rgba(123,94,167,0.05));border-color:rgba(123,94,167,0.2);">
                    <i class="bi bi-people" style="color:#7b5ea7;margin-right:8px;"></i>Parent / Guardian
                </div>
                <div class="card-body">
                    <!-- Mode selector -->
                    <div class="mb-3">
                        <label class="form-label">Linking Mode</label>
                        <div style="display:flex;flex-direction:column;gap:8px;">
                            <?php foreach ([
                                ['existing','bi-link-45deg','Link Existing Parent'],
                                ['new','bi-person-plus','Create New Parent'],
                                ['none','bi-dash-circle','Skip (link later)'],
                            ] as [$val,$icon,$lbl]): ?>
                            <label style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;border:1px solid rgba(255,255,255,0.07);cursor:pointer;transition:all 0.2s;"
                                   class="parent-mode-label <?= ($_POST['parent_link_mode']??'existing')===$val?'active':'' ?>"
                                   id="modeLabel_<?= $val ?>">
                                <input type="radio" name="parent_link_mode" value="<?= $val ?>"
                                       class="d-none" id="mode_<?= $val ?>"
                                       <?= ($_POST['parent_link_mode']??'existing')===$val?'checked':'' ?>
                                       onchange="switchParentMode('<?= $val ?>')">
                                <i class="bi <?= $icon ?>" style="font-size:1rem;color:var(--gold);width:16px;"></i>
                                <span style="font-size:0.85rem;font-weight:600;color:#fff;"><?= $lbl ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Existing parent -->
                    <div id="section_existing" style="<?= ($_POST['parent_link_mode']??'existing')==='existing'?'':'display:none' ?>">
                        <label class="form-label">Select Parent</label>
                        <select name="existing_parent_id" class="form-select" id="existingParentSel">
                            <option value="">— None —</option>
                            <?php foreach ($all_parents as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= (sanitize_int($_POST['existing_parent_id']??0))==$p['id']?'selected':'' ?>>
                                <?= sanitize($p['name']) ?>
                                <?= $p['phone'] ? ' · '.$p['phone'] : '' ?>
                                (<?= $p['relation'] ?? 'Parent' ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($all_parents)): ?>
                        <div style="font-size:0.75rem;color:#f5a623;margin-top:6px;">
                            <i class="bi bi-info-circle me-1"></i>No parents yet. Use "Create New Parent" instead.
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- New parent -->
                    <div id="section_new" style="<?= ($_POST['parent_link_mode']??'')==='new'?'':'display:none' ?>">
                        <div class="mb-2">
                            <label class="form-label">Parent Name <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="new_par_name" class="form-control"
                                   value="<?= sanitize($_POST['new_par_name']??'') ?>" placeholder="e.g. Ramesh Kumar">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="new_par_phone" class="form-control"
                                   value="<?= sanitize($_POST['new_par_phone']??'') ?>" placeholder="+91 9000000000">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Relation</label>
                            <select name="new_par_rel" class="form-select">
                                <?php foreach (['Father','Mother','Guardian'] as $r): ?>
                                <option value="<?= $r ?>" <?= ($_POST['new_par_rel']??'Father')===$r?'selected':'' ?>><?= $r ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="border-top:1px solid rgba(255,255,255,0.06);padding-top:12px;margin-top:12px;">
                            <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:10px;">
                                Parent Portal Access (optional)
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Email</label>
                                <input type="email" name="new_par_email" class="form-control"
                                       value="<?= sanitize($_POST['new_par_email']??'') ?>" placeholder="parent@email.com">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Password</label>
                                <input type="password" name="new_par_pwd" class="form-control"
                                       placeholder="Min 6 chars">
                            </div>
                        </div>
                    </div>

                    <!-- None -->
                    <div id="section_none" style="<?= ($_POST['parent_link_mode']??'')==='none'?'':'display:none' ?>">
                        <div style="background:rgba(245,166,35,0.08);border:1px solid rgba(245,166,35,0.2);border-radius:10px;padding:14px;font-size:0.82rem;color:var(--gold);">
                            <i class="bi bi-info-circle me-2"></i>
                            You can link a parent later from
                            <a href="<?= SITE_URL ?>/admin/students/link-parent.php" style="color:var(--gold);text-decoration:underline;">Students → Link Parents</a>.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit -->
    <div class="d-flex gap-2 mt-2">
        <button type="submit" class="btn btn-primary px-4 btn-lg">
            <i class="bi bi-check-circle me-2"></i>Add Student
        </button>
        <a href="<?= SITE_URL ?>/admin/students/" class="btn btn-outline-secondary btn-lg">Cancel</a>
    </div>
</form>

<style>
.parent-mode-label:hover { background: rgba(255,255,255,0.04); border-color: rgba(245,166,35,0.2); }
.parent-mode-label.active { background: rgba(245,166,35,0.08); border-color: rgba(245,166,35,0.3); }
</style>

<script>
// Photo preview
document.getElementById('photo').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const r = new FileReader();
        r.onload = e => { document.getElementById('photoPreview').src = e.target.result; };
        r.readAsDataURL(file);
    }
});

// Section load via AJAX
document.getElementById('classSelect').addEventListener('change', function() {
    const cid = this.value;
    const sec = document.getElementById('sectionSelect');
    sec.innerHTML = '<option value="">Loading...</option>';
    if (!cid) { sec.innerHTML = '<option value="">Select Section</option>'; return; }
    fetch('<?= SITE_URL ?>/admin/ajax/get-sections.php?class_id=' + cid)
        .then(r => r.json())
        .then(data => {
            sec.innerHTML = '<option value="">Select Section</option>';
            data.forEach(s => { sec.innerHTML += `<option value="${s.id}">${s.name}</option>`; });
        });
});

// Parent mode switcher
function switchParentMode(mode) {
    document.getElementById('mode_' + mode).checked = true;
    ['existing','new','none'].forEach(m => {
        document.getElementById('section_' + m).style.display = m === mode ? '' : 'none';
        document.getElementById('modeLabel_' + m).classList.toggle('active', m === mode);
    });
}
</script>

<?php include INCLUDES_PATH . 'footer.php'; ?>
