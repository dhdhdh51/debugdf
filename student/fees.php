<?php
require_once dirname(__DIR__).'/config/config.php';
require_once INCLUDES_PATH.'auth_check.php';
auth_guard('student');
$student=get_student_by_user_id((int)$_SESSION['user_id']);
if(!$student)redirect(SITE_URL.'/auth/login.php');
$fees=$pdo->prepare("SELECT f.* FROM fees f WHERE f.student_id=? ORDER BY f.created_at DESC");
$fees->execute([$student['id']]);$fees=$fees->fetchAll();
$total=array_sum(array_column($fees,'amount'));
$paid=array_sum(array_column(array_filter($fees,fn($f)=>$f['status']==='paid'),'amount'));
$pending=$total-$paid;
$payu_key=get_setting('payu_merchant_key','');$payu_mode=get_setting('payu_mode','test');
$payu_base=$payu_mode==='live'?'https://secure.payu.in/_payment':'https://test.payu.in/_payment';
$page_title='Fee Payment';
$breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/student/'],['label'=>'Fees','active'=>true]];
include INCLUDES_PATH.'header.php';
?>
<div class="row g-3 mb-3">
<div class="col-4"><div class="card text-center py-3"><div style="font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:800;color:#fff;"><?=currency_format($total)?></div><div style="font-size:0.75rem;color:var(--text-muted);">Total</div></div></div>
<div class="col-4"><div class="card text-center py-3"><div style="font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:800;color:#22c55e;"><?=currency_format($paid)?></div><div style="font-size:0.75rem;color:var(--text-muted);">Paid</div></div></div>
<div class="col-4"><div class="card text-center py-3"><div style="font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:800;color:#ef4444;"><?=currency_format($pending)?></div><div style="font-size:0.75rem;color:var(--text-muted);">Pending</div></div></div>
</div>
<div class="card"><div class="card-header"><i class="bi bi-receipt" style="color:#22c55e;margin-right:8px;"></i>My Fee Invoices</div>
<div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Invoice</th><th>Type</th><th>Amount</th><th>Due Date</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php if(empty($fees)):?><tr><td colspan="6" class="text-center py-5" style="color:var(--text-muted);">No fee invoices.</td></tr>
<?php else:foreach($fees as $f):?>
<tr><td><code><?=sanitize($f['invoice_no'])?></code></td><td style="font-size:0.85rem;"><?=sanitize($f['fee_type'])?></td>
<td style="font-weight:700;color:<?=$f['status']==='paid'?'#22c55e':'#ef4444'?>"><?=currency_format((float)$f['amount'])?></td>
<td style="font-size:0.8rem;color:<?=$f['due_date']&&$f['due_date']<date('Y-m-d')&&$f['status']==='pending'?'#ef4444':'var(--text-muted)'?>"><?=format_date($f['due_date'])?></td>
<td><span class="badge bg-<?=match($f['status']){'paid'=>'success','overdue'=>'danger',default=>'warning'}"><?=ucfirst($f['status'])?></span></td>
<td><?php if($f['status']!=='paid'&&!empty($payu_key)):?><button class="btn btn-primary btn-sm" onclick="payNow(<?=$f['id']?>,'<?=sanitize($f['invoice_no'])?>',<?=$f['amount']?>)"><i class="bi bi-credit-card me-1"></i>Pay</button><?php elseif($f['status']!=='paid'):?><span style="font-size:0.78rem;color:var(--text-muted);">Contact office</span><?php else:?><span style="color:#22c55e;font-size:0.78rem;"><i class="bi bi-check-circle me-1"></i>Paid</span><?php endif;?></td></tr>
<?php endforeach;endif;?>
</tbody></table></div></div>
<?php if(!empty($payu_key)):?>
<form id="payuForm" action="<?=$payu_base?>" method="POST" class="d-none">
<input type="hidden" name="key" value="<?=sanitize($payu_key)?>">
<input type="hidden" name="txnid" id="payu_txnid"><input type="hidden" name="amount" id="payu_amount">
<input type="hidden" name="productinfo" id="payu_product"><input type="hidden" name="firstname" value="<?=sanitize($student['name'])?>">
<input type="hidden" name="email" value="<?=sanitize($student['email']??'')?>"><input type="hidden" name="phone" value="<?=sanitize($student['phone']??'0000000000')?>">
<input type="hidden" name="surl" value="<?=get_setting('payu_surl',SITE_URL.'/payment/success.php')?>">
<input type="hidden" name="furl" value="<?=get_setting('payu_furl',SITE_URL.'/payment/failure.php')?>">
<input type="hidden" name="hash" id="payu_hash"><input type="hidden" name="udf1" id="payu_fee_id">
</form>
<script>
function payNow(feeId,invoiceNo,amount){
fetch('<?=SITE_URL?>/payment/payu.php?action=hash',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`fee_id=${feeId}&amount=${amount}&invoice=${invoiceNo}`})
.then(r=>r.json()).then(data=>{if(data.hash){document.getElementById('payu_txnid').value=data.txnid;document.getElementById('payu_amount').value=amount;document.getElementById('payu_product').value='School Fee: '+invoiceNo;document.getElementById('payu_hash').value=data.hash;document.getElementById('payu_fee_id').value=feeId;document.getElementById('payuForm').submit();}else{alert('Payment failed. Try again.');}}).catch(()=>alert('Network error.'));
}
</script><?php endif;?>
<?php include INCLUDES_PATH.'footer.php';?>
