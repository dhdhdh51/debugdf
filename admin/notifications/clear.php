<?php
require_once dirname(__DIR__,2).'/config/config.php';
require_once INCLUDES_PATH.'auth_check.php';
auth_guard('admin');
$pdo->exec("DELETE FROM notifications WHERE role IN ('admin','all') OR user_id IN (SELECT id FROM users WHERE role='admin')");
set_flash('success','All notifications cleared.');
redirect(SITE_URL.'/admin/notifications/');
