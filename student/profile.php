<?php
require_once dirname(__DIR__).'/config/config.php';
require_once INCLUDES_PATH.'auth_check.php';
auth_guard('student');
$student=get_student_by_user_id((int)$_SESSION['user_id']);
if(!$student)redirect(SITE_URL.'/auth/login.php');
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
csrf_protect();
$action=sanitize($_POST['action']??'');
if($action==='update_profile'){
$phone=sanitize($_POST['phone']??'');$address=sanitize($_POST['address']??'');
$photo=$student['photo'];
if(!empty($_FILES['photo']['name'])){$np=upload_file($_FILES['photo'],'students',ALLOWED_IMAGES);if($np===false){$errors[]='Invalid photo.';}else{if($photo)delete_upload($photo);$photo=$np;}}
if(empty($errors)){$pdo->prepare("UPDATE students SET phone=?,address=?,photo=? WHERE id=?")->execute([$phone?:null,$address?:null,$photo,$student['id']]);set_flash('success','Profile updated.');redirect(SITE_URL.'/student/profile.php');}
}
elseif($action==='change_password'){
$cur=$_POST['current_password']??'';$npwd=$_POST['new_password']??'';$cpwd=$_POST['confirm_password']??'';
$u=$pdo->prepare("SELECT password FROM users WHERE id=?");$u->execute([(int)$_SESSION['user_id']]);$u=$u->fetch();
if(!$u||!password_verify($cur,$u['password'])){$errors[]='Current password incorrect.';}
elseif(strlen($npwd)<8){$errors[]='Min 8 characters.';}
elseif($npwd!==$cpwd){$errors[]='Passwords do not match.';}
else{$pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($npwd,PASSWORD_BCRYPT),(int)$_SESSION['user_id']]);set_flash('success','Password changed.');redirect(SITE_URL.'/student/profile.php');}
}
}
$student=get_student_by_user_id((int)$_SESSION['user_id']);
$page_title='My Profile';
$breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/student/'],['label'=>'Profile','active'=>true]];
include INCLUDES_PATH.'header.php';
?>
<?php if($errors):?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e):?><li><?=sanitize($e)?></li><?php endforeach;?></ul></div><?php endif;?>
<div class="row g-3">
<div class="col-12 col-lg-8">
<div class="card mb-3"><div class="card-header"><i class="bi bi-person-circle" style="color:#4f8ef7;margin-right:8px;"></i>My Information</div><div class="card-body">
<form method="POST" enctype="multipart/form-data">
<?=csrf_field()?><input type="hidden" name="action" value="update_profile">
<div class="text-center mb-4"><div class="photo-upload-wrap mx-auto"><img id="photoPreview" src="<?=$student['photo']?get_upload_url($student['photo']):ASSETS_URL.'/images/avatar.png'?>" class="rounded-circle border" width="100" height="100" style="object-fit:cover;"><label class="photo-upload-btn" for="photo"><i class="bi bi-camera-fill"></i></label></div><input type="file" id="photo" name="photo" class="d-none" accept="image/*"></div>
<div class="row g-3">
<?php foreach([['name','text','Full Name',false],['student_id','text','Student ID',true],['email','email','Email',true],['class_name','text','Class',true]] as [$fn,$ft,$fl,$ro]):?>
<div class="col-12 col-md-6"><label class="form-label"><?=$fl?></label><input type="<?=$ft?>" class="form-control" value="<?=sanitize((string)($student[$fn]??''))?>" <?=$ro?'readonly style="opacity:0.5;"':'?> disabled style="opacity:0.5;"'?>></div>
<?php endforeach;?>
<div class="col-12 col-md-6"><label class="form-label">Phone (editable)</label><input type="tel" name="phone" class="form-control" value="<?=sanitize($student['phone']??'')?>"></div>
<div class="col-12"><label class="form-label">Address (editable)</label><textarea name="address" class="form-control" rows="2"><?=sanitize($student['address']??'')?></textarea></div>
<div class="col-12"><button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-2"></i>Save Changes</button></div>
</div></form></div></div>
</div>
<div class="col-12 col-lg-4">
<div class="card"><div class="card-header"><i class="bi bi-shield-lock" style="color:#f5a623;margin-right:8px;"></i>Change Password</div><div class="card-body">
<form method="POST"><?=csrf_field()?><input type="hidden" name="action" value="change_password">
<div class="mb-3"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
<div class="mb-3"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" required minlength="8"><div class="form-text">Min 8 characters.</div></div>
<div class="mb-3"><label class="form-label">Confirm</label><input type="password" name="confirm_password" class="form-control" required></div>
<button type="submit" class="btn btn-warning w-100"><i class="bi bi-lock me-2"></i>Change Password</button>
</form></div></div>
</div></div>
<script>document.getElementById('photo').addEventListener('change',function(){if(this.files[0]){const r=new FileReader();r.onload=e=>{document.getElementById('photoPreview').src=e.target.result;};r.readAsDataURL(this.files[0]);}});</script>
<?php include INCLUDES_PATH.'footer.php';?>
