<?php
/**
 * School ERP — Main Configuration  v2.0
 */
error_reporting(0);
ini_set('display_errors', 0);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
if (session_status() === PHP_SESSION_NONE) session_start();

define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_NAME',    'school_erp');
define('DB_CHARSET', 'utf8mb4');

define('SITE_URL',      'http://localhost');
define('ROOT_PATH',     dirname(__DIR__) . '/');
define('CONFIG_PATH',   ROOT_PATH . 'config/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('UPLOADS_PATH',  ROOT_PATH . 'uploads/');
define('ASSETS_URL',    SITE_URL . '/assets');
define('UPLOADS_URL',   SITE_URL . '/uploads');

define('CSRF_TOKEN_LENGTH', 32);
define('OTP_EXPIRY_MINUTES', 10);
define('SESSION_TIMEOUT',    1800);
define('APP_NAME',        'School ERP System');
define('APP_VERSION',     '2.0.0');
define('ACADEMIC_YEAR',   '2025-2026');
define('PASS_PERCENTAGE',  33);
define('MAX_FILE_SIZE',  5 * 1024 * 1024);
define('ALLOWED_IMAGES', ['jpg','jpeg','png','gif','webp']);
define('ALLOWED_DOCS',   ['pdf','doc','docx','jpg','jpeg','png']);

date_default_timezone_set('Asia/Kolkata');

require_once CONFIG_PATH . 'database.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'notifications.php';

function get_setting(string $key, string $default = ''): string {
    global $pdo;
    static $cache = [];
    if (!isset($cache[$key])) {
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1");
            $stmt->execute([$key]);
            $row = $stmt->fetch();
            $cache[$key] = $row ? (string)$row['setting_value'] : $default;
        } catch (Exception $e) { return $default; }
    }
    return $cache[$key];
}
