<?php
require_once dirname(__DIR__,2).'/config/config.php';
require_once INCLUDES_PATH.'auth_check.php';
auth_guard('admin');
$page_title='Testimonials';$breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Testimonials','active'=>true]];
if($_SERVER['REQUEST_METHOD']==='POST'){
csrf_protect();$action=sanitize($_POST['action']??'');
if($action==='add'){$name=sanitize($_POST['t_name']??'');$role=sanitize($_POST['t_role']??'Student');$message=sanitize($_POST['t_message']??'');$rating=sanitize_int($_POST['t_rating']??5);
if($name&&$message){$photo='';if(!empty($_FILES['t_photo']['name'])){$photo=upload_file($_FILES['t_photo'],'testimonials',ALLOWED_IMAGES)?:'';}$pdo->prepare("INSERT INTO testimonials (name,role,message,photo,rating,is_active,sort_order) VALUES (?,?,?,?,?,1,0)")->execute([$name,$role,$message,$photo,$rating]);set_flash('success','Testimonial added.');}redirect(SITE_URL.'/admin/testimonials/');}
if($action==='delete'){$id=sanitize_int($_POST['tid']??0);if($id){$stmt=$pdo->prepare("SELECT photo FROM testimonials WHERE id=?");$stmt->execute([$id]);$row=$stmt->fetch();if($row&&$row['photo'])delete_upload($row['photo']);$pdo->prepare("DELETE FROM testimonials WHERE id=?")->execute([$id]);set_flash('success','Deleted.');}redirect(SITE_URL.'/admin/testimonials/');}
if($action==='toggle'){$id=sanitize_int($_POST['tid']??0);if($id)$pdo->prepare("UPDATE testimonials SET is_active=1-is_active WHERE id=?")->execute([$id]);redirect(SITE_URL.'/admin/testimonials/');}
}
$testimonials=$pdo->query("SELECT * FROM testimonials ORDER BY sort_order ASC,id ASC")->fetchAll();
include INCLUDES_PATH.'header.php';
?>
<div class="card mb-4"><div class="card-header"><i class="bi bi-plus-circle" style="color:#f5a623;margin-right:8px;"></i>Add Testimonial</div><div class="card-body">
<form method="POST" enctype="multipart/form-data"><?=csrf_field()?><input type="hidden" name="action" value="add">
<div class="row g-3"><div class="col-md-3"><label class="form-label">Name *</label><input type="text" name="t_name" class="form-control" required></div>
<div class="col-md-2"><label class="form-label">Role</label><input type="text" name="t_role" class="form-control" value="Parent"></div>
<div class="col-md-1"><label class="form-label">Rating</label><select name="t_rating" class="form-select"><?php for($i=5;$i>=1;$i--):?><option value="<?=$i?>" <?=$i===5?'selected':''?>><?=$i?>★</option><?php endfor;?></select></div>
<div class="col-md-2"><label class="form-label">Photo</label><input type="file" name="t_photo" class="form-control" accept="image/*"></div>
<div class="col-md-3"><label class="form-label">Message *</label><textarea name="t_message" class="form-control" rows="2" required></textarea></div>
<div class="col-12"><button type="submit" class="btn btn-primary px-4"><i class="bi bi-plus me-1"></i>Add</button></div></div></form></div></div>
<div class="card"><div class="card-header"><i class="bi bi-chat-quote" style="color:#f5a623;margin-right:8px;"></i>All Testimonials (<?=count($testimonials)?>)</div>
<div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Person</th><th>Role</th><th>Rating</th><th>Message</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
<?php if(empty($testimonials)):?><tr><td colspan="6" class="text-center py-5" style="color:var(--text-muted);">No testimonials yet.</td></tr>
<?php else:foreach($testimonials as $t):?>
<tr><td><div class="d-flex align-items-center gap-2"><?php if(!empty($t['photo'])):?><img src="<?=UPLOADS_URL.'/'.htmlspecialchars($t['photo'],ENT_QUOTES)?>" class="rounded-circle" width="34" height="34" style="object-fit:cover;"><?php else:?><div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#7b5ea7,#5b3f87);display:flex;align-items:center;justify-content:center;font-size:0.85rem;font-weight:700;color:#fff;"><?=strtoupper(substr($t['name'],0,1))?></div><?php endif;?><span style="font-weight:600;color:#fff;"><?=htmlspecialchars($t['name'],ENT_QUOTES)?></span></div></td>
<td style="font-size:0.82rem;color:var(--text-muted);"><?=htmlspecialchars($t['role'],ENT_QUOTES)?></td>
<td><?php for($i=1;$i<=5;$i++):?><i class="bi bi-star-fill" style="font-size:0.72rem;color:<?=$i<=(int)$t['rating']?'#f5a623':'rgba(255,255,255,0.15)';?>;"></i><?php endfor;?></td>
<td style="max-width:280px;font-size:0.82rem;color:var(--text-muted);"><?=htmlspecialchars(mb_substr($t['message'],0,80),ENT_QUOTES)?><?=mb_strlen($t['message'])>80?'…':''?></td>
<td><span class="badge bg-<?=$t['is_active']?'success':'secondary'?>"><?=$t['is_active']?'Visible':'Hidden'?></span></td>
<td class="text-end"><form method="POST" class="d-inline"><?=csrf_field()?><input type="hidden" name="action" value="toggle"><input type="hidden" name="tid" value="<?=$t['id']?>"><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-<?=$t['is_active']?'eye-slash':'eye'?>"></i></button></form>
<form method="POST" class="d-inline" onsubmit="return confirm('Delete?')"><?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="tid" value="<?=$t['id']?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td></tr>
<?php endforeach;endif;?>
</tbody></table></div></div>
<?php include INCLUDES_PATH.'footer.php';?>
