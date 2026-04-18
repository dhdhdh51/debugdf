<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
// Allow both admin and student access
if (!is_logged_in()) redirect(SITE_URL.'/auth/login.php');
$role = get_user_role();
if (!in_array($role, ['admin','student','teacher','parent'])) redirect(SITE_URL.'/auth/login.php');

$exam_id    = sanitize_int($_GET['exam_id']    ?? 0);
$student_id = sanitize_int($_GET['student_id'] ?? 0);

// If student role, force their own student_id
if ($role === 'student') {
    $stu = get_student_by_user_id((int)$_SESSION['user_id']);
    if (!$stu) redirect(SITE_URL.'/student/');
    $student_id = (int)$stu['id'];
}

if (!$exam_id || !$student_id) die('<p style="color:#fff;text-align:center;padding:40px;">Invalid parameters.</p>');

$stmt=$pdo->prepare("SELECT e.*,c.name as class_name FROM exams e LEFT JOIN classes c ON e.class_id=c.id WHERE e.id=? LIMIT 1");$stmt->execute([$exam_id]);$exam=$stmt->fetch();
$stmt=$pdo->prepare("SELECT s.*,c.name as class_name,sec.name as section_name,p.name as parent_name,p.phone as parent_phone FROM students s LEFT JOIN classes c ON s.class_id=c.id LEFT JOIN sections sec ON s.section_id=sec.id LEFT JOIN parents p ON s.parent_id=p.id WHERE s.id=?");$stmt->execute([$student_id]);$student=$stmt->fetch();
if(!$exam||!$student) die('<p style="color:#fff;text-align:center;padding:40px;">Not found.</p>');

// For non-admin roles, verify result is published
if($role!=='admin'){
    $pub=$pdo->prepare("SELECT published FROM results WHERE exam_id=? AND student_id=? LIMIT 1");$pub->execute([$exam_id,$student_id]);$pubrow=$pub->fetch();
    if(!$pubrow||!$pubrow['published']) redirect(SITE_URL.'/'.$role.'/');
}

