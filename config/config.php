<?php
// C:/Users/Kyle/GYM MEMBERSHIP/config/config.php

// Application Constants
define('APP_NAME', 'Iron Forge Gym');
define('APP_URL', 'http://localhost/GYM%20MEMBERSHIP');

// Timezone setting
date_default_timezone_set('Asia/Manila');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
?>
