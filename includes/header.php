<?php
// C:/Users/Kyle/GYM MEMBERSHIP/includes/header.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';

require_login();

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
$expireStmt = $pdo->prepare("UPDATE memberships SET status = 'Expired' WHERE end_date < ? AND status = 'Active'");
$expireStmt->execute([$today]);
$pdo->query("UPDATE members SET status = 'Expired' WHERE status = 'Active' AND deleted_at IS NULL AND id NOT IN (SELECT member_id FROM memberships WHERE status = 'Active')");

// Send automatic expiry reminder emails once per day for active memberships ending within 7 days.
try {
    $autoReminderStmt = $pdo->prepare(
        "SELECT ms.member_id, ms.end_date, mp.plan_name, m.full_name, m.email
         FROM memberships ms
         JOIN members m ON m.id = ms.member_id
         JOIN membership_plans mp ON mp.id = ms.plan_id
         WHERE ms.status = 'Active'
           AND ms.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
           AND m.deleted_at IS NULL
           AND m.email IS NOT NULL AND m.email <> ''
           AND NOT EXISTS (
               SELECT 1
               FROM notifications n
               WHERE n.type = 'Expiry'
                 AND n.member_id = ms.member_id
                 AND DATE(n.created_at) = CURDATE()
                 AND n.message LIKE 'Expiry reminder email:%'
           )"
    );
    $autoReminderStmt->execute();
    $expiringMembers = $autoReminderStmt->fetchAll();

    if (!empty($expiringMembers)) {
        $insertNotifStmt = $pdo->prepare("INSERT INTO notifications (type, member_id, message, is_read) VALUES ('Expiry', ?, ?, 0)");

        foreach ($expiringMembers as $expiring) {
            $result = sendMembershipExpiryReminderEmail($pdo, $expiring, $expiring);
            if (!empty($result['ok'])) {
                $message = 'Expiry reminder email: ' . ($result['days_left'] ?? 0) . ' day(s) left (Plan: ' . $expiring->plan_name . ', End: ' . $expiring->end_date . ')';
                $insertNotifStmt->execute([$expiring->member_id, $message]);
            }
        }
    }
} catch (Exception $e) {
    // Keep UI working even when reminder delivery fails.
}

$pageTitle = $pageTitle ?? 'Dashboard';

// Build user initials for avatar
$nameParts = explode(' ', $_SESSION['user_name'] ?? 'U');
$initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle . ' — ' . ($settings->gym_name ?? APP_NAME)) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="app-container">

        <?php include 'sidebar.php'; ?>

        <main class="main-content">

            <!-- Top Bar -->
            <header class="topbar">
                <div class="topbar-left">
                    <button id="sidebar-toggle" class="btn-icon" title="Toggle sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2><?= htmlspecialchars($pageTitle) ?></h2>
                </div>

                <div class="topbar-right">
                    <!-- User profile chip -->
                    <div class="user-chip">
                        <div class="user-avatar"><?= $initials ?></div>
                        <div>
                            <div class="user-chip-name"><?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]) ?></div>
                            <div class="user-chip-role"><?= ucfirst(htmlspecialchars($_SESSION['user_role'])) ?></div>
                        </div>
                    </div>

                    <!-- Logout -->
                    <a href="<?= APP_URL ?>/auth/logout.php" class="btn-logout" title="Sign out">
                        <i class="fas fa-arrow-right-from-bracket"></i>
                        <span>Sign Out</span>
                    </a>
                </div>
            </header>

            <div class="content-wrapper">
<?php
// Flash message helper — reads from ?success= or ?error= query params
if (isset($_GET['success'])) {
    $msgs = [
        'added'   => 'Record added successfully.',
        'updated' => 'Record updated successfully.',
        'deleted' => 'Record deleted successfully.',
        'saved'   => 'Changes saved.',
    ];
    $msg = $msgs[$_GET['success']] ?? 'Action completed successfully.';
    echo '<div class="alert alert-success"><i class="fas fa-circle-check"></i> ' . htmlspecialchars($msg) . '</div>';
}
if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    echo '<div class="alert alert-danger"><i class="fas fa-lock"></i> You do not have permission to access that page.</div>';
}
?>
