<?php
// C:/Users/Kyle/GYM MEMBERSHIP/includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fas fa-dumbbell"></i></div>
        <div class="brand-name"><?= htmlspecialchars($settings->gym_name ?? 'Gym System') ?></div>
    </div>
    
    <ul class="sidebar-nav">
        <li class="nav-item <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        </li>
        <li class="nav-item <?= ($current_dir == 'members') ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/members/index.php"><i class="fas fa-users"></i> Members</a>
        </li>
        <li class="nav-item <?= ($current_dir == 'plans') ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/plans/index.php"><i class="fas fa-tags"></i> Membership Plans</a>
        </li>
        <li class="nav-item <?= ($current_dir == 'payments') ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/payments/index.php"><i class="fas fa-credit-card"></i> Payments</a>
        </li>
        <li class="nav-item <?= ($current_dir == 'attendance') ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/attendance/index.php"><i class="fas fa-clock"></i> Attendance</a>
        </li>
        
        <?php if ($_SESSION['user_role'] === 'admin'): ?>
        <li class="nav-item <?= ($current_dir == 'reports') ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/reports/index.php"><i class="fas fa-chart-bar"></i> Reports</a>
        </li>
        <li class="nav-title">Admin</li>
        <li class="nav-item <?= ($current_dir == 'admin' && $current_page == 'users.php') ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/admin/users.php"><i class="fas fa-user-shield"></i> User Accounts</a>
        </li>
        <li class="nav-item <?= ($current_dir == 'admin' && $current_page == 'settings.php') ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/admin/settings.php"><i class="fas fa-cog"></i> Settings</a>
        </li>
        <?php endif; ?>
    </ul>
</aside>
