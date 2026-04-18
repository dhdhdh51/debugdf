<?php
require_once dirname(__DIR__,2).'/config/config.php';
require_once INCLUDES_PATH.'auth_check.php';
auth_guard('admin');
$page_title='Enter Marks';$breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Marks','active'=>true]];
$classes=get_classes();$exams=$pdo->query("SELECT * FROM exams ORDER BY created_at DESC")->fetchAll();
$exam_id=sanitize_int($_GET['exam_id']??0);$class_id=sanitize_int($_GET['class_id']??0);$subj_id=sanitize_int($_GET['subject_id']??0);
$selected_exam=null;$students_list=[];$subjects_list=[];$existing_marks=[];
if($exam_id){$stmt=$pdo->prepare("SELECT * FROM exams WHERE id=? LIMIT 1");$stmt->execute([$exam_id]);$selected_exam=$stmt->fetch();}
if($exam_id&&$class_id){$subjects_list=get_subjects($class_id);$stmt=$pdo->prepare("SELECT s.id,s.name,s.student_id FROM students s WHERE s.class_id=? AND s.status='active' ORDER BY s.name");$stmt->execute([$class_id]);$students_list=$stmt->fetchAll();
if($subj_id&&!empty($students_list)){$stmt=$pdo->prepare("SELECT student_id,marks_obtained,max_marks FROM marks WHERE exam_id=? AND subject_id=?");$stmt->execute([$exam_id,$subj_id]);foreach($stmt->fetchAll() as $m)$existing_marks[$m['student_id']]=$m;}}
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['save_marks'])){
csrf_protect();$p_exam_id=sanitize_int($_POST['exam_id']??0);$p_subj_id=sanitize_int($_POST['subject_id']??0);$max_marks=sanitize_float($_POST['max_marks']??100);$marks_data=$_POST['marks']??[];
if($p_exam_id&&$p_subj_id&&!empty($marks_data)){$insert=$pdo->prepare("INSERT INTO marks (exam_id,student_id,subject_id,marks_obtained,max_marks) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE marks_obtained=VALUES(marks_obtained),max_marks=VALUES(max_marks)");
foreach($marks_data as $stu_id=>$mark){$stu_id=(int)$stu_id;$mark=max(0,min((float)$mark,$max_marks));$insert->execute([$p_exam_id,$stu_id,$p_subj_id,$mark,$max_marks]);}
$pass_pct=get_pass_percentage();$stu_ids=array_keys($marks_data);
foreach($stu_ids as $stu_id){$stu_id=(int)$stu_id;$res=$pdo->prepare("SELECT SUM(marks_obtained) as total,SUM(max_marks) as max FROM marks WHERE exam_id=? AND student_id=?");$res->execute([$p_exam_id,$stu_id]);$r=$res->fetch();
if($r&&$r['max']>0){$pct=round(($r['total']/$r['max'])*100,2);$grade=calculate_grade($pct);$result=$pct>=$pass_pct?'Pass':'Fail';$pdo->prepare("INSERT INTO results (exam_id,student_id,total_marks,max_marks,percentage,grade,result) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE total_marks=VALUES(total_marks),max_marks=VALUES(max_marks),percentage=VALUES(percentage),grade=VALUES(grade),result=VALUES(result)")->execute([$p_exam_id,$stu_id,$r['total'],$r['max'],$pct,$grade,$result]);}}
set_flash('success','Marks saved!');}
redirect(SITE_URL."/admin/marks/?exam_id={$p_exam_id}&class_id=".sanitize_int($_POST['class_id']??0)."&subject_id={$p_subj_id}");}
include INCLUDES_PATH.'header.php';
?>
<div class="card mb-3"><div class="card-header"><i class="bi bi-funnel" style="color:#f5a623;margin-right:8px;"></i>Select Exam & Class</div><div class="card-body">
<form method="GET" class="row g-3 align-items-end">
<div class="col-12 col-md-4"><label class="form-label">Exam *</label><select name="exam_id" class="form-select" required><option value="">Select Exam</option><?php foreach($exams as $ex):?><option value="<?=$ex['id']?>" <?=$exam_id==$ex['id']?'selected':''?>><?=sanitize($ex['name'])?> (<?=$ex['type']?>)</option><?php endforeach;?></select></div>
<div class="col-12 col-md-3"><label class="form-label">Class *</label><select name="class_id" class="form-select" required><option value="">Select Class</option><?php foreach($classes as $cl):?><option value="<?=$cl['id']?>" <?=$class_id==$cl['id']?'selected':''?>><?=sanitize($cl['name'])?></option><?php endforeach;?></select></div>
<div class="col-12 col-md-3"><label class="form-label">Subject</label><select name="subject_id" class="form-select"><option value="">Select Subject</option><?php foreach($subjects_list as $sub):?><option value="<?=$sub['id']?>" <?=$subj_id==$sub['id']?'selected':''?>><?=sanitize($sub['name'])?></option><?php endforeach;?></select></div>
<div class="col-auto"><button type="submit" class="btn btn-primary">Load</button></div>
</form></div></div>
<?php if($exam_id&&$class_id&&$subj_id&&!empty($students_list)):?>
<div class="card"><div class="card-header d-flex justify-content-between"><span><i class="bi bi-pencil-square" style="color:#f5a623;margin-right:8px;"></i>Enter Marks — <?=sanitize($selected_exam['name']??'')?></span><span class="badge bg-primary"><?=count($students_list)?> students</span></div><div class="card-body">
<form method="POST"><?=csrf_field()?><input type="hidden" name="save_marks" value="1"><input type="hidden" name="exam_id" value="<?=$exam_id?>"><input type="hidden" name="subject_id" value="<?=$subj_id?>"><input type="hidden" name="class_id" value="<?=$class_id?>">
<div class="row mb-3"><div class="col-12 col-md-4"><label class="form-label">Maximum Marks</label><input type="number" name="max_marks" class="form-control" value="100" min="1" max="1000" id="maxMarks"></div></div>
<div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>#</th><th>Student ID</th><th>Name</th><th style="width:160px;">Marks</th><th>Grade</th></tr></thead><tbody>
<?php foreach($students_list as $i=>$stu):$existing=$existing_marks[$stu['id']]??null;?>
<tr><td style="font-size:0.8rem;color:var(--text-muted);"><?=$i+1?></td><td><code style="font-size:0.75rem;"><?=sanitize($stu['student_id'])?></code></td>
<td style="font-weight:600;color:#fff;"><?=sanitize($stu['name'])?></td>
<td><input type="number" name="marks[<?=$stu['id']?>]" class="form-control form-control-sm marks-input" value="<?=$existing?$existing['marks_obtained']:''?>" min="0" max="100" step="0.5" placeholder="0-100"></td>
<td><span class="grade-display badge bg-secondary">—</span></td></tr>
<?php endforeach;?>
</tbody></table></div>
<button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-2"></i>Save All Marks</button>
</form></div></div>
<script>
const passPct=<?=get_pass_percentage()?>;
function getGrade(pct){if(pct>=90)return['A+','success'];if(pct>=80)return['A','success'];if(pct>=70)return['B+','primary'];if(pct>=60)return['B','primary'];if(pct>=50)return['C+','info'];if(pct>=40)return['C','info'];if(pct>=33)return['D','warning'];return['F','danger'];}
function updateGrade(input){const maxM=parseFloat(document.getElementById('maxMarks').value)||100;const marks=parseFloat(input.value);const row=input.closest('tr');const badge=row.querySelector('.grade-display');if(isNaN(marks)||input.value===''){badge.textContent='—';badge.className='grade-display badge bg-secondary';return;}const pct=(marks/maxM)*100;const[g,cls]=getGrade(pct);badge.textContent=g;badge.className=`grade-display badge bg-${cls}`;}
document.querySelectorAll('.marks-input').forEach(inp=>{inp.addEventListener('input',()=>updateGrade(inp));updateGrade(inp);});
document.getElementById('maxMarks').addEventListener('change',()=>document.querySelectorAll('.marks-input').forEach(inp=>updateGrade(inp)));
</script>
<?php elseif($exam_id&&$class_id):?><div class="alert alert-info">Select a subject to enter marks.</div>
<?php else:?><div class="alert alert-info">Select an exam and class to begin.</div><?php endif;?>
<?php include INCLUDES_PATH.'footer.php';?>
