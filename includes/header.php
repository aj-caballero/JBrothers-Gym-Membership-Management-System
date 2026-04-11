<?php
// C:/Users/Kyle/GYM MEMBERSHIP/includes/header.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

require_login();
$settings = getGymSettings($pdo);
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
