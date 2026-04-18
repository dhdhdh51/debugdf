<?php
require_once dirname(__DIR__).'/config/config.php';
require_once INCLUDES_PATH.'mailer.php';
require_once ROOT_PATH.'payment/payu.php';
$salt=get_setting('payu_merchant_salt','');$data=$_POST;
if(empty($data)){redirect(SITE_URL.'/student/fees.php');}
$status=sanitize($data['status']??'');$txnid=sanitize($data['txnid']??'');
$amount=sanitize_float($data['amount']??0);$fee_id=sanitize_int($data['udf1']??0);
if(!verify_payu_hash($data,$salt)){set_flash('error','Payment verification failed.');redirect(SITE_URL.'/student/fees.php');}
if(strtolower($status)==='success'){
$pdo->prepare("UPDATE fees SET status='paid' WHERE id=?")->execute([$fee_id]);
$pdo->prepare("INSERT INTO transactions (fee_id,amount,payment_method,txn_id,status) VALUES (?,?,'PayU',?,'success')")->execute([$fee_id,$amount,$txnid]);
$fee_stmt=$pdo->prepare("SELECT f.*,s.name,s.email FROM fees f JOIN students s ON f.student_id=s.id WHERE f.id=?");
$fee_stmt->execute([$fee_id]);$fee=$fee_stmt->fetch();
if($fee&&$fee['email'])SchoolMailer::sendPaymentConfirmation($fee['email'],$fee['name'],$txnid,$amount);
}else{redirect(SITE_URL.'/payment/failure.php?txnid='.urlencode($txnid));}
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Payment Successful</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?=ASSETS_URL?>/css/style.css" rel="stylesheet">
</head><body style="min-height:100vh;display:flex;align-items:center;justify-content:center;">
<div style="width:100%;max-width:440px;background:var(--navy-2);border:1px solid rgba(34,197,94,0.25);border-radius:20px;padding:40px;text-align:center;">
<div style="font-size:3.5rem;margin-bottom:16px;">✅</div>
<h3 style="font-family:'Playfair Display',serif;color:#22c55e;margin-bottom:8px;">Payment Successful!</h3>
<p style="color:var(--text-muted);">Your fee payment has been processed successfully.</p>
<div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:12px;padding:16px;margin:20px 0;text-align:left;">
<div style="display:flex;justify-content:space-between;margin-bottom:8px;"><span style="color:var(--text-muted);font-size:0.82rem;">Transaction ID</span><code><?=sanitize($txnid)?></code></div>
<div style="display:flex;justify-content:space-between;margin-bottom:8px;"><span style="color:var(--text-muted);font-size:0.82rem;">Amount</span><strong style="color:#22c55e;"><?=currency_format($amount)?></strong></div>
<div style="display:flex;justify-content:space-between;"><span style="color:var(--text-muted);font-size:0.82rem;">Date</span><span><?=date('d M Y, h:i A')?></span></div>
</div>
<div class="d-flex gap-2 justify-content-center"><a href="<?=SITE_URL?>/student/fees.php" class="btn btn-primary">View Receipts</a><a href="<?=SITE_URL?>/student/" class="btn btn-outline-secondary">Dashboard</a></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>
