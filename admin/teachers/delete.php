<?php
require_once dirname(__DIR__,2).'/config/config.php';
require_once INCLUDES_PATH.'auth_check.php';
auth_guard('admin');
$id=sanitize_int($_GET['id']??0);
$stmt=$pdo->prepare("SELECT * FROM teachers WHERE id=?");$stmt->execute([$id]);$t=$stmt->fetch();
if($t){if($t['photo'])delete_upload($t['photo']);if($t['user_id'])$pdo->prepare("DELETE FROM users WHERE id=?")->execute([$t['user_id']]);$pdo->prepare("DELETE FROM teachers WHERE id=?")->execute([$id]);set_flash('success',"Teacher '{$t['name']}' deleted.");}
else{set_flash('error','Not found.');}
redirect(SITE_URL.'/admin/teachers/');
