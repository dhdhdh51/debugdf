<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('parent');
$parent=get_parent_by_user_id((int)$_SESSION['user_id']);
if(!$parent) redirect(SITE_URL.'/auth/login.php');
$child_id=sanitize_int($_GET['id']??0);
$stmt=$pdo->prepare("SELECT s.*,c.name as class_name,sec.name as section_name FROM students s LEFT JOIN classes c ON s.class_id=c.id LEFT JOIN sections sec ON s.section_id=sec.id WHERE s.id=? AND s.parent_id=? LIMIT 1");
$stmt->execute([$child_id,$parent['id']]); $child=$stmt->fetch();
if(!$child){set_flash('error','Child not found.');redirect(SITE_URL.'/parent/');}
$att=attendance_summary((int)$child['id']); $month=sanitize($_GET['month']??date('Y-m')); $att_month=attendance_summary((int)$child['id'],$month);
$results_stmt=$pdo->prepare("SELECT r.*,e.name as exam_name,e.type FROM results r JOIN exams e ON r.exam_id=e.id WHERE r.student_id=? AND r.published=1 ORDER BY r.created_at DESC LIMIT 5");$results_stmt->execute([$child['id']]);$results=$results_stmt->fetchAll();
$att_records_stmt=$pdo->prepare("SELECT date,status FROM attendance WHERE student_id=? AND DATE_FORMAT(date,'%Y-%m')=? ORDER BY date DESC");$att_records_stmt->execute([$child['id'],$month]);$att_records=$att_records_stmt->fetchAll();
$page_title=sanitize($child['name']).' — Details';
$breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/parent/'],['label'=>$child['name'],'active'=>true]];
include INCLUDES_PATH.'header.php';
?>
<div class="row g-3">
  <div class="col-12 col-lg-4">
    <div class="card mb-3"><div class="card-body text-center py-4">
      <?php if($child['photo']):?><img src="<?=get_upload_url($child['photo'])?>" class="rounded-circle border mb-3" width="80" height="80" style="object-fit:cover;border-color:rgba(245,166,35,0.4)!important;">
      <?php else:?><div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#4f8ef7,#7b5ea7);display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-size:2rem;font-weight:800;color:#fff;margin:0 auto 12px;"><?=strtoupper(substr($child['name'],0,1))?></div><?php endif;?>
      <h5 style="font-family:'Playfair Display',serif;font-weight:700;color:#fff;margin-bottom:4px;"><?=sanitize($child['name'])?></h5>
      <code style="color:#f5a623;"><?=sanitize($child['student_id'])?></code>
      <div class="mt-2"><span class="badge bg-primary"><?=sanitize(($child['class_name']??'').($child['section_name']?' — '.$child['section_name']:''))?></span></div>
    </div></div>
    <div class="card"><div class="card-body text-center">
      <div style="font-family:'Playfair Display',serif;font-size:2.2rem;font-weight:800;color:<?=($att['percentage']??0)>=75?'#22c55e':(($att['percentage']??0)>=60?'#f5a623':'#ef4444')?>"><?=$att['percentage']??0?>%</div>
      <div style="font-size:0.75rem;color:var(--text-muted);margin-bottom:12px;">Overall Attendance</div>
      <div class="d-flex justify-content-around" style="font-size:0.8rem;">
        <span style="color:#22c55e;"><?=$att['present']??0?> Present</span>
        <span style="color:#ef4444;"><?=$att['absent']??0?> Absent</span>
        <span style="color:#f5a623;"><?=$att['late']??0?> Late</span>
      </div>
    </div></div>
  </div>
  <div class="col-12 col-lg-8">
    <div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="bi bi-award me-2" style="color:#f5a623;"></i>Recent Results</span>
    </div><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Exam</th><th>Total</th><th>%</th><th>Grade</th><th>Result</th><th>Report</th></tr></thead>
      <tbody><?php if(empty($results)):?><tr><td colspan="6" class="text-center py-3" style="color:var(--text-muted);">No published results.</td></tr>
      <?php else:foreach($results as $r):?><tr>
        <td><?=sanitize($r['exam_name'])?></td><td><?=$r['total_marks']?>/<?=$r['max_marks']?></td><td><?=$r['percentage']?>%</td>
        <td><span class="badge bg-<?=get_grade_color($r['grade']??'F')?>"><?=$r['grade']?></span></td>
        <td><span class="badge bg-<?=$r['result']==='Pass'?'success':'danger'?>"><?=$r['result']?></span></td>
        <td><a href="<?=SITE_URL?>/admin/results/report-card.php?exam_id=<?=$r['exam_id']?>&student_id=<?=$child['id']?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-printer"></i></a></td>
      </tr><?php endforeach;endif;?></tbody>
    </table></div></div>
    <div class="card"><div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="bi bi-calendar3 me-2" style="color:#4f8ef7;"></i>Monthly Attendance</span>
      <form method="GET"><input type="hidden" name="id" value="<?=$child_id?>"><select name="month" class="form-select form-select-sm" onchange="this.form.submit()"><?php for($m=0;$m<12;$m++){$ym=date('Y-m',strtotime("-{$m} months"));?><option value="<?=$ym?>" <?=$month===$ym?'selected':''?>><?=date('M Y',strtotime($ym.'-01'))?></option><?php }?></select></form>
    </div><div class="card-body">
      <div class="d-flex gap-3 mb-3 flex-wrap">
        <span class="badge bg-success px-3 py-2">Present: <?=$att_month['present']??0?></span>
        <span class="badge bg-danger px-3 py-2">Absent: <?=$att_month['absent']??0?></span>
        <span class="badge bg-primary px-3 py-2"><?=$att_month['percentage']??0?>%</span>
      </div>
      <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Day</th><th>Status</th></tr></thead>
        <tbody><?php if(empty($att_records)):?><tr><td colspan="3" class="text-center py-3" style="color:var(--text-muted);">No records this month.</td></tr>
        <?php else:foreach($att_records as $a):?><tr>
          <td><?=date('d M Y',strtotime($a['date']))?></td><td style="color:var(--text-muted);"><?=date('D',strtotime($a['date']))?></td>
          <td><span class="badge bg-<?=match($a['status']){'Present'=>'success','Absent'=>'danger','Late'=>'warning',default=>'info'}?>"><?=$a['status']?></span></td>
        </tr><?php endforeach;endif;?></tbody>
      </table></div>
    </div></div>
  </div>
</div>
<?php include INCLUDES_PATH.'footer.php';?>
