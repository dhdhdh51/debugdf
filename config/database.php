<?php
if (!defined('DB_HOST')) die('Direct access not allowed.');
try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log('[School ERP DB] ' . $e->getMessage());
    die('<!DOCTYPE html><html><head><title>DB Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head><body style="background:#0a0f1e;color:#fff;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;">
    <div style="text-align:center;max-width:500px;padding:40px;">
    <div style="font-size:3rem;margin-bottom:16px;">⚠️</div>
    <h4>Database Connection Failed</h4>
    <p style="color:rgba(255,255,255,0.5);">Check DB credentials in <code>config/config.php</code> or run the installer at <code>/install/</code></p>
    </div></body></html>');
}
