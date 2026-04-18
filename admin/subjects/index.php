<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('admin');
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_protect(); $action=sanitize($_POST['action']??'');
    if($action==='add'){$name=sanitize($_POST['name']??'');$code=sanitize($_POST['code']??'');$cid=sanitize_int($_POST['class_id']??0);if($name){$pdo->prepare("INSERT INTO subjects (name,code,class_id) VALUES (?,?,?)")->execute([$name,$code?:null,$cid?:null]);set_flash('success',"Subject '{$name}' added.");}}
    elseif($action==='delete'){$sid=sanitize_int($_POST['subject_id']??0);$pdo->prepare("DELETE FROM subjects WHERE id=?")->execute([$sid]);set_flash('success','Subject deleted.');}
    elseif($action==='edit'){$sid=sanitize_int($_POST['subject_id']??0);$name=sanitize($_POST['name']??'');$code=sanitize($_POST['code']??'');$cid=sanitize_int($_POST['class_id']??0);if($sid&&$name){$pdo->prepare("UPDATE subjects SET name=?,code=?,class_id=? WHERE id=?")->execute([$name,$code?:null,$cid?:null,$sid]);set_flash('success','Subject updated.');}}
    redirect(SITE_URL.'/admin/subjects/');
}
$subjects=$pdo->query("SELECT sub.*,c.name as class_name FROM subjects sub LEFT JOIN classes c ON sub.class_id=c.id ORDER BY sub.name")->fetchAll();
$classes=get_classes();
$page_title='Subjects'; $breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Subjects','active'=>true]];
include INCLUDES_PATH.'header.php';
?>
<div class="row g-3">
  <div class="col-12 col-md-4">
    <div class="card"><div class="card-header"><i class="bi bi-book me-2" style="color:#4f8ef7;"></i>Add Subject</div>
      <div class="card-body"><form method="POST"><?=csrf_field()?><input type="hidden" name="action" value="add">
        <div class="mb-2"><label class="form-label">Subject Name *</label><input type="text" name="name" class="form-control" placeholder="Mathematics" required></div>
        <div class="mb-2"><label class="form-label">Code</label><input type="text" name="code" class="form-control" placeholder="MATH" maxlength="20"></div>
        <div class="mb-3"><label class="form-label">Class (Optional)</label><select name="class_id" class="form-select"><option value="">All Classes</option><?php foreach($classes as $cl):?><option value="<?=$cl['id']?>"><?=sanitize($cl['name'])?></option><?php endforeach;?></select></div>
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i>Add Subject</button>
      </form></div></div>
  </div>
  <div class="col-12 col-md-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between"><span><i class="bi bi-book me-2" style="color:#4f8ef7;"></i>All Subjects</span><span class="badge bg-primary"><?=count($subjects)?></span></div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table">
            <thead><tr><th>#</th><th>Name</th><th>Code</th><th>Class</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
              <?php if(empty($subjects)):?><tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted);">No subjects yet.</td></tr>
              <?php else:foreach($subjects as $i=>$sub):?>
              <tr>
                <td class="text-muted"><?=$i+1?></td>
                <td class="fw-semibold"><?=sanitize($sub['name'])?></td>
                <td><code><?=sanitize($sub['code']??'—')?></code></td>
                <td><?=sanitize($sub['class_name']??'All Classes')?></td>
                <td class="text-end">
                  <button class="btn btn-outline-warning btn-sm me-1" onclick="editSubject(<?=$sub['id']?>,'<?=addslashes(sanitize($sub['name']))??>','<?=sanitize($sub['code']??'')??>',<?=$sub['class_id']??0?>)"><i class="bi bi-pencil"></i></button>
                  <form method="POST" class="d-inline" onsubmit="return confirm('Delete?')"><?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="subject_id" value="<?=$sub['id']?>"><button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button></form>
                </td>
              </tr>
              <?php endforeach;endif;?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Edit Subject</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><form method="POST"><?=csrf_field()?><input type="hidden" name="action" value="edit"><input type="hidden" name="subject_id" id="edit_id">
    <div class="mb-2"><label class="form-label">Name</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Code</label><input type="text" name="code" id="edit_code" class="form-control"></div>
    <div class="mb-3"><label class="form-label">Class</label><select name="class_id" id="edit_class" class="form-select"><option value="">All Classes</option><?php foreach($classes as $cl):?><option value="<?=$cl['id']?>"><?=sanitize($cl['name'])?></option><?php endforeach;?></select></div>
    <button type="submit" class="btn btn-warning w-100">Update Subject</button>
  </form></div>
</div></div></div>
<script>function editSubject(id,name,code,classId){document.getElementById('edit_id').value=id;document.getElementById('edit_name').value=name;document.getElementById('edit_code').value=code;document.getElementById('edit_class').value=classId;new bootstrap.Modal(document.getElementById('editModal')).show();}</script>
<?php include INCLUDES_PATH.'footer.php';?>
