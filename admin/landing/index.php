<?php
require_once dirname(__DIR__,2).'/config/config.php';
require_once INCLUDES_PATH.'auth_check.php';
auth_guard('admin');
$page_title='Landing Page';$breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Landing Page','active'=>true]];
$tab=sanitize($_GET['tab']??'hero');
if($_SERVER['REQUEST_METHOD']==='POST'){
csrf_protect();$action=sanitize($_POST['action']??'');
$upsert=$pdo->prepare("INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
if($action==='delete_gallery'){$gid=sanitize_int($_POST['gallery_id']??0);if($gid){$stmt=$pdo->prepare("SELECT image FROM gallery WHERE id=?");$stmt->execute([$gid]);$row=$stmt->fetch();if($row)delete_upload($row['image']);$pdo->prepare("DELETE FROM gallery WHERE id=?")->execute([$gid]);}set_flash('success','Deleted.');redirect(SITE_URL.'/admin/landing/?tab=gallery');}
if($action==='toggle_gallery'){$gid=sanitize_int($_POST['gallery_id']??0);if($gid)$pdo->prepare("UPDATE gallery SET is_active=1-is_active WHERE id=?")->execute([$gid]);redirect(SITE_URL.'/admin/landing/?tab=gallery');}
if($action==='delete_notice'){$nid=sanitize_int($_POST['notice_id']??0);if($nid)$pdo->prepare("DELETE FROM notices WHERE id=?")->execute([$nid]);set_flash('success','Deleted.');redirect(SITE_URL.'/admin/landing/?tab=notices');}
if($action==='toggle_notice'){$nid=sanitize_int($_POST['notice_id']??0);if($nid)$pdo->prepare("UPDATE notices SET is_active=1-is_active WHERE id=?")->execute([$nid]);redirect(SITE_URL.'/admin/landing/?tab=notices');}
if($action==='add_notice'){$title=sanitize($_POST['notice_title']??'');$body=sanitize($_POST['notice_body']??'');$category=sanitize($_POST['notice_cat']??'general');if($title){$pdo->prepare("INSERT INTO notices (title,body,category,is_active) VALUES (?,?,?,1)")->execute([$title,$body,$category]);set_flash('success','Notice added.');}redirect(SITE_URL.'/admin/landing/?tab=notices');}
if($action==='add_gallery'){if(!empty($_FILES['gallery_images']['name'][0])){$count=count($_FILES['gallery_images']['name']);$uploaded=0;for($i=0;$i<$count;$i++){$file=['name'=>$_FILES['gallery_images']['name'][$i],'type'=>$_FILES['gallery_images']['type'][$i],'tmp_name'=>$_FILES['gallery_images']['tmp_name'][$i],'error'=>$_FILES['gallery_images']['error'][$i],'size'=>$_FILES['gallery_images']['size'][$i]];if($file['error']!==UPLOAD_ERR_OK)continue;$path=upload_file($file,'gallery',ALLOWED_IMAGES);if($path){$pdo->prepare("INSERT INTO gallery (title,image,sort_order,is_active) VALUES (?,?,0,1)")->execute([sanitize($_POST['gallery_title']??''),$path]);$uploaded++;}}set_flash('success',"$uploaded image(s) uploaded.");}redirect(SITE_URL.'/admin/landing/?tab=gallery');}
$textKeys=['lp_hero_title','lp_hero_subtitle','lp_hero_btn1_text','lp_hero_btn1_url','lp_hero_btn2_text','lp_hero_btn2_url','lp_about_title','lp_about_text','lp_stat_students','lp_stat_teachers','lp_stat_years','lp_stat_success','lp_stat_label1','lp_stat_label2','lp_stat_label3','lp_stat_label4','lp_show_gallery','lp_show_notices','lp_show_about','lp_show_stats','lp_primary_color','lp_cta_title','lp_cta_text'];
foreach($textKeys as $k){if(isset($_POST[$k]))$upsert->execute([$k,sanitize($_POST[$k])]);}
foreach(['lp_show_gallery','lp_show_notices','lp_show_about','lp_show_stats'] as $k){if(!isset($_POST[$k]))$upsert->execute([$k,'0']);}
if(!empty($_FILES['lp_hero_image']['name'])){$img=upload_file($_FILES['lp_hero_image'],'landing',ALLOWED_IMAGES);if($img)$upsert->execute(['lp_hero_image',$img]);}
if(!empty($_FILES['lp_about_image']['name'])){$img=upload_file($_FILES['lp_about_image'],'landing',ALLOWED_IMAGES);if($img)$upsert->execute(['lp_about_image',$img]);}
set_flash('success','Landing page saved!');redirect(SITE_URL.'/admin/landing/?tab='.$tab);
}
$rows=$pdo->query("SELECT setting_key,setting_value FROM settings")->fetchAll();$cfg=[];foreach($rows as $r)$cfg[$r['setting_key']]=$r['setting_value'];
function cfg(array $c,string $k,string $d=''):string{return htmlspecialchars($c[$k]??$d,ENT_QUOTES,'UTF-8');}
$gallery=$pdo->query("SELECT * FROM gallery ORDER BY sort_order ASC,id ASC")->fetchAll();
$notices=$pdo->query("SELECT * FROM notices ORDER BY created_at DESC")->fetchAll();
include INCLUDES_PATH.'header.php';
?>
<ul class="nav nav-tabs mb-4 flex-wrap">
<?php foreach(['hero'=>['Hero','image'],'about'=>['About & Stats','info-circle'],'gallery'=>['Gallery','images'],'notices'=>['Notices','bell'],'display'=>['Display','palette']] as $t=>[$label,$icon]):?>
<li class="nav-item"><a class="nav-link <?=$tab===$t?'active':''?>" href="?tab=<?=$t?>"><i class="bi bi-<?=$icon?> me-1"></i><?=$label?></a></li>
<?php endforeach;?>
<li class="nav-item ms-auto"><a href="<?=SITE_URL?>/" target="_blank" class="btn btn-sm btn-outline-primary mt-1"><i class="bi bi-eye me-1"></i>Preview</a></li>
</ul>
<?php if(in_array($tab,['hero','about','display'])):?><form method="POST" enctype="multipart/form-data"><?=csrf_field()?><input type="hidden" name="action" value="save_settings"><?php endif;?>
<?php if($tab==='hero'):?>
<div class="row g-4">
<div class="col-lg-7"><div class="card"><div class="card-header">Hero Text</div><div class="card-body">
<div class="mb-3"><label class="form-label">Main Heading</label><input type="text" name="lp_hero_title" class="form-control" value="<?=cfg($cfg,'lp_hero_title','Welcome to Our School')?>"></div>
<div class="mb-3"><label class="form-label">Sub-heading</label><textarea name="lp_hero_subtitle" class="form-control" rows="3"><?=cfg($cfg,'lp_hero_subtitle')?></textarea></div>
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Button 1 Text</label><input type="text" name="lp_hero_btn1_text" class="form-control" value="<?=cfg($cfg,'lp_hero_btn1_text','Apply for Admission')?>"></div>
<div class="col-md-6"><label class="form-label">Button 1 URL</label><input type="text" name="lp_hero_btn1_url" class="form-control" value="<?=cfg($cfg,'lp_hero_btn1_url','/public/admission.php')?>"></div>
<div class="col-md-6"><label class="form-label">Button 2 Text</label><input type="text" name="lp_hero_btn2_text" class="form-control" value="<?=cfg($cfg,'lp_hero_btn2_text','Check Status')?>"></div>
<div class="col-md-6"><label class="form-label">Button 2 URL</label><input type="text" name="lp_hero_btn2_url" class="form-control" value="<?=cfg($cfg,'lp_hero_btn2_url','/public/admission-status.php')?>"></div>
</div></div></div></div>
<div class="col-lg-5"><div class="card"><div class="card-header">Hero Background Image</div><div class="card-body">
<?php if(!empty($cfg['lp_hero_image'])):?><img src="<?=UPLOADS_URL.'/'.htmlspecialchars($cfg['lp_hero_image'],ENT_QUOTES)?>" class="img-fluid rounded mb-3" style="max-height:160px;width:100%;object-fit:cover;"><?php endif;?>
<input type="file" name="lp_hero_image" class="form-control" accept="image/*"><div class="form-text">Recommended: 1920×1080px</div>
</div></div></div></div>
<?php elseif($tab==='about'):?>
<div class="row g-4">
<div class="col-lg-7"><div class="card"><div class="card-header">About Section</div><div class="card-body">
<div class="mb-3"><label class="form-label">Section Heading</label><input type="text" name="lp_about_title" class="form-control" value="<?=cfg($cfg,'lp_about_title','About Our School')?>"></div>
<div class="mb-3"><label class="form-label">About Text</label><textarea name="lp_about_text" class="form-control" rows="6"><?=cfg($cfg,'lp_about_text')?></textarea></div>
<div class="row g-3">
<?php $sf=[['lp_stat_students','1200+','lp_stat_label1','Students'],['lp_stat_teachers','80+','lp_stat_label2','Teachers'],['lp_stat_years','25+','lp_stat_label3','Years'],['lp_stat_success','98%','lp_stat_label4','Pass Rate']];
foreach($sf as $i=>[$nk,$nd,$lk,$ld]):?>
<div class="col-6"><label class="form-label" style="font-size:0.72rem;">Stat <?=$i+1?> Number</label><input type="text" name="<?=$nk?>" class="form-control form-control-sm" value="<?=cfg($cfg,$nk,$nd)?>"></div>
<div class="col-6"><label class="form-label" style="font-size:0.72rem;">Stat <?=$i+1?> Label</label><input type="text" name="<?=$lk?>" class="form-control form-control-sm" value="<?=cfg($cfg,$lk,$ld)?>"></div>
<?php endforeach;?>
</div></div></div></div>
<div class="col-lg-5"><div class="card"><div class="card-header">About Image</div><div class="card-body">
<?php if(!empty($cfg['lp_about_image'])):?><img src="<?=UPLOADS_URL.'/'.htmlspecialchars($cfg['lp_about_image'],ENT_QUOTES)?>" class="img-fluid rounded mb-3" style="max-height:180px;width:100%;object-fit:cover;"><?php endif;?>
<input type="file" name="lp_about_image" class="form-control" accept="image/*">
</div></div></div></div>
<?php elseif($tab==='gallery'):?>
<div class="card mb-4"><div class="card-header">Upload Photos</div><div class="card-body">
<form method="POST" enctype="multipart/form-data"><?=csrf_field()?><input type="hidden" name="action" value="add_gallery">
<div class="row g-3 align-items-end">
<div class="col-md-4"><label class="form-label">Caption</label><input type="text" name="gallery_title" class="form-control" placeholder="Event name..."></div>
<div class="col-md-6"><label class="form-label">Images (multiple)</label><input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple required></div>
<div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-upload me-1"></i>Upload</button></div>
</div></form></div></div>
<?php if(empty($gallery)):?><div class="text-center py-5" style="color:var(--text-muted);"><i class="bi bi-images" style="font-size:3rem;display:block;margin-bottom:12px;"></i>No gallery images yet.</div>
<?php else:?><div class="row g-3"><?php foreach($gallery as $img):?>
<div class="col-6 col-md-4 col-lg-3"><div class="card"><div style="height:150px;overflow:hidden;border-radius:14px 14px 0 0;"><img src="<?=UPLOADS_URL.'/'.htmlspecialchars($img['image'],ENT_QUOTES)?>" class="w-100 h-100" style="object-fit:cover;"></div>
<div class="card-body p-2"><p class="small mb-2 text-truncate"><?=htmlspecialchars($img['title']?:'(no caption)',ENT_QUOTES)?></p>
<div class="d-flex gap-1">
<form method="POST" class="d-inline"><?=csrf_field()?><input type="hidden" name="action" value="toggle_gallery"><input type="hidden" name="gallery_id" value="<?=$img['id']?>"><button type="submit" class="btn btn-sm btn-<?=$img['is_active']?'success':'secondary'?>" style="font-size:0.72rem;padding:2px 8px;"><i class="bi bi-<?=$img['is_active']?'eye':'eye-slash'?>"></i></button></form>
<form method="POST" class="d-inline" onsubmit="return confirm('Delete?')"><?=csrf_field()?><input type="hidden" name="action" value="delete_gallery"><input type="hidden" name="gallery_id" value="<?=$img['id']?>"><button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:0.72rem;padding:2px 8px;"><i class="bi bi-trash"></i></button></form>
</div></div></div></div>
<?php endforeach;?></div><?php endif;?>
<?php elseif($tab==='notices'):?>
<div class="card mb-4"><div class="card-header">Add Notice</div><div class="card-body">
<form method="POST"><?=csrf_field()?><input type="hidden" name="action" value="add_notice">
<div class="row g-3 align-items-end">
<div class="col-md-4"><label class="form-label">Title</label><input type="text" name="notice_title" class="form-control" required></div>
<div class="col-md-3"><label class="form-label">Category</label><select name="notice_cat" class="form-select"><?php foreach(['general','exam','event','holiday','admission'] as $c):?><option value="<?=$c?>"><?=ucfirst($c)?></option><?php endforeach;?></select></div>
<div class="col-md-3"><label class="form-label">Body</label><input type="text" name="notice_body" class="form-control"></div>
<div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Add</button></div>
</div></form></div></div>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
<thead><tr><th>Title</th><th>Category</th><th>Date</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
<?php if(empty($notices)):?><tr><td colspan="5" class="text-center py-5" style="color:var(--text-muted);">No notices.</td></tr>
<?php else:foreach($notices as $n):?>
<tr><td style="font-weight:600;color:#fff;"><?=htmlspecialchars($n['title'],ENT_QUOTES)?></td>
<td><span class="badge bg-primary bg-opacity-20" style="font-size:0.7rem;"><?=ucfirst($n['category'])?></span></td>
<td style="font-size:0.78rem;color:var(--text-muted);"><?=date('d M Y',strtotime($n['created_at']))?></td>
<td><span class="badge bg-<?=$n['is_active']?'success':'secondary'?>"><?=$n['is_active']?'Visible':'Hidden'?></span></td>
<td class="text-end">
<form method="POST" class="d-inline"><?=csrf_field()?><input type="hidden" name="action" value="toggle_notice"><input type="hidden" name="notice_id" value="<?=$n['id']?>"><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-<?=$n['is_active']?'eye-slash':'eye'?>"></i></button></form>
<form method="POST" class="d-inline" onsubmit="return confirm('Delete?')"><?=csrf_field()?><input type="hidden" name="action" value="delete_notice"><input type="hidden" name="notice_id" value="<?=$n['id']?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
</td></tr>
<?php endforeach;endif;?>
</tbody></table></div></div></div>
<?php elseif($tab==='display'):?>
<div class="row g-4"><div class="col-lg-6"><div class="card"><div class="card-header">Show/Hide Sections</div><div class="card-body">
<?php foreach(['lp_show_about'=>'About Section','lp_show_stats'=>'Statistics Block','lp_show_gallery'=>'Photo Gallery','lp_show_notices'=>'Notices'] as $key=>$label):?>
<div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="<?=$key?>" id="<?=$key?>" value="1" <?=($cfg[$key]??'1')==='1'?'checked':''?>><label class="form-check-label" for="<?=$key?>"><?=$label?></label></div>
<?php endforeach;?>
</div></div></div>
<div class="col-lg-4"><div class="card"><div class="card-header">Brand Colour</div><div class="card-body">
<div class="input-group"><input type="color" name="lp_primary_color" class="form-control form-control-color" value="<?=cfg($cfg,'lp_primary_color','#f5a623')?>" style="max-width:60px;"></div>
</div></div></div></div>
<?php endif;?>
<?php if(in_array($tab,['hero','about','display'])):?>
<div class="mt-4"><button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-2"></i>Save Changes</button><a href="<?=SITE_URL?>/" target="_blank" class="btn btn-outline-secondary ms-2"><i class="bi bi-eye me-1"></i>Preview</a></div>
</form><?php endif;?>
<?php include INCLUDES_PATH.'footer.php';?>
