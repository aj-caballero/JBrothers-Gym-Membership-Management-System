<?php
// C:/Users/Kyle/GYM MEMBERSHIP/includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));

// Generate initials from the gym name for the brand icon
$gymName = $settings->gym_name ?? 'Gym';
$initials = strtoupper(substr($gymName, 0, 1));
?>
<aside class="sidebar" id="sidebar">

    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="fas fa-dumbbell"></i>
        </div>
        <div>
            <div class="brand-name"><?= htmlspecialchars($gymName) ?></div>
            <div class="brand-sub">Management Portal</div>
        </div>
    </div>

    <!-- Navigation -->
    <ul class="sidebar-nav">

        <!-- Main -->
        <li class="nav-section-label">Main</li>

        <li class="nav-item <?= ($current_page === 'dashboard.php' && $current_dir !== 'member_panel') ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/dashboard.php">
                <span class="nav-icon"><i class="fas fa-grid-2"></i></span>
                Dashboard
            </a>
        </li>

        <?php if (has_permission('members')): ?>
        <li class="nav-item <?= ($current_dir === 'members') ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/members/index.php">
                <span class="nav-icon"><i class="fas fa-users"></i></span>
                Members
            </a>
        </li>
        <?php endif; ?>

        <?php if (has_permission('payments')): ?>
        <li class="nav-item <?= ($current_dir === 'payments') ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/payments/index.php">
                <span class="nav-icon"><i class="fas fa-credit-card"></i></span>
                Payments
            </a>
        </li>
        <?php endif; ?>

        <?php if (has_permission('plans')): ?>
        <li class="nav-item <?= ($current_dir === 'plans') ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/plans/index.php">
                <span class="nav-icon"><i class="fas fa-layer-group"></i></span>
                Membership Plans
            </a>
        </li>
        <?php endif; ?>

        <?php if (has_permission('attendance')): ?>
        <li class="nav-item <?= ($current_dir === 'attendance') ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/attendance/index.php">
                <span class="nav-icon"><i class="fas fa-clock"></i></span>
                Attendance
            </a>
        </li>
        <?php endif; ?>

        <?php if (has_permission('reports')): ?>
        <li class="nav-item <?= ($current_dir === 'reports') ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/reports/index.php">
                <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
                Reports
            </a>
        </li>
        <?php endif; ?>

        <!-- Admin -->
        <?php if ($_SESSION['user_role'] === 'admin'): ?>
        <li class="nav-section-label" style="margin-top: 8px;">Admin</li>

        <li class="nav-item <?= ($current_dir === 'admin' && $current_page === 'users.php') ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/admin/users.php">
                <span class="nav-icon"><i class="fas fa-user-shield"></i></span>
                User Accounts
            </a>
        </li>

        <li class="nav-item <?= ($current_dir === 'admin' && $current_page === 'settings.php') ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/admin/settings.php">
                <span class="nav-icon"><i class="fas fa-sliders"></i></span>
                Settings
            </a>
        </li>
        <?php endif; ?>

    </ul>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <a href="<?= APP_URL ?>/auth/logout.php">
            <span class="nav-icon"><i class="fas fa-arrow-right-from-bracket"></i></span>
            Sign Out
        </a>
    </div>

</aside>
