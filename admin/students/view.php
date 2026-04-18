<?php
require_once dirname(__DIR__,2).'/config/config.php';
require_once INCLUDES_PATH.'auth_check.php';
auth_guard('admin');
$id=sanitize_int($_GET['id']??0);
if(!$id){redirect(SITE_URL.'/admin/students/');}
$stmt=$pdo->prepare("SELECT s.*,c.name as class_name,sec.name as section_name,p.name as parent_name,p.phone as parent_phone,p.email as parent_email,p.relation FROM students s LEFT JOIN classes c ON s.class_id=c.id LEFT JOIN sections sec ON s.section_id=sec.id LEFT JOIN parents p ON s.parent_id=p.id WHERE s.id=? LIMIT 1");
$stmt->execute([$id]);$s=$stmt->fetch();
if(!$s){set_flash('error','Student not found.');redirect(SITE_URL.'/admin/students/');}
$att=attendance_summary((int)$s['id']);
$results=$pdo->prepare("SELECT r.*,e.name as exam_name,e.type FROM results r JOIN exams e ON r.exam_id=e.id WHERE r.student_id=? ORDER BY r.created_at DESC LIMIT 5");$results->execute([$id]);$results=$results->fetchAll();
$fee_stmt=$pdo->prepare("SELECT COALESCE(SUM(amount),0) as total,COALESCE(SUM(CASE WHEN status='paid' THEN amount ELSE 0 END),0) as paid,COALESCE(SUM(CASE WHEN status='pending' THEN amount ELSE 0 END),0) as pending FROM fees WHERE student_id=?");$fee_stmt->execute([$id]);$fees=$fee_stmt->fetch();
$page_title=sanitize($s['name']);
$breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Students','url'=>SITE_URL.'/admin/students/'],['label'=>$s['name'],'active'=>true]];
$page_action='<div class="d-flex gap-2"><a href="'.SITE_URL.'/admin/students/id-card.php?id='.$id.'" class="btn btn-outline-secondary btn-sm"><i class="bi bi-card-text me-1"></i>ID Card</a><a href="'.SITE_URL.'/admin/students/edit.php?id='.$id.'" class="btn btn-outline-warning btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a><a href="'.SITE_URL.'/admin/students/delete.php?id='.$id.'" class="btn btn-outline-danger btn-sm" onclick="return confirm(\'Delete?\')"><i class="bi bi-trash me-1"></i>Delete</a></div>';
include INCLUDES_PATH.'header.php';
?>
<div class="row g-3">
<div class="col-12 col-lg-4">
<div class="card mb-3"><div class="card-body text-center py-4">
<?php if($s['photo']):?><img src="<?=get_upload_url($s['photo'])?>" class="rounded-circle border mb-3" width="90" height="90" style="object-fit:cover;border-color:rgba(245,166,35,0.3)!important;"><?php else:?><div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#4f8ef7,#7b5ea7);display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-size:2rem;font-weight:800;color:#fff;margin:0 auto 16px;"><?=strtoupper(substr($s['name'],0,1))?></div><?php endif;?>
<h5 style="font-family:'Playfair Display',serif;color:#fff;margin:0 0 4px;"><?=sanitize($s['name'])?></h5>
<code style="color:#f5a623;font-size:0.8rem;"><?=sanitize($s['student_id'])?></code>
<div class="mt-2"><span class="badge bg-<?=$s['status']==='active'?'success':'secondary'?>"><?=ucfirst($s['status'])?></span></div>
<div class="mt-3 text-start" style="font-size:0.82rem;">
<?php foreach([['bi-mortarboard','Class',($s['class_name']??'—').($s['section_name']?' — '.$s['section_name']:'')],['bi-calendar','DOB',format_date($s['dob'])],['bi-gender-ambiguous','Gender',$s['gender']??'—'],['bi-droplet','Blood',$s['blood_group']??'—'],['bi-telephone','Phone',$s['phone']??'—'],['bi-envelope','Email',$s['email']??'—']] as [$icon,$label,$val]):?>
<div class="d-flex gap-2 mb-2 align-items-center"><i class="bi bi-<?=$icon?>" style="color:#f5a623;flex-shrink:0;width:16px;text-align:center;"></i><div><div style="font-size:0.68rem;color:var(--text-muted);"><?=$label?></div><div style="color:#fff;font-weight:500;"><?=sanitize((string)$val)?></div></div></div>
<?php endforeach;?>
</div></div></div>
<?php if($s['parent_name']):?><div class="card"><div class="card-header"><i class="bi bi-people" style="color:#7b5ea7;margin-right:8px;"></i>Parent Info</div><div class="card-body" style="font-size:0.85rem;">
<p style="color:#fff;font-weight:600;margin-bottom:4px;"><?=sanitize($s['parent_name'])?> <span style="font-weight:400;color:var(--text-muted);">(<?=sanitize($s['relation']??'Parent')?>)</span></p>
<?php if($s['parent_phone']):?><p style="color:var(--text-muted);margin-bottom:2px;"><i class="bi bi-telephone me-2"></i><?=sanitize($s['parent_phone'])?></p><?php endif;?>
<?php if($s['parent_email']):?><p style="color:var(--text-muted);margin:0;"><i class="bi bi-envelope me-2"></i><?=sanitize($s['parent_email'])?></p><?php endif;?>
</div></div><?php endif;?>
</div>
<div class="col-12 col-lg-8">
<div class="row g-3 mb-3">
<div class="col-4"><div class="card text-center py-3"><div style="font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:800;color:#22c55e;"><?=$att['present']??0?></div><div style="font-size:0.75rem;color:var(--text-muted);">Present</div></div></div>
<div class="col-4"><div class="card text-center py-3"><div style="font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:800;color:#ef4444;"><?=$att['absent']??0?></div><div style="font-size:0.75rem;color:var(--text-muted);">Absent</div></div></div>
<div class="col-4"><div class="card text-center py-3"><div style="font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:800;color:#4f8ef7;"><?=$att['percentage']??0?>%</div><div style="font-size:0.75rem;color:var(--text-muted);">Attendance</div></div></div>
</div>
<div class="card mb-3"><div class="card-header d-flex justify-content-between"><span><i class="bi bi-receipt" style="color:#f5a623;margin-right:8px;"></i>Fee Summary</span><a href="<?=SITE_URL?>/admin/fees/?student_id=<?=$id?>" style="font-size:0.75rem;color:var(--gold);">Manage</a></div><div class="card-body"><div class="row g-2 text-center">
<div class="col-4"><div style="font-weight:700;color:#fff;"><?=currency_format((float)($fees['total']??0))?></div><div style="font-size:0.72rem;color:var(--text-muted);">Total</div></div>
<div class="col-4"><div style="font-weight:700;color:#22c55e;"><?=currency_format((float)($fees['paid']??0))?></div><div style="font-size:0.72rem;color:var(--text-muted);">Paid</div></div>
<div class="col-4"><div style="font-weight:700;color:#ef4444;"><?=currency_format((float)($fees['pending']??0))?></div><div style="font-size:0.72rem;color:var(--text-muted);">Pending</div></div>
</div></div></div>
<div class="card"><div class="card-header d-flex justify-content-between"><span><i class="bi bi-award" style="color:#f5a623;margin-right:8px;"></i>Results</span><a href="<?=SITE_URL?>/admin/results/?student_id=<?=$id?>" style="font-size:0.75rem;color:var(--gold);">All</a></div>
<div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Exam</th><th>%</th><th>Grade</th><th>Result</th><th>Report</th></tr></thead><tbody>
<?php if(empty($results)):?><tr><td colspan="5" class="text-center py-3" style="color:var(--text-muted);">No results</td></tr>
<?php else:foreach($results as $r):?>
<tr><td style="font-size:0.82rem;"><?=sanitize($r['exam_name'])?><br><span style="font-size:0.72rem;color:var(--text-muted);"><?=$r['type']?></span></td>
<td style="font-weight:600;"><?=$r['percentage']?>%</td>
<td><span class="badge bg-<?=get_grade_color($r['grade']??'F')?>"><?=$r['grade']?></span></td>
<td><span class="badge bg-<?=$r['result']==='Pass'?'success':'danger'?>"><?=$r['result']?></span></td>
<td><a href="<?=SITE_URL?>/admin/results/report-card.php?exam_id=<?=$r['exam_id']?>&student_id=<?=$id?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-printer"></i></a></td></tr>
<?php endforeach;endif;?>
</tbody></table></div></div>
</div></div>
<?php include INCLUDES_PATH.'footer.php';?>
