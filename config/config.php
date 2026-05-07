<?php
// C:/Users/Kyle/GYM MEMBERSHIP/config/config.php

// Application Constants
define('APP_NAME', 'Iron Forge Gym');
define('APP_URL', 'http://localhost/GYM%20MEMBERSHIP');

// Mail settings (override via environment variables if available)
define('MAIL_HOST', getenv('MAIL_HOST') ?: '');
define('MAIL_PORT', (int) (getenv('MAIL_PORT') ?: 587));
define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: '');
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: '');
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: 'tls');
define('MAIL_FROM_EMAIL', getenv('MAIL_FROM_EMAIL') ?: 'no-reply@localhost');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: APP_NAME);

// Timezone setting
date_default_timezone_set('Asia/Manila');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start(); // Buffer output to prevent "headers already sent" errors on redirects

// Redirect helper
function redirect($path) {
    header("Location: " . APP_URL . $path);
    exit();
}

// Ensure user is logged in
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        redirect('/index.php');
    }
}

// Ensure user has admin role
function require_admin() {
    require_login();
    if ($_SESSION['user_role'] !== 'admin') {
        redirect('/dashboard.php?error=unauthorized');
    }
}

// Check module permission
function has_permission($module) {
    if (!isset($_SESSION['user_role'])) return false;
    if ($_SESSION['user_role'] === 'admin') return true; // Admins have all permissions
    if ($_SESSION['user_role'] === 'member') return false;
    
    $perms = $_SESSION['user_permissions'] ?? [];
    return in_array($module, $perms);
}

// Require module access
function require_permission($module) {
    require_login();
    if (!has_permission($module)) {
        redirect('/dashboard.php?error=unauthorized');
    }
}
?>
