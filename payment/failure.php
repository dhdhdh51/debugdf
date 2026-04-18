<?php
require_once dirname(__DIR__).'/config/config.php';
$txnid=sanitize($_GET['txnid']??($_POST['txnid']??''));
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Payment Failed</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?=ASSETS_URL?>/css/style.css" rel="stylesheet">
</head><body style="min-height:100vh;display:flex;align-items:center;justify-content:center;">
<div style="width:100%;max-width:440px;background:var(--navy-2);border:1px solid rgba(239,68,68,0.25);border-radius:20px;padding:40px;text-align:center;">
<div style="font-size:3.5rem;margin-bottom:16px;">❌</div>
<h3 style="font-family:'Playfair Display',serif;color:#ef4444;margin-bottom:8px;">Payment Failed</h3>
<p style="color:var(--text-muted);">No amount was deducted from your account.</p>
<?php if($txnid):?><p style="font-size:0.82rem;color:var(--text-muted);">Reference: <code><?=sanitize($txnid)?></code></p><?php endif;?>
<div class="alert alert-warning text-start" style="font-size:0.82rem;margin:20px 0;text-align:left!important;">Possible reasons: Insufficient balance, wrong OTP, bank timeout, or cancelled.</div>
<div class="d-flex gap-2 justify-content-center"><a href="<?=SITE_URL?>/student/fees.php" class="btn btn-primary">Try Again</a><a href="<?=SITE_URL?>/student/" class="btn btn-outline-secondary">Dashboard</a></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>
