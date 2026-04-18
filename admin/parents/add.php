<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
require_once INCLUDES_PATH . 'mailer.php';
auth_guard('admin');
if (isset($_GET['delete'])) {
    $pid = sanitize_int($_GET['delete']);
    $stmt=$pdo->prepare("SELECT * FROM parents WHERE id=?");$stmt->execute([$pid]);$p=$stmt->fetch();
    if($p){if($p['user_id'])$pdo->prepare("DELETE FROM users WHERE id=?")->execute([$p['user_id']]);$pdo->prepare("DELETE FROM parents WHERE id=?")->execute([$pid]);set_flash('success','Parent deleted.');}
    redirect(SITE_URL.'/admin/parents/');
}
$edit_id=sanitize_int($_GET['edit']??0);$parent=null;
if($edit_id){$s=$pdo->prepare("SELECT * FROM parents WHERE id=?");$s->execute([$edit_id]);$parent=$s->fetch();}
$page_title=$parent?'Edit Parent':'Add Parent';
$breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Parents','url'=>SITE_URL.'/admin/parents/'],['label'=>$page_title,'active'=>true]];
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_protect();
    $name=sanitize($_POST['name']??'');$email=sanitize_email($_POST['email']??'');
    $phone=sanitize($_POST['phone']??'');$relation=sanitize($_POST['relation']??'Father');
    $occ=sanitize($_POST['occupation']??'');$address=sanitize($_POST['address']??'');
    $status=sanitize($_POST['status']??'active');$pwd=$_POST['password']??'';
    if(empty($name))$errors[]='Name required.';
    if(!empty($email)&&!validate_email($email))$errors[]='Invalid email.';
    if(empty($errors)){
        $pdo->beginTransaction();
        try {
            $user_id=$parent['user_id']??null;
            if(!empty($email)&&!empty($pwd)&&!$parent){
                $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,'parent')")->execute([$name,$email,password_hash($pwd,PASSWORD_BCRYPT)]);
                $user_id=(int)$pdo->lastInsertId();
            }
            if($parent){
                $pdo->prepare("UPDATE parents SET name=?,email=?,phone=?,relation=?,occupation=?,address=?,status=? WHERE id=?")->execute([$name,$email?:null,$phone?:null,$relation,$occ?:null,$address?:null,$status,$edit_id]);
                if($user_id)$pdo->prepare("UPDATE users SET name=?,email=? WHERE id=?")->execute([$name,$email?:null,$user_id]);
                set_flash('success','Parent updated.');
            }else{
                $pdo->prepare("INSERT INTO parents (user_id,name,email,phone,relation,occupation,address,status) VALUES (?,?,?,?,?,?,?,?)")->execute([$user_id,$name,$email?:null,$phone?:null,$relation,$occ?:null,$address?:null,$status]);
                if($user_id&&!empty($email))SchoolMailer::sendWelcome($email,$name,'parent',$pwd);
                set_flash('success','Parent added.');
            }
            $pdo->commit();redirect(SITE_URL.'/admin/parents/');
        }catch(Exception $e){$pdo->rollBack();$errors[]='Error: '.$e->getMessage();}
    }
}
include INCLUDES_PATH.'header.php';
?>
<?php if($errors):?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e):?><li><?=sanitize($e)?></li><?php endforeach;?></ul></div><?php endif;?>
<div class="card" style="max-width:640px">
  <div class="card-header"><i class="bi bi-people me-2" style="color:#7b5ea7"></i><?=$page_title?></div>
  <div class="card-body">
    <form method="POST">
      <?=csrf_field()?>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-control" value="<?=sanitize($parent['name']??($_POST['name']??''))?>" required></div>
        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?=sanitize($parent['email']??($_POST['email']??''))?>"></div>
        <div class="col-md-6"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control" value="<?=sanitize($parent['phone']??($_POST['phone']??''))?>"></div>
        <div class="col-md-6"><label class="form-label">Relation</label><select name="relation" class="form-select"><?php foreach(['Father','Mother','Guardian'] as $r):?><option value="<?=$r?>" <?=($parent['relation']??'Father')===$r?'selected':''?>><?=$r?></option><?php endforeach;?></select></div>
        <div class="col-md-6"><label class="form-label">Occupation</label><input type="text" name="occupation" class="form-control" value="<?=sanitize($parent['occupation']??($_POST['occupation']??''))?>"></div>
        <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" <?=($parent['status']??'active')==='active'?'selected':''?>>Active</option><option value="inactive" <?=($parent['status']??'')==='inactive'?'selected':''?>>Inactive</option></select></div>
        <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"><?=sanitize($parent['address']??($_POST['address']??''))?></textarea></div>
        <?php if(!$parent):?><div class="col-12"><label class="form-label">Portal Password</label><input type="password" name="password" class="form-control" placeholder="Leave blank = no portal access"></div><?php endif;?>
        <div class="col-12 d-flex gap-2">
          <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-2"></i><?=$parent?'Update':'Add'?> Parent</button>
          <a href="<?=SITE_URL?>/admin/parents/" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </div>
    </form>
  </div>
</div>
<?php include INCLUDES_PATH.'footer.php';?>
