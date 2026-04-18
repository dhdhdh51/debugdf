<?php
require_once dirname(__DIR__).'/config/config.php';
$page_title='Contact Us'; $active_nav='contact'; $map_embed=get_setting('contact_map_embed','');
include INCLUDES_PATH.'public_header.php';
$sent=false; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['contact_submit'])){
    $name=htmlspecialchars($_POST['c_name']??'',ENT_QUOTES); $c_email=htmlspecialchars($_POST['c_email']??'',ENT_QUOTES);
    $subject=htmlspecialchars($_POST['c_subject']??'General Enquiry',ENT_QUOTES); $msg=htmlspecialchars($_POST['c_message']??'',ENT_QUOTES);
    if($name&&$c_email&&$msg){
        $pdo->prepare("INSERT INTO notifications (user_id,role,title,message,type) VALUES (NULL,'admin',?,?,0)")->execute(["Website Enquiry: $subject","From: $name <$c_email>\n\n$msg"]);
        $sent=true;
    } else $err='Please fill all required fields.';
}
?>
<div style="padding-top:80px;">
<div class="page-hero text-center text-white"><div class="container"><h1 style="font-family:'Playfair Display',serif;font-size:clamp(2rem,4vw,3rem);font-weight:800;margin-bottom:12px;">Contact Us</h1><p style="color:rgba(255,255,255,0.6);">We'd love to hear from you.</p></div></div>
<section class="py-5" style="background:#0f1729;"><div class="container">
  <div class="row g-4 justify-content-center">
    <?php foreach([['bi-geo-alt-fill','#f5a623','Address',get_setting('contact_address','School Address')],['bi-telephone-fill','#22c55e','Phone',get_setting('contact_phone','+91 9000000000')],['bi-envelope-fill','#4f8ef7','Email',get_setting('contact_email','info@school.com')]] as [$icon,$col,$lbl,$val]):?>
    <div class="col-12 col-md-4"><div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:16px;padding:28px;text-align:center;">
      <div style="width:52px;height:52px;border-radius:14px;background:<?=$col?>1a;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;"><i class="bi <?=$icon?>" style="color:<?=$col?>;font-size:1.3rem;"></i></div>
      <h6 style="color:#fff;font-weight:700;margin-bottom:6px;"><?=$lbl?></h6>
      <p style="color:rgba(255,255,255,0.45);font-size:0.875rem;margin:0;"><?=htmlspecialchars($val,ENT_QUOTES)?></p>
    </div></div>
    <?php endforeach;?>
  </div>
</div></section>
<section class="py-5" style="background:#131d35;"><div class="container">
  <div class="row g-5">
    <div class="col-lg-6">
      <h3 style="font-family:'Playfair Display',serif;color:#fff;margin-bottom:20px;">Send a Message</h3>
      <?php if($sent):?><div class="alert alert-success">✅ Thank you! We'll respond within 24 hours.</div><?php endif;?>
      <?php if($err):?><div class="alert alert-danger"><?=$err?></div><?php endif;?>
      <form method="POST"><div class="row g-3">
        <div class="col-md-6"><label class="form-label">Your Name *</label><input type="text" name="c_name" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="c_email" class="form-control" required></div>
        <div class="col-12"><label class="form-label">Subject</label><select name="c_subject" class="form-select"><option>General Enquiry</option><option>Admission Enquiry</option><option>Fee Related</option><option>Other</option></select></div>
        <div class="col-12"><label class="form-label">Message *</label><textarea name="c_message" class="form-control" rows="5" required></textarea></div>
        <div class="col-12"><button type="submit" name="contact_submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-send me-2"></i>Send Message</button></div>
      </div></form>
    </div>
    <div class="col-lg-6">
      <?php if($map_embed):?><div style="border-radius:16px;overflow:hidden;height:380px;"><?=$map_embed?></div>
      <?php else:?><div style="border-radius:16px;height:380px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;"><i class="bi bi-map" style="font-size:4rem;color:rgba(255,255,255,0.15);margin-bottom:12px;"></i><p style="color:rgba(255,255,255,0.3);font-size:0.85rem;">Map not configured.<br>Add Google Maps embed in Admin → Settings.</p></div><?php endif;?>
    </div>
  </div>
</div></section>
</div>
<?php include INCLUDES_PATH.'public_footer.php';?>
