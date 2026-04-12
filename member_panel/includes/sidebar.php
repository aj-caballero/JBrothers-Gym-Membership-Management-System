<?php
// C:/Users/Kyle/GYM MEMBERSHIP/member_panel/includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fas fa-dumbbell"></i></div>
        <div class="brand-name"><?= htmlspecialchars($settings->gym_name ?? 'Gym System') ?></div>
    </div>
    
    <ul class="sidebar-nav">
        <li class="nav-title">Member Panel</li>
        <li class="nav-item <?= ($current_page == 'index.php') ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/member_panel/index.php"><i class="fas fa-home"></i> Home</a>
        </li>
        <li class="nav-item <?= ($current_page == 'profile.php') ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/member_panel/profile.php"><i class="fas fa-user"></i> My Profile</a>
        </li>
    </ul>
</aside>
