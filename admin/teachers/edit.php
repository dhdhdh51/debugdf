<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('admin');
$id=sanitize_int($_GET['id']??0);
$s=$pdo->prepare("SELECT * FROM teachers WHERE id=? LIMIT 1");$s->execute([$id]);$t=$s->fetch();
if(!$t){set_flash('error','Teacher not found.');redirect(SITE_URL.'/admin/teachers/');}
$classes=get_classes();$subjects=get_subjects();$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_protect();
    $name=sanitize($_POST['name']??'');$email=sanitize_email($_POST['email']??'');
    $phone=sanitize($_POST['phone']??'');$gender=sanitize($_POST['gender']??'');
    $qual=sanitize($_POST['qualification']??'');$subj=sanitize_int($_POST['subject_id']??0);
    $cls=sanitize_int($_POST['class_id']??0);$addr=sanitize($_POST['address']??'');
    $status=sanitize($_POST['status']??'active');
    if(empty($name))$errors[]='Name required.';
    $photo=$t['photo'];
    if(!empty($_FILES['photo']['name'])){$np=upload_file($_FILES['photo'],'teachers',ALLOWED_IMAGES);if($np===false)$errors[]='Invalid photo.';else{if($photo)delete_upload($photo);$photo=$np;}}
    if(empty($errors)){
        $pdo->prepare("UPDATE teachers SET name=?,email=?,phone=?,gender=?,qualification=?,subject_id=?,class_id=?,address=?,photo=?,status=? WHERE id=?")->execute([$name,$email?:null,$phone?:null,$gender?:null,$qual?:null,$subj?:null,$cls?:null,$addr?:null,$photo,$status,$id]);
        if($t['user_id'])$pdo->prepare("UPDATE users SET name=?,email=? WHERE id=?")->execute([$name,$email?:null,$t['user_id']]);
        set_flash('success','Teacher updated.');redirect(SITE_URL.'/admin/teachers/');
    }
    $t=array_merge($t,$_POST,['photo'=>$photo]);
}
$page_title='Edit Teacher';
$breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Teachers','url'=>SITE_URL.'/admin/teachers/'],['label'=>'Edit','active'=>true]];
include INCLUDES_PATH.'header.php';
?>
<?php if($errors):?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e):?><li><?=sanitize($e)?></li><?php endforeach;?></ul></div><?php endif;?>
<div class="card">
  <div class="card-header"><i class="bi bi-pencil me-2" style="color:#f5a623"></i>Edit Teacher: <?=sanitize($t['teacher_id']??'')?></div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <?=csrf_field()?>
      <div class="row g-3">
        <div class="col-12 text-center">
          <div class="photo-upload-wrap mx-auto">
            <img id="photoPreview" src="<?=$t['photo']?get_upload_url($t['photo']):ASSETS_URL.'/images/avatar.png'?>" class="rounded-circle border" width="100" height="100" style="object-fit:cover">
            <label class="photo-upload-btn" for="photo"><i class="bi bi-camera-fill"></i></label>
          </div>
          <input type="file" id="photo" name="photo" class="d-none" accept="image/*">
        </div>
        <div class="col-md-6 col-lg-4"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" value="<?=sanitize($t['name'])?>" required></div>
        <div class="col-md-6 col-lg-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?=sanitize($t['email']??'')?>"></div>
        <div class="col-md-6 col-lg-4"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control" value="<?=sanitize($t['phone']??'')?>"></div>
        <div class="col-md-4"><label class="form-label">Gender</label><select name="gender" class="form-select"><option value="">Select</option><?php foreach(['Male','Female','Other'] as $g):?><option value="<?=$g?>" <?=($t['gender']??'')===$g?'selected':''?>><?=$g?></option><?php endforeach;?></select></div>
        <div class="col-md-4"><label class="form-label">Qualification</label><input type="text" name="qualification" class="form-control" value="<?=sanitize($t['qualification']??'')?>"></div>
        <div class="col-md-4"><label class="form-label">Subject</label><select name="subject_id" class="form-select"><option value="">Select</option><?php foreach($subjects as $sub):?><option value="<?=$sub['id']?>" <?=($t['subject_id']??0)==$sub['id']?'selected':''?>><?=sanitize($sub['name'])?></option><?php endforeach;?></select></div>
        <div class="col-md-4"><label class="form-label">Class</label><select name="class_id" class="form-select"><option value="">Select</option><?php foreach($classes as $cl):?><option value="<?=$cl['id']?>" <?=($t['class_id']??0)==$cl['id']?'selected':''?>><?=sanitize($cl['name'])?></option><?php endforeach;?></select></div>
        <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" <?=($t['status']??'active')==='active'?'selected':''?>>Active</option><option value="inactive" <?=($t['status']??'')==='inactive'?'selected':''?>>Inactive</option></select></div>
        <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"><?=sanitize($t['address']??'')?></textarea></div>
        <div class="col-12 d-flex gap-2">
          <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-2"></i>Update</button>
          <a href="<?=SITE_URL?>/admin/teachers/" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </div>
    </form>
  </div>
</div>
<script>document.getElementById('photo').addEventListener('change',function(){if(this.files[0]){const r=new FileReader();r.onload=e=>document.getElementById('photoPreview').src=e.target.result;r.readAsDataURL(this.files[0]);}});</script>
<?php include INCLUDES_PATH.'footer.php';?>
