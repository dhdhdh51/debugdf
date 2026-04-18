<?php
require_once dirname(__DIR__).'/config/config.php';
$key=get_setting('payu_merchant_key','');$salt=get_setting('payu_merchant_salt','');$mode=get_setting('payu_mode','test');
if(isset($_GET['action'])&&$_GET['action']==='hash'){
if(!is_logged_in()||get_user_role()!=='student'){http_response_code(403);echo json_encode(['error'=>'Unauthorized']);exit;}
$fee_id=sanitize_int($_POST['fee_id']??0);$amount=sanitize_float($_POST['amount']??0);$invoice=sanitize($_POST['invoice']??'');
if(!$fee_id||!$amount||empty($key)||empty($salt)){echo json_encode(['error'=>'Invalid parameters']);exit;}
$student=get_student_by_user_id((int)$_SESSION['user_id']);
if(!$student){echo json_encode(['error'=>'Student not found']);exit;}
$fee_stmt=$pdo->prepare("SELECT * FROM fees WHERE id=? AND student_id=? AND status!='paid'");
$fee_stmt->execute([$fee_id,$student['id']]);$fee=$fee_stmt->fetch();
if(!$fee){echo json_encode(['error'=>'Fee not found or already paid']);exit;}
$txnid='TXN'.time().rand(100,999);$productinfo='School Fee: '.$invoice;$firstname=$student['name'];$email=$student['email']??'';
$hash_str="{$key}|{$txnid}|{$amount}|{$productinfo}|{$firstname}|{$email}|{$fee_id}||||||||||||{$salt}";
$hash=strtolower(hash('sha512',$hash_str));
header('Content-Type: application/json');echo json_encode(['hash'=>$hash,'txnid'=>$txnid]);exit;
}
function verify_payu_hash(array $response,string $salt):bool{
if(empty($response['hash']))return false;
$hash_str="{$salt}|{$response['status']}|||||||||{$response['udf1']}|{$response['email']}|{$response['firstname']}|{$response['productinfo']}|{$response['amount']}|{$response['txnid']}|".get_setting('payu_merchant_key','');
return hash_equals(strtolower(hash('sha512',$hash_str)),strtolower($response['hash']));
}
