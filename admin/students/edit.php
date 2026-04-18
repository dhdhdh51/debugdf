<?php
require_once dirname(__DIR__,2).'/config/config.php';
require_once INCLUDES_PATH.'auth_check.php';
auth_guard('admin');
$id=sanitize_int($_GET['id']??0);
$stmt=$pdo->prepare("SELECT * FROM students WHERE id=? LIMIT 1");$stmt->execute([$id]);$s=$stmt->fetch();
if(!$s){set_flash('error','Student not found.');redirect(SITE_URL.'/admin/students/');}
$classes=get_classes();$sections=$s['class_id']?get_sections_by_class((int)$s['class_id']):[];$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
csrf_protect();
$name=sanitize($_POST['name']??'');$email=sanitize_email($_POST['email']??'');$phone=sanitize($_POST['phone']??'');
$dob=sanitize($_POST['dob']??'');$gender=sanitize($_POST['gender']??'');$blood=sanitize($_POST['blood_group']??'');
$address=sanitize($_POST['address']??'');$class_id=sanitize_int($_POST['class_id']??0);
$section_id=sanitize_int($_POST['section_id']??0);$adm_date=sanitize($_POST['admission_date']??'');
$status=sanitize($_POST['status']??'active');
if(empty($name))$errors[]='Name is required.';
$photo=$s['photo'];
if(!empty($_FILES['photo']['name'])){$np=upload_file($_FILES['photo'],'students',ALLOWED_IMAGES);if($np===false){$errors[]='Invalid photo.';}else{if($photo)delete_upload($photo);$photo=$np;}}
if(empty($errors)){
$pdo->prepare("UPDATE students SET name=?,email=?,phone=?,dob=?,gender=?,blood_group=?,address=?,class_id=?,section_id=?,photo=?,admission_date=?,status=? WHERE id=?")->execute([$name,$email?:null,$phone?:null,$dob?:null,$gender?:null,$blood?:null,$address?:null,$class_id?:null,$section_id?:null,$photo,$adm_date?:null,$status,$id]);
if($s['user_id'])$pdo->prepare("UPDATE users SET name=?,email=? WHERE id=?")->execute([$name,$email?:null,$s['user_id']]);
set_flash('success','Student updated.');redirect(SITE_URL.'/admin/students/view.php?id='.$id);}
$s=array_merge($s,$_POST,['photo'=>$photo]);
}
$page_title='Edit: '.sanitize($s['name']);
$breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Students','url'=>SITE_URL.'/admin/students/'],['label'=>'Edit','active'=>true]];
include INCLUDES_PATH.'header.php';
?>
<?php if($errors):?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e):?><li><?=sanitize($e)?></li><?php endforeach;?></ul></div><?php endif;?>
<div class="card"><div class="card-header"><i class="bi bi-pencil" style="color:#f5a623;margin-right:8px;"></i>Edit Student: <?=sanitize($s['student_id']??'')?></div><div class="card-body">
<form method="POST" enctype="multipart/form-data">
<?=csrf_field()?>
<div class="row g-3">
<div class="col-12 text-center mb-2"><div class="photo-upload-wrap mx-auto"><img id="photoPreview" src="<?=$s['photo']?get_upload_url($s['photo']):ASSETS_URL.'/images/avatar.png'?>" class="rounded-circle border" width="100" height="100" style="object-fit:cover;"><label class="photo-upload-btn" for="photo"><i class="bi bi-camera-fill"></i></label></div><input type="file" id="photo" name="photo" class="d-none" accept="image/*"></div>
<div class="col-12 col-md-6"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-control" value="<?=sanitize((string)($s['name']??''))?>" required></div>
<div class="col-12 col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?=sanitize((string)($s['email']??''))?>"></div>
<div class="col-12 col-md-6"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control" value="<?=sanitize((string)($s['phone']??''))?>"></div>
<div class="col-12 col-md-6"><label class="form-label">DOB</label><input type="date" name="dob" class="form-control" value="<?=sanitize((string)($s['dob']??''))?>"></div>
<div class="col-6 col-md-3"><label class="form-label">Gender</label><select name="gender" class="form-select"><option value="">Select</option><?php foreach(['Male','Female','Other'] as $g):?><option value="<?=$g?>" <?=($s['gender']??'')===$g?'selected':''?>><?=$g?></option><?php endforeach;?></select></div>
<div class="col-6 col-md-3"><label class="form-label">Blood Group</label><select name="blood_group" class="form-select"><option value="">Select</option><?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg):?><option value="<?=$bg?>" <?=($s['blood_group']??'')===$bg?'selected':''?>><?=$bg?></option><?php endforeach;?></select></div>
<div class="col-12 col-md-6"><label class="form-label">Class</label><select name="class_id" class="form-select" id="classSelect"><option value="">Select</option><?php foreach($classes as $cl):?><option value="<?=$cl['id']?>" <?=($s['class_id']??0)==$cl['id']?'selected':''?>><?=sanitize($cl['name'])?></option><?php endforeach;?></select></div>
<div class="col-12 col-md-6"><label class="form-label">Section</label><select name="section_id" class="form-select" id="sectionSelect"><option value="">Select</option><?php foreach($sections as $sec):?><option value="<?=$sec['id']?>" <?=($s['section_id']??0)==$sec['id']?'selected':''?>><?=sanitize($sec['name'])?></option><?php endforeach;?></select></div>
<div class="col-12 col-md-6"><label class="form-label">Admission Date</label><input type="date" name="admission_date" class="form-control" value="<?=sanitize((string)($s['admission_date']??''))?>"></div>
<div class="col-12 col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" <?=($s['status']??'active')==='active'?'selected':''?>>Active</option><option value="inactive" <?=($s['status']??'')==='inactive'?'selected':''?>>Inactive</option></select></div>
<div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"><?=sanitize((string)($s['address']??''))?></textarea></div>
<div class="col-12 d-flex gap-2"><button type="submit" class="btn btn-warning px-4"><i class="bi bi-check-circle me-2"></i>Update</button><a href="<?=SITE_URL?>/admin/students/view.php?id=<?=$id?>" class="btn btn-outline-secondary">Cancel</a></div>
</div></form></div></div>
<script>
document.getElementById('photo').addEventListener('change',function(){const f=this.files[0];if(f){const r=new FileReader();r.onload=e=>{document.getElementById('photoPreview').src=e.target.result;};r.readAsDataURL(f);}});
document.getElementById('classSelect').addEventListener('change',function(){const cid=this.value;const sec=document.getElementById('sectionSelect');sec.innerHTML='<option value="">Loading...</option>';if(!cid){sec.innerHTML='<option value="">Select</option>';return;}fetch('<?=SITE_URL?>/admin/ajax/get-sections.php?class_id='+cid).then(r=>r.json()).then(data=>{sec.innerHTML='<option value="">Select</option>';data.forEach(s=>{sec.innerHTML+=`<option value="${s.id}">${s.name}</option>`;});});});
</script>
<?php include INCLUDES_PATH.'footer.php';?>
