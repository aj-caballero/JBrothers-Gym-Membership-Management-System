<?php
// C:/Users/Kyle/GYM MEMBERSHIP/includes/header.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

require_login();
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'member') {
    redirect('/member_panel/index.php');
}

// Automatically enforce module permissions based on folder structure
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$protected_modules = ['members', 'plans', 'payments', 'attendance', 'reports'];
if (in_array($current_dir, $protected_modules)) {
    if (!has_permission($current_dir)) {
        redirect('/dashboard.php?error=unauthorized');
    }
}

$settings = getGymSettings($pdo);

// Auto-check and update expired memberships and members globally
$today = date('Y-m-d');
$pdo->query("UPDATE memberships SET status = 'Expired' WHERE end_date < '$today' AND status = 'Active'");
$pdo->query("UPDATE members SET status = 'Expired' WHERE status = 'Active' AND id NOT IN (SELECT member_id FROM memberships WHERE status = 'Active')");

$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle . ' - ' . ($settings->gym_name ?? APP_NAME)) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left">
                    <button id="sidebar-toggle" class="btn-icon"><i class="fas fa-bars"></i></button>
                    <h2><?= htmlspecialchars($pageTitle) ?></h2>
                </div>
                <div class="topbar-right">
                    <div class="user-profile dropdown">
                        <span class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?> <small>(<?= ucfirst(htmlspecialchars($_SESSION['user_role'])) ?>)</small></span>
                        <a href="<?= APP_URL ?>/auth/logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </header>
            <div class="content-wrapper">
