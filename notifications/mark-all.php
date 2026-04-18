<?php
require_once dirname(__DIR__) . '/config/config.php';
if (!is_logged_in()) { http_response_code(403); exit; }
mark_all_read();
redirect(SITE_URL . '/' . get_user_role() . '/');
