<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
require_once INCLUDES_PATH . 'mailer.php';
auth_guard('admin');
$page_title='Add Teacher';
$breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Teachers','url'=>SITE_URL.'/admin/teachers/'],['label'=>'Add','active'=>true]];
$errors=[];$classes=get_classes();$subjects=get_subjects();
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_protect();
    $name=sanitize($_POST['name']??'');$email=sanitize_email($_POST['email']??'');
    $phone=sanitize($_POST['phone']??'');$gender=sanitize($_POST['gender']??'');
    $qual=sanitize($_POST['qualification']??'');$subj=sanitize_int($_POST['subject_id']??0);
    $cls=sanitize_int($_POST['class_id']??0);$addr=sanitize($_POST['address']??'');
    $status=sanitize($_POST['status']??'active');$pwd=$_POST['password']??'';
    if(empty($name))$errors[]='Name required.';
    if(!empty($email)&&!validate_email($email))$errors[]='Invalid email.';
    $photo=null;
    if(!empty($_FILES['photo']['name'])){$photo=upload_file($_FILES['photo'],'teachers',ALLOWED_IMAGES);if($photo===false)$errors[]='Invalid photo.';}
    if(empty($errors)){
        $pdo->beginTransaction();
        try{
            $teacher_id=generate_teacher_id();$user_id=null;
            if(!empty($email)&&!empty($pwd)){$pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,'teacher')")->execute([$name,$email,password_hash($pwd,PASSWORD_BCRYPT)]);$user_id=(int)$pdo->lastInsertId();}
            $pdo->prepare("INSERT INTO teachers (user_id,teacher_id,name,email,phone,gender,qualification,subject_id,class_id,address,photo,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")->execute([$user_id,$teacher_id,$name,$email?:null,$phone?:null,$gender?:null,$qual?:null,$subj?:null,$cls?:null,$addr?:null,$photo,$status]);
            $pdo->commit();
            if($user_id&&!empty($email))SchoolMailer::sendWelcome($email,$name,'teacher',$pwd);
            set_flash('success',"Teacher added. ID: {$teacher_id}");redirect(SITE_URL.'/admin/teachers/');
        }catch(Exception $e){$pdo->rollBack();$errors[]='Error: '.$e->getMessage();}
    }
}
include INCLUDES_PATH.'header.php';
?>
<?php if($errors):?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e):?><li><?=sanitize($e)?></li><?php endforeach;?></ul></div><?php endif;?>
<div class="card">
  <div class="card-header"><i class="bi bi-person-plus me-2" style="color:#22c55e"></i>Add Teacher</div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <?=csrf_field()?>
      <div class="row g-3">
        <div class="col-12 text-center">
          <div class="photo-upload-wrap mx-auto">
            <img id="photoPreview" src="<?=ASSETS_URL?>/images/avatar.png" class="rounded-circle border" width="100" height="100" style="object-fit:cover">
            <label class="photo-upload-btn" for="photo"><i class="bi bi-camera-fill"></i></label>
          </div>
          <input type="file" id="photo" name="photo" class="d-none" accept="image/*">
        </div>
        <div class="col-md-6 col-lg-4"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-control" value="<?=sanitize($_POST['name']??'')?>" required></div>
        <div class="col-md-6 col-lg-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?=sanitize($_POST['email']??'')?>"></div>
        <div class="col-md-6 col-lg-4"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control" value="<?=sanitize($_POST['phone']??'')?>"></div>
        <div class="col-md-6 col-lg-3"><label class="form-label">Gender</label><select name="gender" class="form-select"><option value="">Select</option><?php foreach(['Male','Female','Other'] as $g):?><option value="<?=$g?>" <?=($_POST['gender']??'')===$g?'selected':''?>><?=$g?></option><?php endforeach;?></select></div>
        <div class="col-md-6 col-lg-4"><label class="form-label">Qualification</label><input type="text" name="qualification" class="form-control" value="<?=sanitize($_POST['qualification']??'')?>" placeholder="B.Ed, M.Sc..."></div>
        <div class="col-md-6 col-lg-4"><label class="form-label">Subject</label><select name="subject_id" class="form-select"><option value="">Select</option><?php foreach($subjects as $sub):?><option value="<?=$sub['id']?>"><?=sanitize($sub['name'])?></option><?php endforeach;?></select></div>
        <div class="col-md-6 col-lg-4"><label class="form-label">Assigned Class</label><select name="class_id" class="form-select"><option value="">Select</option><?php foreach($classes as $cl):?><option value="<?=$cl['id']?>"><?=sanitize($cl['name'])?></option><?php endforeach;?></select></div>
        <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"><?=sanitize($_POST['address']??'')?></textarea></div>
        <div class="col-md-6"><label class="form-label">Portal Password</label><input type="password" name="password" class="form-control" placeholder="Min 6 characters"></div>
        <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
        <div class="col-12 d-flex gap-2">
          <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-2"></i>Add Teacher</button>
          <a href="<?=SITE_URL?>/admin/teachers/" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </div>
    </form>
  </div>
</div>
<script>document.getElementById('photo').addEventListener('change',function(){if(this.files[0]){const r=new FileReader();r.onload=e=>document.getElementById('photoPreview').src=e.target.result;r.readAsDataURL(this.files[0]);}});</script>
<?php include INCLUDES_PATH.'footer.php';?>
