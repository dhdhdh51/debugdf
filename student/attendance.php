<?php
require_once dirname(__DIR__).'/config/config.php';
require_once INCLUDES_PATH.'auth_check.php';
auth_guard('student');
$student=get_student_by_user_id((int)$_SESSION['user_id']);
if(!$student)redirect(SITE_URL.'/auth/login.php');
$month=sanitize($_GET['month']??date('Y-m'));
$att=attendance_summary((int)$student['id']);
$att_month=attendance_summary((int)$student['id'],$month);
$stmt=$pdo->prepare("SELECT date,status FROM attendance WHERE student_id=? AND DATE_FORMAT(date,'%Y-%m')=? ORDER BY date DESC");
$stmt->execute([$student['id'],$month]);$records=$stmt->fetchAll();
$months_stmt=$pdo->prepare("SELECT DISTINCT DATE_FORMAT(date,'%Y-%m') as ym FROM attendance WHERE student_id=? ORDER BY ym DESC");
$months_stmt->execute([$student['id']]);$available_months=$months_stmt->fetchAll(PDO::FETCH_COLUMN);
$page_title='My Attendance';
$breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/student/'],['label'=>'Attendance','active'=>true]];
include INCLUDES_PATH.'header.php';
?>
<div class="row g-3 mb-3">
<?php foreach([['Present',$att['present']??0,'#22c55e','calendar-check'],['Absent',$att['absent']??0,'#ef4444','calendar-x'],['Late',$att['late']??0,'#f5a623','calendar-minus'],['Overall',$att['percentage'].'%','#4f8ef7','percent']] as [$l,$v,$c,$i]):?>
<div class="col-6 col-md-3"><div class="card text-center py-3"><i class="bi bi-<?=$i?>" style="font-size:1.6rem;color:<?=$c?>;display:block;margin-bottom:6px;"></i><div style="font-family:'Playfair Display',serif;font-size:1.6rem;font-weight:800;color:<?=$c?>;"><?=$v?></div><div style="font-size:0.75rem;color:var(--text-muted);"><?=$l?></div></div></div>
<?php endforeach;?>
</div>
<div class="card mb-3"><div class="card-body py-2">
<form method="GET" class="d-flex gap-2 align-items-center">
<label class="form-label mb-0" style="font-size:0.82rem;">Month:</label>
<select name="month" class="form-select form-select-sm" style="max-width:180px;" onchange="this.form.submit()">
<option value="<?=date('Y-m')?>" <?=$month===date('Y-m')?'selected':''?>><?=date('M Y')?></option>
<?php foreach($available_months as $ym):if($ym===date('Y-m'))continue;?><option value="<?=$ym?>" <?=$month===$ym?'selected':''?>><?=date('M Y',strtotime($ym.'-01'))?></option><?php endforeach;?>
</select></form></div></div>
<div class="card"><div class="card-header d-flex justify-content-between"><span><i class="bi bi-calendar3" style="color:#4f8ef7;margin-right:8px;"></i><?=date('F Y',strtotime($month.'-01'))?></span><span style="font-size:0.78rem;color:#22c55e;"><?=$att_month['percentage']??0?>% this month</span></div>
<div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>#</th><th>Date</th><th>Day</th><th>Status</th></tr></thead><tbody>
<?php if(empty($records)):?><tr><td colspan="4" class="text-center py-5" style="color:var(--text-muted);">No records for this month.</td></tr>
<?php else:foreach($records as $i=>$r):?>
<tr><td style="color:var(--text-muted);font-size:0.8rem;"><?=$i+1?></td><td style="font-weight:600;color:#fff;"><?=date('d M Y',strtotime($r['date']))?></td><td style="color:var(--text-muted);"><?=date('l',strtotime($r['date']))?></td>
<td><span class="badge bg-<?=match($r['status']){'Present'=>'success','Absent'=>'danger','Late'=>'warning',default=>'info'}?>"><?=$r['status']?></span></td></tr>
<?php endforeach;endif;?>
</tbody></table></div></div>
<?php include INCLUDES_PATH.'footer.php';?>
