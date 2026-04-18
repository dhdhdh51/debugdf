<?php
require_once dirname(__DIR__,2).'/config/config.php';
require_once INCLUDES_PATH.'auth_check.php';
auth_guard('admin');
$page_title='Settings';$breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Settings','active'=>true]];
$tab=sanitize($_GET['tab']??'general');
if($_SERVER['REQUEST_METHOD']==='POST'){
csrf_protect();
$upsert=$pdo->prepare("INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
$allowed=['site_name','site_tagline','site_url','footer_text','contact_email','contact_phone','contact_address','academic_year','currency','currency_symbol','smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from','smtp_from_name','smtp_encryption','payu_merchant_key','payu_merchant_salt','payu_mode','payu_surl','payu_furl','meta_title','meta_description','meta_keywords','pass_percentage','contact_map_embed','social_facebook','social_twitter','social_instagram','social_youtube','about_mission','about_vision'];
foreach($allowed as $key){if(isset($_POST[$key]))$upsert->execute([$key,sanitize($_POST[$key])]);}
if(!empty($_FILES['site_logo']['name'])){$logo=upload_file($_FILES['site_logo'],'logos',ALLOWED_IMAGES);if($logo)$upsert->execute(['site_logo',$logo]);}
if(!empty($_FILES['site_favicon']['name'])){$fav=upload_file($_FILES['site_favicon'],'logos',['ico','png','gif','jpg']);if($fav)$upsert->execute(['site_favicon',$fav]);}
set_flash('success','Settings saved!');redirect(SITE_URL.'/admin/settings/?tab='.$tab);}
$settings=[];$rows=$pdo->query("SELECT setting_key,setting_value FROM settings")->fetchAll();foreach($rows as $r)$settings[$r['setting_key']]=$r['setting_value'];
function s(array $s,string $k,string $d=''):string{return htmlspecialchars($s[$k]??$d,ENT_QUOTES,'UTF-8');}
include INCLUDES_PATH.'header.php';
?>
<ul class="nav nav-tabs mb-3 flex-wrap">
<?php foreach(['general'=>['General','gear'],'smtp'=>['SMTP','envelope'],'payment'=>['Payment','credit-card'],'seo'=>['SEO','search']] as $t=>[$label,$icon]):?>
<li class="nav-item"><a class="nav-link <?=$tab===$t?'active':''?>" href="?tab=<?=$t?>"><i class="bi bi-<?=$icon?> me-1"></i><?=$label?></a></li>
<?php endforeach;?>
</ul>
<form method="POST" enctype="multipart/form-data"><?=csrf_field()?>
<?php if($tab==='general'):?>
<div class="row g-3">
<div class="col-12 col-lg-6"><div class="card"><div class="card-header">Website Identity</div><div class="card-body">
<div class="mb-3"><label class="form-label">Site Name</label><input type="text" name="site_name" class="form-control" value="<?=s($settings,'site_name')?>"></div>
<div class="mb-3"><label class="form-label">Tagline</label><input type="text" name="site_tagline" class="form-control" value="<?=s($settings,'site_tagline')?>"></div>
<div class="mb-3"><label class="form-label">Site URL</label><input type="url" name="site_url" class="form-control" value="<?=s($settings,'site_url')?>"></div>
<div class="mb-3"><label class="form-label">Footer Text</label><input type="text" name="footer_text" class="form-control" value="<?=s($settings,'footer_text')?>"></div>
<div class="mb-3"><label class="form-label">Academic Year</label><input type="text" name="academic_year" class="form-control" value="<?=s($settings,'academic_year')?>"></div>
<div class="row g-2"><div class="col-6"><label class="form-label">Currency</label><input type="text" name="currency" class="form-control" value="<?=s($settings,'currency','INR')?>"></div><div class="col-6"><label class="form-label">Symbol</label><input type="text" name="currency_symbol" class="form-control" value="<?=s($settings,'currency_symbol','₹')?>"></div></div>
<div class="mt-3"><label class="form-label">Pass %</label><input type="number" name="pass_percentage" class="form-control" value="<?=s($settings,'pass_percentage','33')?>" min="1" max="100"></div>
</div></div></div>
<div class="col-12 col-lg-6">
<div class="card mb-3"><div class="card-header">Contact</div><div class="card-body">
<div class="mb-3"><label class="form-label">Email</label><input type="email" name="contact_email" class="form-control" value="<?=s($settings,'contact_email')?>"></div>
<div class="mb-3"><label class="form-label">Phone</label><input type="text" name="contact_phone" class="form-control" value="<?=s($settings,'contact_phone')?>"></div>
<div class="mb-3"><label class="form-label">Address</label><textarea name="contact_address" class="form-control" rows="2"><?=s($settings,'contact_address')?></textarea></div>
<div class="mb-3"><label class="form-label">Google Maps Embed</label><textarea name="contact_map_embed" class="form-control" rows="3"><?=s($settings,'contact_map_embed')?></textarea></div>
</div></div>
<div class="card mb-3"><div class="card-header">Social Links</div><div class="card-body">
<?php foreach(['social_facebook'=>'Facebook URL','social_instagram'=>'Instagram URL','social_twitter'=>'Twitter/X URL','social_youtube'=>'YouTube URL'] as $key=>$label):?>
<div class="mb-2"><label class="form-label" style="font-size:0.78rem;"><?=$label?></label><input type="url" name="<?=$key?>" class="form-control form-control-sm" value="<?=s($settings,$key)?>" placeholder="https://..."></div>
<?php endforeach;?>
</div></div>
<div class="card"><div class="card-header">Logo & Favicon</div><div class="card-body">
<div class="mb-3"><label class="form-label">Site Logo</label><?php if(!empty($settings['site_logo'])):?><div class="mb-2"><img src="<?=get_upload_url($settings['site_logo'])?>" height="44" class="rounded"></div><?php endif;?><input type="file" name="site_logo" class="form-control" accept="image/*"></div>
<div class="mb-3"><label class="form-label">Favicon</label><?php if(!empty($settings['site_favicon'])):?><div class="mb-2"><img src="<?=get_upload_url($settings['site_favicon'])?>" height="28" class="rounded"></div><?php endif;?><input type="file" name="site_favicon" class="form-control" accept="image/x-icon,image/png,image/gif"></div>
</div></div>
</div></div>
<?php elseif($tab==='smtp'):?>
<div class="card" style="max-width:600px;"><div class="card-header"><i class="bi bi-envelope me-2"></i>SMTP Configuration</div><div class="card-body">
<div class="alert alert-info" style="font-size:0.82rem;"><i class="bi bi-info-circle me-1"></i>Gmail: host <code>smtp.gmail.com</code>, port <code>587</code> (TLS). Use App Password.</div>
<div class="row g-3">
<div class="col-8"><label class="form-label">SMTP Host</label><input type="text" name="smtp_host" class="form-control" value="<?=s($settings,'smtp_host','smtp.gmail.com')?>"></div>
<div class="col-4"><label class="form-label">Port</label><input type="number" name="smtp_port" class="form-control" value="<?=s($settings,'smtp_port','587')?>"></div>
<div class="col-12"><label class="form-label">Username</label><input type="email" name="smtp_user" class="form-control" value="<?=s($settings,'smtp_user')?>"></div>
<div class="col-12"><label class="form-label">Password / App Password</label><input type="password" name="smtp_pass" class="form-control" value="<?=s($settings,'smtp_pass')?>"></div>
<div class="col-12"><label class="form-label">From Email</label><input type="email" name="smtp_from" class="form-control" value="<?=s($settings,'smtp_from')?>"></div>
<div class="col-12"><label class="form-label">From Name</label><input type="text" name="smtp_from_name" class="form-control" value="<?=s($settings,'smtp_from_name')?>"></div>
<div class="col-12"><label class="form-label">Encryption</label><select name="smtp_encryption" class="form-select"><option value="tls" <?=s($settings,'smtp_encryption','tls')==='tls'?'selected':''?>>TLS</option><option value="ssl" <?=s($settings,'smtp_encryption','')==='ssl'?'selected':''?>>SSL</option><option value="" <?=s($settings,'smtp_encryption','')==='none'?'selected':''?>>None</option></select></div>
</div></div></div>
<?php elseif($tab==='payment'):?>
<div class="card" style="max-width:600px;"><div class="card-header"><i class="bi bi-credit-card me-2"></i>PayU Payment Gateway</div><div class="card-body">
<div class="row g-3">
<div class="col-12"><label class="form-label">Merchant Key</label><input type="text" name="payu_merchant_key" class="form-control" value="<?=s($settings,'payu_merchant_key')?>"></div>
<div class="col-12"><label class="form-label">Merchant Salt</label><input type="text" name="payu_merchant_salt" class="form-control" value="<?=s($settings,'payu_merchant_salt')?>"></div>
<div class="col-12"><label class="form-label">Mode</label><select name="payu_mode" class="form-select"><option value="test" <?=s($settings,'payu_mode','test')==='test'?'selected':''?>>Test (Sandbox)</option><option value="live" <?=s($settings,'payu_mode','test')==='live'?'selected':''?>>Live</option></select></div>
<div class="col-12"><label class="form-label">Success URL</label><input type="url" name="payu_surl" class="form-control" value="<?=s($settings,'payu_surl')?>" placeholder="<?=SITE_URL?>/payment/success.php"></div>
<div class="col-12"><label class="form-label">Failure URL</label><input type="url" name="payu_furl" class="form-control" value="<?=s($settings,'payu_furl')?>" placeholder="<?=SITE_URL?>/payment/failure.php"></div>
</div></div></div>
<?php elseif($tab==='seo'):?>
<div class="card" style="max-width:600px;"><div class="card-header"><i class="bi bi-search me-2"></i>SEO Settings</div><div class="card-body">
<div class="mb-3"><label class="form-label">Meta Title</label><input type="text" name="meta_title" class="form-control" value="<?=s($settings,'meta_title')?>" maxlength="70"></div>
<div class="mb-3"><label class="form-label">Meta Description</label><textarea name="meta_description" class="form-control" rows="3" maxlength="160"><?=s($settings,'meta_description')?></textarea></div>
<div class="mb-3"><label class="form-label">Meta Keywords</label><input type="text" name="meta_keywords" class="form-control" value="<?=s($settings,'meta_keywords')?>"></div>
</div></div>
<?php endif;?>
<div class="mt-3"><button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-2"></i>Save Settings</button></div>
</form>
<?php include INCLUDES_PATH.'footer.php';?>
