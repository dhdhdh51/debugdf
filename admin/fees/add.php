<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
require_once INCLUDES_PATH . 'mailer.php';
auth_guard('admin');
if(isset($_GET['delete'])){$fid=sanitize_int($_GET['delete']);$pdo->prepare("DELETE FROM fees WHERE id=?")->execute([$fid]);set_flash('success','Fee deleted.');redirect(SITE_URL.'/admin/fees/');}
$page_title='Add Fee Invoice';
$breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Fees','url'=>SITE_URL.'/admin/fees/'],['label'=>'Add','active'=>true]];
$errors=[]; $classes=get_classes();
$students=$pdo->query("SELECT s.id,s.name,s.student_id,c.name as class_name FROM students s LEFT JOIN classes c ON s.class_id=c.id WHERE s.status='active' ORDER BY s.name")->fetchAll();
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_protect();
    $student_id=sanitize_int($_POST['student_id']??0); $fee_type=sanitize($_POST['fee_type']??'');
    $amount=sanitize_float($_POST['amount']??0); $due_date=sanitize($_POST['due_date']??''); $status=sanitize($_POST['status']??'pending');
    if(!$student_id) $errors[]='Select a student.';
    if(empty($fee_type)) $errors[]='Fee type required.';
    if($amount<=0) $errors[]='Amount must be > 0.';
    if(empty($errors)){
        $invoice_no=generate_invoice_no();
        $pdo->prepare("INSERT INTO fees (student_id,fee_type,amount,due_date,status,invoice_no,created_by) VALUES (?,?,?,?,?,?,?)")->execute([$student_id,$fee_type,$amount,$due_date?:null,$status,$invoice_no,$_SESSION['user_id']]);
        $stmt=$pdo->prepare("SELECT * FROM students WHERE id=?");$stmt->execute([$student_id]);$stu=$stmt->fetch();
        if($stu&&$stu['email']) SchoolMailer::sendFeeInvoice($stu['email'],$stu['name'],$invoice_no,$amount,$due_date);
        create_notification($stu['user_id']??null,'student','Fee Invoice Generated',"Invoice {$invoice_no} for {$fee_type} — ".currency_format($amount),'warning');
        set_flash('success',"Fee invoice {$invoice_no} created.");
        redirect(SITE_URL.'/admin/fees/');
    }
}
include INCLUDES_PATH.'header.php';
?>
<?php if($errors):?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e):?><li><?=sanitize($e)?></li><?php endforeach;?></ul></div><?php endif;?>
<div class="card" style="max-width:600px;">
  <div class="card-header"><i class="bi bi-receipt me-2" style="color:#22c55e;"></i>New Fee Invoice</div>
  <div class="card-body">
    <form method="POST">
      <?=csrf_field()?>
      <div class="row g-3">
        <div class="col-12"><label class="form-label">Student *</label>
          <select name="student_id" class="form-select" required><option value="">Select Student</option>
            <?php foreach($students as $stu):?><option value="<?=$stu['id']?>" <?=(sanitize_int($_POST['student_id']??0))==$stu['id']?'selected':''?>><?=sanitize($stu['name'])?> (<?=sanitize($stu['student_id'])?>) — <?=sanitize($stu['class_name']??'')?></option><?php endforeach;?>
          </select></div>
        <div class="col-12"><label class="form-label">Fee Type *</label><input type="text" name="fee_type" class="form-control" value="<?=sanitize($_POST['fee_type']??'')?>" placeholder="Tuition Fee, Exam Fee..." list="fee_types" required><datalist id="fee_types"><?php foreach(['Tuition Fee','Examination Fee','Lab Fee','Sports Fee','Library Fee','Transport Fee','Annual Charges','Admission Fee'] as $ft):?><option value="<?=$ft?>"><?php endforeach;?></datalist></div>
        <div class="col-12 col-md-6"><label class="form-label">Amount *</label><div class="input-group"><span class="input-group-text"><?=get_setting('currency_symbol','₹')?></span><input type="number" name="amount" class="form-control" value="<?=sanitize_float($_POST['amount']??0)?:''?>" min="1" step="0.01" required></div></div>
        <div class="col-12 col-md-6"><label class="form-label">Due Date</label><input type="date" name="due_date" class="form-control" value="<?=sanitize($_POST['due_date']??'')?>" min="<?=date('Y-m-d')?>"></div>
        <div class="col-12 col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="pending" <?=($_POST['status']??'pending')==='pending'?'selected':''?>>Pending</option><option value="paid" <?=($_POST['status']??'')==='paid'?'selected':''?>>Paid</option></select></div>
        <div class="col-12 d-flex gap-2"><button type="submit" class="btn btn-success px-4"><i class="bi bi-receipt me-2"></i>Create Invoice</button><a href="<?=SITE_URL?>/admin/fees/" class="btn btn-outline-secondary">Cancel</a></div>
      </div>
    </form>
  </div>
</div>
<?php include INCLUDES_PATH.'footer.php';?>
