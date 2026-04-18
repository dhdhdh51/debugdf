<?php
require_once dirname(__DIR__).'/config/config.php';
require_once INCLUDES_PATH.'auth_check.php';
auth_guard('student');
$student=get_student_by_user_id((int)$_SESSION['user_id']);
if(!$student)redirect(SITE_URL.'/auth/login.php');
header('Location: '.SITE_URL.'/admin/students/id-card.php?id='.(int)$student['id']);
exit;
