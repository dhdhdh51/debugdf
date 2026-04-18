<?php
require_once dirname(__DIR__) . '/config/config.php';
if (!is_logged_in()) { http_response_code(403); exit; }
$id = sanitize_int($_GET['id'] ?? 0);
if ($id) mark_notification_read($id);
$role = get_user_role();
$back = $_SERVER['HTTP_REFERER'] ?? SITE_URL . '/' . $role . '/';
redirect($back);
