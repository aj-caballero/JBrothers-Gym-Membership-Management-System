<?php
// C:/Users/Kyle/GYM MEMBERSHIP/dashboard.php
$pageTitle = 'Dashboard';
require_once 'includes/header.php';

// Fetch quick stats
$activeMembers = getActiveMembersCount($pdo);
$totalRevenue = getTotalRevenue($pdo);

// Recent payments
$recentPayments = $pdo->query("SELECT p.*, m.full_name as member_name FROM payments p JOIN members m ON p.member_id = m.id ORDER BY p.payment_date DESC LIMIT 5")->fetchAll();

// Recent check-ins
$recentCheckins = $pdo->query("SELECT a.*, m.full_name as member_name FROM attendance_logs a JOIN members m ON a.member_id = m.id ORDER BY a.time_in DESC LIMIT 5")->fetchAll();
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3>Active Members</h3>
            <p class="stat-value"><?= number_format($activeMembers) ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-info">
            <h3>Total Revenue</h3>
            <p class="stat-value"><?= formatCurrency($totalRevenue) ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <h3>Check-ins Today</h3>
            <p class="stat-value">
                <?php
                $todayDate = date('Y-m-d');
                $checkinsToday = $pdo->query("SELECT COUNT(*) as count FROM attendance_logs WHERE DATE(time_in) = '{$todayDate}'")->fetch()->count;
                echo number_format($checkinsToday);
                ?>
            </p>
        </div>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns: 1fr 1fr;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Payments</h3>
            <a href="payments/index.php" class="btn btn-sm btn-primary">View All</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentPayments as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p->member_name) ?></td>
                            <td><?= formatCurrency($p->amount) ?></td>
                            <td><?= formatDate($p->payment_date) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentPayments)): ?>
                        <tr><td colspan="3">No recent payments.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Check-ins</h3>
            <a href="attendance/index.php" class="btn btn-sm btn-primary">View All</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Time In</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentCheckins as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c->member_name) ?></td>
                            <td><?= date('h:i A', strtotime($c->time_in)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentCheckins)): ?>
                        <tr><td colspan="2">No check-ins today.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
