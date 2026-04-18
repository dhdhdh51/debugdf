<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('teacher');
$teacher=get_teacher_by_user_id((int)$_SESSION['user_id']);
if(!$teacher) redirect(SITE_URL.'/auth/login.php');
$page_title='Enter Marks'; $breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/teacher/'],['label'=>'Marks','active'=>true]];
$class_id=(int)($teacher['class_id']??0); $subj_id=(int)($teacher['subject_id']??0);
$exams=$pdo->query("SELECT * FROM exams WHERE status IN ('ongoing','upcoming') ORDER BY created_at DESC")->fetchAll();
$subjects=$subj_id?[$pdo->query("SELECT * FROM subjects WHERE id={$subj_id}")->fetch()]:get_subjects($class_id);
$exam_id=sanitize_int($_GET['exam_id']??0); $sel_subj=sanitize_int($_GET['subject_id']??$subj_id);
$students=[]; $exist_marks=[];
if($exam_id&&$class_id){
    $stmt=$pdo->prepare("SELECT id,name,student_id,photo FROM students WHERE class_id=? AND status='active' ORDER BY name");$stmt->execute([$class_id]);$students=$stmt->fetchAll();
    if($sel_subj&&!empty($students)){$stmt=$pdo->prepare("SELECT student_id,marks_obtained,max_marks FROM marks WHERE exam_id=? AND subject_id=?");$stmt->execute([$exam_id,$sel_subj]);foreach($stmt->fetchAll() as $m) $exist_marks[$m['student_id']]=$m;}
}
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['save_marks'])){
    csrf_protect();
    $p_exam=sanitize_int($_POST['exam_id']??0); $p_subj=sanitize_int($_POST['subject_id']??0);
    $max_m=sanitize_float($_POST['max_marks']??100); $mdata=$_POST['marks']??[];
    if($p_exam&&$p_subj&&!empty($mdata)){
        $ins=$pdo->prepare("INSERT INTO marks (exam_id,student_id,subject_id,marks_obtained,max_marks) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE marks_obtained=VALUES(marks_obtained),max_marks=VALUES(max_marks)");
        foreach($mdata as $sid=>$mark){$sid=(int)$sid;$mark=max(0,min((float)$mark,$max_m));$ins->execute([$p_exam,$sid,$p_subj,$mark,$max_m]);}
        $pass_pct=get_pass_percentage();
        foreach(array_keys($mdata) as $sid){$sid=(int)$sid;$r=$pdo->prepare("SELECT SUM(marks_obtained) as t,SUM(max_marks) as m FROM marks WHERE exam_id=? AND student_id=?");$r->execute([$p_exam,$sid]);$row=$r->fetch();
            if($row&&$row['m']>0){$pct=round(($row['t']/$row['m'])*100,2);$grade=calculate_grade($pct);$res=$pct>=$pass_pct?'Pass':'Fail';
                $pdo->prepare("INSERT INTO results (exam_id,student_id,total_marks,max_marks,percentage,grade,result) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE total_marks=VALUES(total_marks),max_marks=VALUES(max_marks),percentage=VALUES(percentage),grade=VALUES(grade),result=VALUES(result)")->execute([$p_exam,$sid,$row['t'],$row['m'],$pct,$grade,$res]);}}
        set_flash('success','Marks saved!');
    }
    redirect(SITE_URL."/teacher/marks.php?exam_id={$p_exam}&subject_id={$p_subj}");
}
include INCLUDES_PATH.'header.php';
?>
<div class="card mb-3"><div class="card-body"><form method="GET" class="row g-3 align-items-end">
  <div class="col-12 col-md-4"><label class="form-label">Exam</label><select name="exam_id" class="form-select" required><option value="">Select Exam</option><?php foreach($exams as $ex):?><option value="<?=$ex['id']?>" <?=$exam_id==$ex['id']?'selected':''?>><?=sanitize($ex['name'])?> (<?=$ex['type']?>)</option><?php endforeach;?></select></div>
  <div class="col-12 col-md-4"><label class="form-label">Subject</label><select name="subject_id" class="form-select"><option value="">Select Subject</option><?php foreach($subjects as $sub):if(!$sub) continue;?><option value="<?=$sub['id']?>" <?=$sel_subj==$sub['id']?'selected':''?>><?=sanitize($sub['name'])?></option><?php endforeach;?></select></div>
  <div class="col-auto"><button type="submit" class="btn btn-primary">Load Students</button></div>
