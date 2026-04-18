<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
require_once INCLUDES_PATH . 'mailer.php';
auth_guard('admin');
$page_title='Attendance'; $breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Attendance','active'=>true]];
$classes=get_classes(); $class_id=sanitize_int($_GET['class_id']??0); $section_id=sanitize_int($_GET['section_id']??0);
$date=sanitize($_GET['date']??date('Y-m-d')); $sections=$class_id?get_sections_by_class($class_id):[];
$students=[]; $attendance_map=[];
if ($class_id&&$date) {
    $where=["s.class_id=?","s.status='active'"]; $params=[$class_id];
    if ($section_id){$where[]="s.section_id=?";$params[]=$section_id;}
    $stmt=$pdo->prepare("SELECT s.id,s.name,s.student_id,s.photo FROM students s WHERE ".implode(' AND ',$where)." ORDER BY s.name");
    $stmt->execute($params); $students=$stmt->fetchAll();
    if (!empty($students)){
        $ids=array_column($students,'id'); $in=implode(',',array_fill(0,count($ids),'?'));
        $stmt=$pdo->prepare("SELECT student_id,status FROM attendance WHERE date=? AND student_id IN ($in)");
        $stmt->execute(array_merge([$date],$ids));
        foreach ($stmt->fetchAll() as $a) $attendance_map[$a['student_id']]=$a['status'];
    }
}
if ($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['save_attendance'])) {
    csrf_protect();
    $p_class=sanitize_int($_POST['class_id']??0); $p_section=sanitize_int($_POST['section_id']??0);
    $p_date=sanitize($_POST['date']??''); $att_data=$_POST['attendance']??[];
    if ($p_class&&$p_date&&!empty($att_data)){
        $upsert=$pdo->prepare("INSERT INTO attendance (student_id,class_id,section_id,date,status,marked_by) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status),marked_by=VALUES(marked_by)");
        $absent_students=[];
        foreach ($att_data as $sid=>$status){
            $sid=(int)$sid; $status=in_array($status,['Present','Absent','Late','Holiday'])?$status:'Present';
            $upsert->execute([$sid,$p_class,$p_section?:null,$p_date,$status,$_SESSION['user_id']]);
            if ($status==='Absent') $absent_students[]=$sid;
        }
        if (!empty($absent_students)){
            $in=implode(',',array_fill(0,count($absent_students),'?'));
            $stmt=$pdo->prepare("SELECT s.name as sn,p.name as pn,p.email as pe FROM students s JOIN parents p ON s.parent_id=p.id WHERE s.id IN ($in) AND p.email IS NOT NULL");
            $stmt->execute($absent_students);
            foreach ($stmt->fetchAll() as $row) SchoolMailer::sendAbsenceAlert($row['pe'],$row['pn'],$row['sn'],$p_date);
        }
        set_flash('success','Attendance saved for '.count($att_data).' students.');
    }
    redirect(SITE_URL."/admin/attendance/?class_id={$p_class}&section_id={$p_section}&date=".urlencode($p_date));
}
include INCLUDES_PATH.'header.php';
?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-calendar-check me-2" style="color:#22c55e;"></i>Select Class & Date</div>
  <div class="card-body">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-12 col-md-3"><label class="form-label">Class *</label>
        <select name="class_id" class="form-select" id="classSelect" required>
          <option value="">Select Class</option>
          <?php foreach($classes as $cl):?><option value="<?=$cl['id']?>" <?=$class_id==$cl['id']?'selected':''?>><?=sanitize($cl['name'])?></option><?php endforeach;?>
        </select></div>
      <div class="col-12 col-md-3"><label class="form-label">Section</label>
        <select name="section_id" class="form-select" id="sectionSelect">
          <option value="">All Sections</option>
          <?php foreach($sections as $sec):?><option value="<?=$sec['id']?>" <?=$section_id==$sec['id']?'selected':''?>><?=sanitize($sec['name'])?></option><?php endforeach;?>
        </select></div>
      <div class="col-12 col-md-3"><label class="form-label">Date *</label>
        <input type="date" name="date" class="form-control" value="<?=sanitize($date)?>" max="<?=date('Y-m-d')?>" required></div>
      <div class="col-auto"><button type="submit" class="btn btn-primary">Load</button></div>
    </form>
  </div>
