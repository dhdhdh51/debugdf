<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('admin');
$edit_id=sanitize_int($_GET['id']??0); $exam=null;
if($edit_id){$stmt=$pdo->prepare("SELECT * FROM exams WHERE id=?");$stmt->execute([$edit_id]);$exam=$stmt->fetch();}
$page_title=$exam?'Edit Exam':'Create Exam';
$breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Exams','url'=>SITE_URL.'/admin/exams/'],['label'=>$page_title,'active'=>true]];
$errors=[]; $classes=get_classes();
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_protect();
    $name=sanitize($_POST['name']??''); $type=sanitize($_POST['type']??'Unit Test');
    $cls=sanitize_int($_POST['class_id']??0); $start=sanitize($_POST['start_date']??'');
    $end=sanitize($_POST['end_date']??''); $status=sanitize($_POST['status']??'upcoming');
    if(empty($name)) $errors[]='Exam name required.';
    if(!in_array($type,['Unit Test','Mid Term','Final','Other'])) $type='Unit Test';
    if(empty($errors)){
        if($edit_id){
            $pdo->prepare("UPDATE exams SET name=?,type=?,class_id=?,start_date=?,end_date=?,status=? WHERE id=?")->execute([$name,$type,$cls?:null,$start?:null,$end?:null,$status,$edit_id]);
            set_flash('success','Exam updated.');
        } else {
            $pdo->prepare("INSERT INTO exams (name,type,class_id,start_date,end_date,status) VALUES (?,?,?,?,?,?)")->execute([$name,$type,$cls?:null,$start?:null,$end?:null,$status]);
            set_flash('success',"Exam '{$name}' created.");
        }
        redirect(SITE_URL.'/admin/exams/');
    }
}
include INCLUDES_PATH.'header.php';
?>
<?php if($errors):?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e):?><li><?=sanitize($e)?></li><?php endforeach;?></ul></div><?php endif;?>
<div class="card">
  <div class="card-header"><i class="bi bi-clipboard2-data me-2" style="color:#4f8ef7;"></i><?=$page_title?></div>
  <div class="card-body">
    <form method="POST">
      <?=csrf_field()?>
      <div class="row g-3">
        <div class="col-12 col-md-6"><label class="form-label">Exam Name *</label><input type="text" name="name" class="form-control" required value="<?=sanitize($exam['name']??($_POST['name']??''))?>" placeholder="e.g., Final Examination 2025-26"></div>
        <div class="col-12 col-md-3"><label class="form-label">Type</label><select name="type" class="form-select"><?php $cur=$exam['type']??($_POST['type']??'Unit Test');foreach(['Unit Test','Mid Term','Final','Other'] as $t):?><option value="<?=$t?>" <?=$cur===$t?'selected':''?>><?=$t?></option><?php endforeach;?></select></div>
        <div class="col-12 col-md-3"><label class="form-label">Class</label><select name="class_id" class="form-select"><option value="">All Classes</option><?php $cc=$exam['class_id']??(sanitize_int($_POST['class_id']??0));foreach($classes as $cl):?><option value="<?=$cl['id']?>" <?=$cc==$cl['id']?'selected':''?>><?=sanitize($cl['name'])?></option><?php endforeach;?></select></div>
        <div class="col-6 col-md-3"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control" value="<?=sanitize($exam['start_date']??($_POST['start_date']??''))?>"></div>
        <div class="col-6 col-md-3"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control" value="<?=sanitize($exam['end_date']??($_POST['end_date']??''))?>"></div>
        <div class="col-12 col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><?php $cs=$exam['status']??'upcoming';foreach(['upcoming','ongoing','completed'] as $st):?><option value="<?=$st?>" <?=$cs===$st?'selected':''?>><?=ucfirst($st)?></option><?php endforeach;?></select></div>
        <div class="col-12 d-flex gap-2"><button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-2"></i><?=$exam?'Update':'Create'?> Exam</button><a href="<?=SITE_URL?>/admin/exams/" class="btn btn-outline-secondary">Cancel</a></div>
      </div>
    </form>
  </div>
</div>
<?php include INCLUDES_PATH.'footer.php';?>
