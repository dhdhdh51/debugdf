<?php
require_once dirname(__DIR__,2).'/config/config.php';
require_once INCLUDES_PATH.'auth_check.php';
auth_guard('admin');
$id=sanitize_int($_GET['id']??0);if(!$id)redirect(SITE_URL.'/admin/students/');
$stmt=$pdo->prepare("SELECT s.*,c.name as class_name,sec.name as section_name,p.name as parent_name,p.phone as parent_phone FROM students s LEFT JOIN classes c ON s.class_id=c.id LEFT JOIN sections sec ON s.section_id=sec.id LEFT JOIN parents p ON s.parent_id=p.id WHERE s.id=? LIMIT 1");
$stmt->execute([$id]);$s=$stmt->fetch();if(!$s)redirect(SITE_URL.'/admin/students/');
$site_name=get_setting('site_name','School ERP');$site_logo=get_setting('site_logo','');
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>ID Card - <?=sanitize($s['name'])?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body{background:#0a0f1e;font-family:'DM Sans',Arial,sans-serif;}
.id-card{width:340px;border-radius:20px;overflow:hidden;background:#0f1729;border:1px solid rgba(245,166,35,0.3);box-shadow:0 8px 40px rgba(0,0,0,0.5);}
.id-header{background:linear-gradient(135deg,#f5a623,#c47f0a);padding:18px;color:#0a0f1e;text-align:center;}
.id-header h5{margin:4px 0 0;font-size:15px;font-weight:800;}
.id-body{padding:18px;}
.stu-photo{width:80px;height:80px;border-radius:50%;border:3px solid rgba(245,166,35,0.5);object-fit:cover;}
.stu-photo-ph{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#4f8ef7,#7b5ea7);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:800;color:#fff;border:3px solid rgba(245,166,35,0.4);}
.info-row{display:flex;gap:8px;margin-bottom:5px;font-size:12px;color:#e8eaf6;}
.info-lbl{color:#8892b0;min-width:70px;}
.id-footer{background:rgba(255,255,255,0.03);border-top:1px solid rgba(255,255,255,0.06);padding:10px 16px;font-size:11px;color:#8892b0;text-align:center;}
@media print{body{background:#fff!important;}.no-print{display:none!important;}.id-card{box-shadow:none;border:2px solid #333;background:#fff!important;}*{color-adjust:exact;-webkit-print-color-adjust:exact;}}
</style></head><body>
<div class="no-print text-center py-3"><button onclick="window.print()" class="btn btn-warning me-2"><i class="bi bi-printer me-1"></i>Print ID Card</button><a href="<?=SITE_URL?>/admin/students/view.php?id=<?=$id?>" class="btn btn-outline-secondary">← Back</a></div>
<div class="d-flex justify-content-center gap-4 py-3 flex-wrap">
<div class="id-card">
<div class="id-header"><?php if($site_logo):?><img src="<?=get_upload_url($site_logo)?>" height="36" class="rounded mb-1"><?php endif;?><h5><?=sanitize($site_name)?></h5><small style="font-size:11px;opacity:0.8;">Student Identity Card</small></div>
<div class="id-body">
<div class="d-flex gap-3 align-items-center mb-3">
<?php if($s['photo']):?><img src="<?=get_upload_url($s['photo'])?>" class="stu-photo"><?php else:?><div class="stu-photo-ph"><?=strtoupper(substr($s['name'],0,1))?></div><?php endif;?>
<div><div style="font-size:16px;font-weight:800;color:#fff;"><?=sanitize($s['name'])?></div><div style="color:#f5a623;font-size:13px;font-weight:600;"><?=sanitize($s['student_id'])?></div><span style="background:rgba(79,142,247,0.2);color:#93c5fd;border-radius:4px;padding:2px 8px;font-size:10px;"><?=sanitize(($s['class_name']??'').($s['section_name']?' — '.$s['section_name']:''))?></span></div>
</div>
<?php foreach([['DOB',format_date($s['dob'])],['Gender',$s['gender']??'—'],['Blood',$s['blood_group']??'—'],['Phone',$s['phone']??'—'],['Parent',$s['parent_name']??'—'],['Admitted',format_date($s['admission_date'])]] as [$l,$v]):?>
<div class="info-row"><span class="info-lbl"><?=$l?>:</span><span style="font-weight:600;"><?=sanitize((string)$v)?></span></div>
<?php endforeach;?>
<div style="text-align:center;margin-top:12px;font-family:monospace;font-size:12px;letter-spacing:3px;color:#f5a623;">||||| <?=sanitize($s['student_id'])?> |||||</div>
</div>
<div class="id-footer"><?=sanitize(get_setting('contact_phone',''))?> | <?=sanitize(get_setting('contact_email',''))?></div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
