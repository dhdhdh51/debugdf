<?php
require_once dirname(__DIR__,2).'/config/config.php';
require_once INCLUDES_PATH.'auth_check.php';
auth_guard('admin');
$id=sanitize_int($_GET['id']??0);
$stmt=$pdo->prepare("SELECT * FROM parents WHERE id=?");$stmt->execute([$id]);$p=$stmt->fetch();
if($p){if($p['user_id'])$pdo->prepare("DELETE FROM users WHERE id=?")->execute([$p['user_id']]);$pdo->prepare("DELETE FROM parents WHERE id=?")->execute([$id]);set_flash('success',"Parent '{$p['name']}' deleted.");}
else{set_flash('error','Not found.');}
redirect(SITE_URL.'/admin/parents/');
