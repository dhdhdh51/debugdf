<?php
require_once dirname(__DIR__) . '/config/config.php';
session_unset(); session_destroy();
redirect(SITE_URL . '/auth/login.php');