</form></div></div>
<?php if($exam_id&&$sel_subj&&!empty($students)):?>
<div class="card"><div class="card-header d-flex justify-content-between align-items-center">
  <span><i class="bi bi-pencil-square me-2" style="color:#4f8ef7;"></i>Enter Marks</span><span class="badge bg-primary"><?=count($students)?> students</span>
</div><div class="card-body"><form method="POST"><?=csrf_field()?>
  <input type="hidden" name="save_marks" value="1"><input type="hidden" name="exam_id" value="<?=$exam_id?>"><input type="hidden" name="subject_id" value="<?=$sel_subj?>">
  <div class="mb-3" style="max-width:200px;"><label class="form-label">Max Marks</label><input type="number" name="max_marks" id="maxMarks" class="form-control" value="100" min="1" max="1000"></div>
  <div class="table-responsive"><table class="table"><thead><tr><th>#</th><th>Student</th><th style="width:160px;">Marks</th><th>Grade</th></tr></thead><tbody>
    <?php foreach($students as $i=>$stu): $ex=$exist_marks[$stu['id']]??null;?>
    <tr><td class="text-muted"><?=$i+1?></td>
      <td><div class="d-flex align-items-center gap-2"><?php if($stu['photo']):?><img src="<?=get_upload_url($stu['photo'])?>" class="rounded-circle" width="28" height="28" style="object-fit:cover;"><?php endif;?><div><div class="fw-semibold" style="font-size:0.85rem;"><?=sanitize($stu['name'])?></div><code style="font-size:0.7rem;color:var(--text-muted);"><?=sanitize($stu['student_id'])?></code></div></div></td>
      <td><input type="number" name="marks[<?=$stu['id']?>]" class="form-control form-control-sm marks-input" value="<?=$ex?$ex['marks_obtained']:''?>" min="0" max="100" step="0.5" placeholder="0–100"></td>
      <td><span class="grade-badge badge bg-secondary">—</span></td>
    </tr><?php endforeach;?>
  </tbody></table></div>
  <div class="d-flex gap-2"><button type="submit" class="btn btn-success px-4"><i class="bi bi-save me-2"></i>Save Marks</button></div>
</form></div></div>
<script>
const passPct=<?=get_pass_percentage()?>;
function getGrade(p){if(p>=90)return['A+','success'];if(p>=80)return['A','success'];if(p>=70)return['B+','primary'];if(p>=60)return['B','primary'];if(p>=50)return['C+','info'];if(p>=40)return['C','info'];if(p>=33)return['D','warning'];return['F','danger'];}
function updateGrade(inp){const max=parseFloat(document.getElementById('maxMarks').value)||100;const m=parseFloat(inp.value);const row=inp.closest('tr');const b=row.querySelector('.grade-badge');if(isNaN(m)||inp.value===''){b.textContent='—';b.className='grade-badge badge bg-secondary';return;}const p=(m/max)*100;const[g,c]=getGrade(p);b.textContent=g;b.className=`grade-badge badge bg-${c}`;}
document.querySelectorAll('.marks-input').forEach(i=>{i.addEventListener('input',()=>updateGrade(i));updateGrade(i);});
document.getElementById('maxMarks').addEventListener('change',()=>document.querySelectorAll('.marks-input').forEach(i=>updateGrade(i)));
</script>
<?php elseif($exam_id):?><div class="alert alert-info">Select a subject to enter marks.</div>
<?php else:?><div class="alert alert-info">Select an exam to begin.</div><?php endif;?>
<?php include INCLUDES_PATH.'footer.php';?>
