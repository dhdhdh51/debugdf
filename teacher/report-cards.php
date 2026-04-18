<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('teacher');
$teacher=get_teacher_by_user_id((int)$_SESSION['user_id']);
if(!$teacher) redirect(SITE_URL.'/auth/login.php');
$page_title='Report Cards'; $breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/teacher/'],['label'=>'Report Cards','active'=>true]];
$class_id=(int)($teacher['class_id']??0); $exams=$pdo->query("SELECT * FROM exams ORDER BY created_at DESC")->fetchAll();
$exam_id=sanitize_int($_GET['exam_id']??0); $results=[];
if($exam_id&&$class_id){
    $stmt=$pdo->prepare("SELECT r.*,s.name as student_name,s.student_id,s.photo FROM results r JOIN students s ON r.student_id=s.id WHERE r.exam_id=? AND s.class_id=? ORDER BY r.percentage DESC");
    $stmt->execute([$exam_id,$class_id]); $results=$stmt->fetchAll();
}
include INCLUDES_PATH.'header.php';
?>
<div class="card mb-3"><div class="card-body py-2"><form method="GET" class="d-flex gap-3 align-items-end flex-wrap">
  <div><label class="form-label" style="font-size:0.8rem;">Exam</label><select name="exam_id" class="form-select form-select-sm" style="min-width:200px;"><option value="">Select Exam</option><?php foreach($exams as $ex):?><option value="<?=$ex['id']?>" <?=$exam_id==$ex['id']?'selected':''?>><?=sanitize($ex['name'])?></option><?php endforeach;?></select></div>
  <button type="submit" class="btn btn-primary btn-sm">Load</button>
</form></div></div>
<?php if(!empty($results)):?>
<div class="card"><div class="card-header d-flex justify-content-between align-items-center">
  <span><i class="bi bi-award me-2" style="color:#f5a623;"></i>Results — <?=sanitize($teacher['class_name']??'')?></span>
  <span class="badge bg-primary"><?=count($results)?> students</span>
</div><div class="table-responsive"><table class="table"><thead><tr><th>Rank</th><th>Student</th><th>Total</th><th>%</th><th>Grade</th><th>Result</th><th>Report</th></tr></thead>
  <tbody><?php foreach($results as $rank=>$r):?>
  <tr>
    <td class="fw-bold text-muted"><?=$rank+1?></td>
    <td><div class="d-flex align-items-center gap-2"><?php if($r['photo']):?><img src="<?=get_upload_url($r['photo'])?>" class="rounded-circle" width="28" height="28" style="object-fit:cover;"><?php endif;?><div><div class="fw-semibold" style="font-size:0.85rem;"><?=sanitize($r['student_name'])?></div><code style="font-size:0.7rem;color:var(--text-muted);"><?=sanitize($r['student_id'])?></code></div></div></td>
    <td><?=$r['total_marks']?>/<?=$r['max_marks']?></td>
    <td class="fw-semibold"><?=$r['percentage']?>%</td>
    <td><span class="badge bg-<?=get_grade_color($r['grade']??'F')?>"><?=$r['grade']?></span></td>
    <td><span class="badge bg-<?=$r['result']==='Pass'?'success':'danger'?>"><?=$r['result']?></span></td>
    <td><a href="<?=SITE_URL?>/admin/results/report-card.php?exam_id=<?=$exam_id?>&student_id=<?=$r['student_id']?>" target="_blank" class="btn btn-outline-primary btn-sm py-0 px-2"><i class="bi bi-printer"></i></a></td>
  </tr><?php endforeach;?>
  </tbody>
</table></div></div>
<?php elseif($exam_id):?><div class="alert alert-info">No results found for this exam in your class.</div>
<?php else:?><div class="alert alert-info">Select an exam to view report cards.</div><?php endif;?>
<?php include INCLUDES_PATH.'footer.php';?>