</div>
<?php if($class_id&&!empty($students)):?>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-calendar-check me-2" style="color:#22c55e;"></i>Mark Attendance — <?=format_date($date)?></span>
    <div class="d-flex gap-2">
      <button class="btn btn-sm btn-success" onclick="markAll('Present')">All Present</button>
      <button class="btn btn-sm btn-danger" onclick="markAll('Absent')">All Absent</button>
    </div>
  </div>
  <div class="card-body">
    <form method="POST">
      <?=csrf_field()?>
      <input type="hidden" name="save_attendance" value="1">
      <input type="hidden" name="class_id" value="<?=$class_id?>">
      <input type="hidden" name="section_id" value="<?=$section_id?>">
      <input type="hidden" name="date" value="<?=sanitize($date)?>">
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>#</th><th>Photo</th><th>ID</th><th>Name</th><th>Attendance</th></tr></thead>
          <tbody>
          <?php foreach($students as $i=>$stu): $cur=$attendance_map[$stu['id']]??'Present';?>
          <tr>
            <td class="text-muted"><?=$i+1?></td>
            <td><?php if($stu['photo']):?><img src="<?=get_upload_url($stu['photo'])?>" class="rounded-circle" width="32" height="32" style="object-fit:cover;"><?php else:?><div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:12px;"><?=strtoupper(substr($stu['name'],0,1))?></div><?php endif;?></td>
            <td><code><?=sanitize($stu['student_id'])?></code></td>
            <td class="fw-semibold"><?=sanitize($stu['name'])?></td>
            <td>
              <?php foreach(['Present'=>'success','Absent'=>'danger','Late'=>'warning','Holiday'=>'info'] as $s=>$c):?>
              <input type="radio" class="d-none" name="attendance[<?=$stu['id']?>]" id="att_<?=$stu['id']?>_<?=$s?>" value="<?=$s?>" <?=$cur===$s?'checked':''?>>
              <?php endforeach;?>
              <div class="d-flex gap-1 flex-wrap att-btn-group" data-id="<?=$stu['id']?>">
                <?php foreach(['Present'=>'success','Absent'=>'danger','Late'=>'warning','Holiday'=>'info'] as $s=>$c):?>
                <button type="button" class="btn btn-sm att-btn btn-<?=$cur===$s?$c:'outline-'.$c?>" data-status="<?=$s?>" onclick="setAtt(<?=$stu['id']?>,'<?=$s?>')"><?=$s?></button>
                <?php endforeach;?>
              </div>
            </td>
          </tr>
          <?php endforeach;?>
          </tbody>
        </table>
      </div>
      <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-2"></i>Save Attendance</button>
    </form>
  </div>
</div>
<script>
function setAtt(id,status){
  document.getElementById(`att_${id}_${status}`).checked=true;
  const g=document.querySelector(`.att-btn-group[data-id="${id}"]`);
  const colors={Present:'success',Absent:'danger',Late:'warning',Holiday:'info'};
  g.querySelectorAll('.att-btn').forEach(b=>{const s=b.dataset.status,c=colors[s];b.className=`btn btn-sm att-btn btn-${s===status?c:'outline-'+c}`;});
}
function markAll(status){document.querySelectorAll('.att-btn-group').forEach(g=>setAtt(g.dataset.id,status));}
// Section AJAX
document.getElementById('classSelect')?.addEventListener('change',function(){
  const sec=document.getElementById('sectionSelect'); sec.innerHTML='<option value="">Loading...</option>';
  if(!this.value){sec.innerHTML='<option value="">All Sections</option>';return;}
  fetch('<?=SITE_URL?>/admin/ajax/get-sections.php?class_id='+this.value).then(r=>r.json()).then(data=>{
    sec.innerHTML='<option value="">All Sections</option>';
    data.forEach(s=>{sec.innerHTML+=`<option value="${s.id}">${s.name}</option>`;});
  });
});
</script>
<?php elseif($class_id&&empty($students)):?>
<div class="alert alert-warning">No active students found for this class/section.</div>
<?php endif;?>
<?php include INCLUDES_PATH.'footer.php';?>
