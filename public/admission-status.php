<?php
require_once dirname(__DIR__) . '/config/config.php';
$site_name=get_setting('site_name','School ERP');
$app_id=sanitize($_GET['id']??($_POST['app_id']??'')); $admission=null; $searched=false;
if($_SERVER['REQUEST_METHOD']==='POST'||$app_id){
    if($_SERVER['REQUEST_METHOD']==='POST'){csrf_protect();$app_id=sanitize($_POST['app_id']??'');}
    if(!empty($app_id)){$searched=true;$stmt=$pdo->prepare("SELECT a.*,c.name as class_name FROM admissions a LEFT JOIN classes c ON a.class_applying=c.id WHERE a.application_id=? LIMIT 1");$stmt->execute([$app_id]);$admission=$stmt->fetch();}
}
include INCLUDES_PATH.'public_header.php';
?>
<div style="padding-top:80px;">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
      <div class="text-center mb-4">
        <div style="width:64px;height:64px;border-radius:18px;background:linear-gradient(135deg,rgba(245,166,35,0.15),rgba(245,166,35,0.05));border:1px solid rgba(245,166,35,0.3);display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto 16px;">🔍</div>
        <h2 style="font-family:'Playfair Display',serif;color:#fff;">Track Application</h2>
        <p style="color:rgba(255,255,255,0.5);font-size:0.9rem;"><?=htmlspecialchars($site_name,ENT_QUOTES)?></p>
      </div>
      <div class="card mb-4"><div class="card-body">
        <form method="POST"><?=csrf_field()?>
          <label class="form-label">Application ID</label>
          <div class="input-group">
            <input type="text" name="app_id" class="form-control" style="font-family:monospace;font-size:1.1rem;" value="<?=sanitize($app_id)?>" placeholder="e.g., APP2026-0001" required autofocus>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Check</button>
          </div>
        </form>
      </div></div>
      <?php if($searched&&!$admission):?>
      <div class="card"><div class="card-body text-center py-5" style="color:var(--text-muted);"><i class="bi bi-search" style="font-size:2.5rem;display:block;margin-bottom:12px;"></i><h5>Not Found</h5><p style="font-size:0.85rem;">No application for ID: <code><?=sanitize($app_id)?></code></p><a href="<?=SITE_URL?>/public/admission.php" class="btn btn-primary mt-2">Apply for Admission</a></div></div>
      <?php elseif($admission):?>
      <div class="card"><div class="card-header d-flex justify-content-between align-items-center">
        <span style="font-weight:700;">Application Details</span>
        <?php $sc=match($admission['status']){'approved'=>['success','check-circle-fill','Approved'],'rejected'=>['danger','x-circle-fill','Rejected'],default=>['warning','clock-fill','Pending']};?>
        <span class="badge bg-<?=$sc[0]?> px-3 py-2"><i class="bi bi-<?=$sc[1]?> me-1"></i><?=$sc[2]?></span>
      </div>
      <div class="card-body">
        <table class="table table-sm">
          <tr><th>App ID</th><td><code><?=sanitize($admission['application_id'])?></code></td></tr>
          <tr><th>Name</th><td><?=sanitize($admission['name'])?></td></tr>
          <tr><th>Class</th><td><?=sanitize($admission['class_name']??'N/A')?></td></tr>
          <tr><th>Applied</th><td><?=format_date($admission['created_at'],'d M Y, h:i A')?></td></tr>
          <tr><th>Contact</th><td><?=sanitize($admission['phone']??'—')?></td></tr>
          <?php if($admission['remarks']):?><tr><th>Remarks</th><td class="<?=$admission['status']==='rejected'?'':'text-muted'?>"><?=sanitize($admission['remarks'])?></td></tr><?php endif;?>
        </table>
        <?php if($admission['status']==='approved'):?><div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i><strong>Approved!</strong> Contact the school office to complete enrollment.</div>
        <?php elseif($admission['status']==='rejected'):?><div class="alert alert-danger"><i class="bi bi-x-circle-fill me-2"></i>Application not approved at this time.</div>
        <?php else:?><div class="alert alert-warning"><i class="bi bi-clock-fill me-2"></i>Application under review. You will be notified via email.</div><?php endif;?>
      </div></div>
      <?php endif;?>
    </div>
  </div>
</div></div>
<?php include INCLUDES_PATH.'public_footer.php';?>