$stmt=$pdo->prepare("SELECT m.*,sub.name as subject_name,sub.code as subject_code FROM marks m JOIN subjects sub ON m.subject_id=sub.id WHERE m.exam_id=? AND m.student_id=? ORDER BY sub.name");$stmt->execute([$exam_id,$student_id]);$marks=$stmt->fetchAll();
$stmt=$pdo->prepare("SELECT * FROM results WHERE exam_id=? AND student_id=?");$stmt->execute([$exam_id,$student_id]);$result=$stmt->fetch();
$site_name=get_setting('site_name','School ERP');$site_logo=get_setting('site_logo','');$contact=get_setting('contact_address','');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Report Card — <?=sanitize($student['name']??'')?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body{font-family:'DM Sans',Arial,sans-serif;background:#f0f0f0}
    .rc{max-width:800px;margin:20px auto;background:#fff;border:2px solid #f5a623;border-radius:12px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.15)}
    .rc-head{background:linear-gradient(135deg,#0a0f1e,#1a2545);padding:22px;display:flex;align-items:center;gap:16px}
    .rc-title{font-family:'Playfair Display',serif;font-size:20px;font-weight:800;color:#fff;margin:0}
    .rc-sub{font-size:12px;color:rgba(255,255,255,0.6)}
    .rc-badge{background:linear-gradient(135deg,#f5a623,#c47f0a);color:#0a0f1e;padding:3px 12px;border-radius:99px;font-size:11px;font-weight:700;display:inline-block;margin-top:4px}
    .rc-body{padding:24px}
    .info-table td{padding:5px 10px;font-size:13px}
    .info-table td:first-child{font-weight:600;color:#555;width:130px}
    .marks-table th{background:#0a0f1e;color:#fff;font-size:12px;text-transform:uppercase;letter-spacing:0.05em}
    .marks-table td{font-size:13px}
    .grade-pill{display:inline-block;padding:2px 10px;border-radius:20px;font-weight:700;font-size:13px}
    .watermark{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) rotate(-30deg);font-size:80px;color:rgba(245,166,35,0.04);pointer-events:none;z-index:0;font-weight:900;white-space:nowrap;font-family:'Playfair Display',serif}
    @media print{body{background:#fff!important}.no-print{display:none!important}.rc{margin:0!important;border:1px solid #333!important;box-shadow:none!important}@page{margin:8mm}}
  </style>
</head>
<body>
<div class="no-print text-center py-3 bg-white border-bottom">
  <button onclick="window.print()" class="btn btn-warning fw-bold me-2"><i class="bi bi-printer me-1"></i>Print / Save PDF</button>
  <a href="javascript:history.back()" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<div class="watermark"><?=sanitize($site_name)?></div>
<div class="rc">
  <div class="rc-head">
    <?php if($site_logo):?><img src="<?=get_upload_url($site_logo)?>" height="56" class="rounded-2" alt="Logo"><?php else:?><div style="width:56px;height:56px;border-radius:12px;background:linear-gradient(135deg,#f5a623,#c47f0a);display:flex;align-items:center;justify-content:center;font-size:1.6rem;flex-shrink:0">🎓</div><?php endif;?>
    <div>
      <div class="rc-title"><?=sanitize($site_name)?></div>
      <div class="rc-sub"><?=sanitize($contact)?></div>
      <div class="rc-badge">STUDENT REPORT CARD — <?=get_setting('academic_year','2025-2026')?></div>
    </div>
    <?php if($student['photo']):?><img src="<?=get_upload_url($student['photo'])?>" class="ms-auto rounded-3" style="width:68px;height:68px;object-fit:cover;border:2px solid rgba(245,166,35,0.5)" alt="Photo"><?php endif;?>
  </div>
  <div class="rc-body">
    <div class="row g-0 mb-4">
      <div class="col-12 col-md-6">
        <table class="info-table w-100">
          <tr><td>Student Name:</td><td class="fw-bold"><?=sanitize($student['name'])?></td></tr>
          <tr><td>Student ID:</td><td><code style="color:#f5a623"><?=sanitize($student['student_id'])?></code></td></tr>
          <tr><td>Class:</td><td><?=sanitize(($student['class_name']??'').($student['section_name']?' — '.$student['section_name']:''))?></td></tr>
          <tr><td>Exam:</td><td><?=sanitize($exam['name']??'')?> (<?=sanitize($exam['type']??'')?>)</td></tr>
        </table>
      </div>
      <div class="col-12 col-md-6">
        <table class="info-table w-100">
          <tr><td>Date of Birth:</td><td><?=format_date($student['dob']??'')?></td></tr>
          <tr><td>Gender:</td><td><?=sanitize($student['gender']??'—')?></td></tr>
          <tr><td>Parent:</td><td><?=sanitize($student['parent_name']??'—')?></td></tr>
          <tr><td>Exam Date:</td><td><?=format_date($exam['start_date']??'')?></td></tr>
        </table>
      </div>
    </div>
    <h6 class="fw-bold border-bottom pb-2 mb-3" style="font-family:'Playfair Display',serif">Subject-wise Performance</h6>
    <div class="table-responsive mb-4">
      <table class="table table-bordered marks-table">
        <thead><tr><th>#</th><th>Subject</th><th>Code</th><th>Max</th><th>Obtained</th><th>%</th><th>Grade</th><th>Remarks</th></tr></thead>
        <tbody>
          <?php if(empty($marks)):?><tr><td colspan="8" class="text-center text-muted">No marks recorded</td></tr>
          <?php else:$pp=get_pass_percentage();foreach($marks as $i=>$m):$pct=$m['max_marks']>0?round(($m['marks_obtained']/$m['max_marks'])*100,1):0;$grade=calculate_grade($pct);$remark=$pct>=75?'Excellent':($pct>=60?'Good':($pct>=33?'Average':'Needs Improvement'));?>
          <tr>
            <td class="text-center"><?=$i+1?></td><td class="fw-semibold"><?=sanitize($m['subject_name'])?></td>
            <td><code><?=sanitize($m['subject_code']??'')?></code></td>
            <td class="text-center"><?=$m['max_marks']?></td>
            <td class="text-center fw-bold" style="color:<?=$pct<$pp?'#ef4444':'#22c55e'?>"><?=$m['marks_obtained']?></td>
            <td class="text-center"><?=$pct?>%</td>
            <td class="text-center"><span class="grade-pill" style="background:rgba(245,166,35,0.15);color:#c47f0a;border:1px solid rgba(245,166,35,0.3)"><?=$grade?></span></td>
            <td class="text-muted small"><?=$remark?></td>
          </tr>
          <?php endforeach;endif;?>
        </tbody>
      </table>
    </div>
    <?php if($result):?>
    <div class="row g-3 mb-4">
      <?php foreach([['Total','<strong>'.$result['total_marks'].'</strong>/'.$result['max_marks'],'#e8eaf6'],['Percentage',$result['percentage'].'%','#f5a623'],['Grade',$result['grade'],'#f5a623'],['Result',$result['result'],$result['result']==='Pass'?'#22c55e':'#ef4444']] as [$lbl,$val,$col]):?>
      <div class="col-6 col-md-3"><div style="text-align:center;padding:16px;background:#0a0f1e;border-radius:10px;border:1px solid rgba(245,166,35,0.2)"><div style="font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:800;color:<?=$col?>"><?=$val?></div><div style="font-size:0.72rem;color:rgba(255,255,255,0.5)"><?=$lbl?></div></div></div>
      <?php endforeach;?>
    </div>
    <?php endif;?>
    <div class="row mt-5 pt-3">
      <div class="col-4 text-center"><div class="border-top pt-2"><small style="color:#666">Class Teacher</small></div></div>
      <div class="col-4 text-center"><div class="border-top pt-2"><small style="color:#666">Examiner</small></div></div>
      <div class="col-4 text-center"><div class="border-top pt-2"><small style="color:#666">Principal</small></div></div>
    </div>
    <div class="text-center mt-4 border-top pt-3" style="font-size:0.75rem;color:#999">
      <?=sanitize(get_setting('footer_text',''))?> | Generated: <?=date('d M Y H:i')?>
    </div>
  </div>
</div>
<script>if(new URLSearchParams(location.search).get('print')==='1')window.print();</script>
</body></html>
