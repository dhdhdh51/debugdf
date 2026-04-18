<?php
require_once dirname(__DIR__,2).'/config/config.php';
require_once INCLUDES_PATH.'auth_check.php';
require_once INCLUDES_PATH.'mailer.php';
auth_guard('admin');
$page_title='Results';$breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Results','active'=>true]];
$exams=$pdo->query("SELECT * FROM exams ORDER BY created_at DESC")->fetchAll();$classes=get_classes();
$exam_id=sanitize_int($_GET['exam_id']??0);$class_id=sanitize_int($_GET['class_id']??0);
if(isset($_GET['publish'])&&$exam_id){
$pdo->prepare("UPDATE results SET published=1 WHERE exam_id=?")->execute([$exam_id]);
$stu_stmt=$pdo->prepare("SELECT s.email,s.name FROM students s JOIN results r ON r.student_id=s.id WHERE r.exam_id=? AND r.published=1");$stu_stmt->execute([$exam_id]);
$exam_name_row=$pdo->prepare("SELECT name FROM exams WHERE id=?");$exam_name_row->execute([$exam_id]);$exam_name=$exam_name_row->fetchColumn();
foreach($stu_stmt->fetchAll() as $stu){if($stu['email'])SchoolMailer::sendResultPublished($stu['email'],$stu['name'],$exam_name);}
notify_role('student','Results Published',"Results for '{$exam_name}' are now available.",'success');
set_flash('success','Results published!');redirect(SITE_URL."/admin/results/?exam_id={$exam_id}&class_id={$class_id}");
}
$results=[];
if($exam_id){$where=['r.exam_id=?'];$params=[$exam_id];if($class_id){$where[]="s.class_id=?";$params[]=$class_id;}$wh=implode(' AND ',$where);
$stmt=$pdo->prepare("SELECT r.*,s.name as student_name,s.student_id,s.photo,c.name as class_name FROM results r JOIN students s ON r.student_id=s.id LEFT JOIN classes c ON s.class_id=c.id WHERE $wh ORDER BY r.percentage DESC");$stmt->execute($params);$results=$stmt->fetchAll();}
include INCLUDES_PATH.'header.php';
?>
<div class="card mb-3"><div class="card-body"><form method="GET" class="row g-2 align-items-end">
<div class="col-12 col-md-4"><label class="form-label">Exam</label><select name="exam_id" class="form-select form-select-sm"><option value="">Select Exam</option><?php foreach($exams as $ex):?><option value="<?=$ex['id']?>" <?=$exam_id==$ex['id']?'selected':''?>><?=sanitize($ex['name'])?></option><?php endforeach;?></select></div>
<div class="col-12 col-md-3"><label class="form-label">Class</label><select name="class_id" class="form-select form-select-sm"><option value="">All Classes</option><?php foreach($classes as $cl):?><option value="<?=$cl['id']?>" <?=$class_id==$cl['id']?'selected':''?>><?=sanitize($cl['name'])?></option><?php endforeach;?></select></div>
<div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">View</button>
<?php if($exam_id):?><a href="?exam_id=<?=$exam_id?>&class_id=<?=$class_id?>&publish=1" class="btn btn-success btn-sm ms-1" onclick="return confirm('Publish results?')"><i class="bi bi-check-circle me-1"></i>Publish</a><?php endif;?></div>
</form></div></div>
<?php if(!empty($results)):?>
<div class="card"><div class="card-header d-flex justify-content-between"><span><i class="bi bi-award" style="color:#f5a623;margin-right:8px;"></i>Results</span><span class="badge bg-primary"><?=count($results)?> students</span></div>
<div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Rank</th><th>Student</th><th>Class</th><th>Total</th><th>%</th><th>Grade</th><th>Result</th><th>Published</th><th>Report</th></tr></thead><tbody>
<?php foreach($results as $rank=>$r):?>
<tr><td style="font-weight:700;color:var(--text-muted);"><?=$rank===0?'🥇':($rank===1?'🥈':($rank===2?'🥉':($rank+1)))?></td>
<td><div class="d-flex align-items-center gap-2"><?php if($r['photo']):?><img src="<?=get_upload_url($r['photo'])?>" class="rounded-circle" width="28" height="28" style="object-fit:cover;"><?php endif;?><div><div style="font-weight:600;font-size:0.85rem;color:#fff;"><?=sanitize($r['student_name'])?></div><code style="font-size:0.7rem;"><?=sanitize($r['student_id'])?></code></div></div></td>
<td style="font-size:0.82rem;"><?=sanitize($r['class_name']??'—')?></td>
<td><?=$r['total_marks']?>/<?=$r['max_marks']?></td><td style="font-weight:600;"><?=$r['percentage']?>%</td>
<td><span class="badge bg-<?=get_grade_color($r['grade']??'F')?>"><?=$r['grade']?></span></td>
<td><span class="badge bg-<?=$r['result']==='Pass'?'success':'danger'?>"><?=$r['result']?></span></td>
<td><span class="badge bg-<?=$r['published']?'success':'warning'?>"><?=$r['published']?'Yes':'No'?></span></td>
<td><a href="<?=SITE_URL?>/admin/results/report-card.php?exam_id=<?=$exam_id?>&student_id=<?=$r['student_id']?>" target="_blank" class="btn btn-outline-primary btn-sm py-0 px-2"><i class="bi bi-printer"></i></a></td></tr>
<?php endforeach;?>
</tbody></table></div></div>
<?php elseif($exam_id):?><div class="alert alert-info">No results found. <a href="<?=SITE_URL?>/admin/marks/?exam_id=<?=$exam_id?>">Enter marks</a> first.</div>
<?php else:?><div class="alert alert-info">Select an exam to view results.</div><?php endif;?>
<?php include INCLUDES_PATH.'footer.php';?>
