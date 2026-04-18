<?php
require_once __DIR__ . '/config/config.php';
if (is_logged_in()) {
    $role = get_user_role();
    $map  = ['admin'=>'/admin/','student'=>'/student/','teacher'=>'/teacher/','parent'=>'/parent/'];
    redirect(SITE_URL . ($map[$role] ?? '/auth/login.php'));
}
require_once __DIR__ . '/public/home.php';
