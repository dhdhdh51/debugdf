<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('admin');
if ($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['action'])) {
    csrf_protect(); $action=sanitize($_POST['action']);
    if ($action==='add_class'){$name=sanitize($_POST['class_name']??'');if($name){$pdo->prepare("INSERT INTO classes (name) VALUES (?)")->execute([$name]);set_flash('success',"Class '{$name}' added.");}}
    elseif($action==='delete_class'){$cid=sanitize_int($_POST['class_id']??0);$pdo->prepare("DELETE FROM classes WHERE id=?")->execute([$cid]);set_flash('success','Class deleted.');}
    elseif($action==='add_section'){$cid=sanitize_int($_POST['class_id']??0);$name=sanitize($_POST['section_name']??'');if($cid&&$name){$pdo->prepare("INSERT INTO sections (class_id,name) VALUES (?,?)")->execute([$cid,$name]);set_flash('success',"Section '{$name}' added.");}}
    elseif($action==='delete_section'){$sid=sanitize_int($_POST['section_id']??0);$pdo->prepare("DELETE FROM sections WHERE id=?")->execute([$sid]);set_flash('success','Section deleted.');}
    redirect(SITE_URL.'/admin/classes/');
}
$classes=$pdo->query("SELECT c.*,COUNT(DISTINCT s.id) as student_count,COUNT(DISTINCT sec.id) as section_count FROM classes c LEFT JOIN students s ON s.class_id=c.id AND s.status='active' LEFT JOIN sections sec ON sec.class_id=c.id GROUP BY c.id ORDER BY c.id")->fetchAll();
$page_title='Classes & Sections'; $breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Classes','active'=>true]];
include INCLUDES_PATH.'header.php';
?>
<div class="row g-3">
  <div class="col-12 col-md-4">
    <div class="card mb-3"><div class="card-header"><i class="bi bi-mortarboard me-2" style="color:#f5a623;"></i>Add Class</div>
      <div class="card-body"><form method="POST"><?=csrf_field()?><input type="hidden" name="action" value="add_class">
        <div class="mb-3"><label class="form-label">Class Name</label><input type="text" name="class_name" class="form-control" placeholder="e.g., Class 7" required></div>
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i>Add Class</button>
      </form></div></div>
    <div class="card"><div class="card-header"><i class="bi bi-grid me-2" style="color:#22c55e;"></i>Add Section</div>
      <div class="card-body"><form method="POST"><?=csrf_field()?><input type="hidden" name="action" value="add_section">
        <div class="mb-2"><label class="form-label">Class</label><select name="class_id" class="form-select" required><option value="">Select Class</option>
          <?php foreach($classes as $cl):?><option value="<?=$cl['id']?>"><?=sanitize($cl['name'])?></option><?php endforeach;?></select></div>
        <div class="mb-3"><label class="form-label">Section Name</label><input type="text" name="section_name" class="form-control" placeholder="e.g., A, B, C" required maxlength="10"></div>
        <button type="submit" class="btn btn-success w-100"><i class="bi bi-plus-circle me-1"></i>Add Section</button>
      </form></div></div>
  </div>
  <div class="col-12 col-md-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-mortarboard me-2" style="color:#f5a623;"></i>All Classes</span>
        <span class="badge bg-primary"><?=count($classes)?> classes</span>
      </div>
      <div class="card-body p-0">
        <?php if(empty($classes)):?>
        <div class="text-center py-5" style="color:var(--text-muted);"><i class="bi bi-mortarboard" style="font-size:2.5rem;display:block;margin-bottom:12px;"></i>No classes yet.</div>
        <?php else:?>
        <div class="accordion" id="classAccordion">
          <?php foreach($classes as $cl): $sections=get_sections_by_class((int)$cl['id']);?>
          <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed py-2 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#class<?=$cl['id']?>">
                <i class="bi bi-mortarboard me-2" style="color:#f5a623;"></i><?=sanitize($cl['name'])?>
                <span class="ms-2 badge bg-primary" style="font-size:0.65rem;"><?=$cl['student_count']?> students</span>
                <span class="ms-1 badge bg-success" style="font-size:0.65rem;"><?=$cl['section_count']?> sections</span>
              </button>
            </h2>
            <div id="class<?=$cl['id']?>" class="accordion-collapse collapse">
              <div class="accordion-body py-2">
                <div class="d-flex flex-wrap gap-2 mb-2">
                  <?php foreach($sections as $sec):?>
                  <div class="d-flex align-items-center gap-1 badge bg-secondary px-3 py-2">
                    <i class="bi bi-grid" style="color:#22c55e;"></i><?=sanitize($sec['name'])?>
                    <form method="POST" class="d-inline ms-1" onsubmit="return confirm('Delete section?')"><?=csrf_field()?><input type="hidden" name="action" value="delete_section"><input type="hidden" name="section_id" value="<?=$sec['id']?>">
                    <button type="submit" class="btn btn-link btn-sm p-0" style="color:#f87171;"><i class="bi bi-x-circle"></i></button></form>
                  </div>
                  <?php endforeach; if(empty($sections)):?><span style="color:var(--text-muted);font-size:0.82rem;">No sections</span><?php endif;?>
                </div>
                <form method="POST" class="d-inline-block" onsubmit="return confirm('Delete class?')"><?=csrf_field()?><input type="hidden" name="action" value="delete_class"><input type="hidden" name="class_id" value="<?=$cl['id']?>">
                  <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Delete Class</button></form>
              </div>
            </div>
          </div>
          <?php endforeach;?>
        </div>
        <?php endif;?>
      </div>
    </div>
  </div>
</div>
<?php include INCLUDES_PATH.'footer.php';?>
