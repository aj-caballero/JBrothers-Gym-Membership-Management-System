<?php
// C:/Users/Kyle/GYM MEMBERSHIP/dashboard.php
$pageTitle = 'Dashboard';
require_once 'includes/header.php';

// Fetch quick stats
$activeMembers  = getActiveMembersCount($pdo);
$totalRevenue   = getTotalRevenue($pdo);
$totalMembers   = $pdo->query("SELECT COUNT(*) as c FROM members")->fetch()->c;
$expiringCount  = $pdo->query("SELECT COUNT(*) as c FROM memberships WHERE status='Active' AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetch()->c;

$todayDate      = date('Y-m-d');
$checkinsToday  = $pdo->query("SELECT COUNT(*) as c FROM attendance_logs WHERE DATE(time_in) = '$todayDate'")->fetch()->c;

// Recent payments
$recentPayments = $pdo->query("SELECT p.*, m.full_name as member_name FROM payments p JOIN members m ON p.member_id = m.id ORDER BY p.payment_date DESC LIMIT 6")->fetchAll();

// Recent check-ins
$recentCheckins = $pdo->query("SELECT a.*, m.full_name as member_name FROM attendance_logs a JOIN members m ON a.member_id = m.id ORDER BY a.time_in DESC LIMIT 6")->fetchAll();
?>

<!-- Stat Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon stat-icon-blue"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <div class="stat-label">Total Members</div>
            <div class="stat-value"><?= number_format($totalMembers) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-green"><i class="fas fa-user-check"></i></div>
        <div class="stat-info">
            <div class="stat-label">Active Members</div>
            <div class="stat-value"><?= number_format($activeMembers) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-green"><i class="fas fa-peso-sign"></i></div>
        <div class="stat-info">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value"><?= formatCurrency($totalRevenue) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-yellow"><i class="fas fa-clock-rotate-left"></i></div>
        <div class="stat-info">
            <div class="stat-label">Expiring (7 days)</div>
            <div class="stat-value"><?= number_format($expiringCount) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-blue"><i class="fas fa-arrow-right-to-bracket"></i></div>
        <div class="stat-info">
            <div class="stat-label">Check-ins Today</div>
            <div class="stat-value"><?= number_format($checkinsToday) ?></div>
        </div>
    </div>
</div>

<!-- Two-column tables -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

    <!-- Recent Payments -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Recent Payments</span>
            <a href="payments/index.php" class="btn btn-ghost btn-sm">View all <i class="fas fa-arrow-right"></i></a>
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
                            <td class="td-name"><?= htmlspecialchars($p->member_name) ?></td>
                            <td><?= formatCurrency($p->amount) ?></td>
                            <td class="td-muted"><?= formatDate($p->payment_date) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentPayments)): ?>
                        <tr><td colspan="3">
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fas fa-receipt"></i></div>
                                <h3>No payments yet</h3>
                                <p>Payments will appear here once recorded.</p>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Check-ins -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Recent Check-ins</span>
            <a href="attendance/index.php" class="btn btn-ghost btn-sm">View all <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Time In</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentCheckins as $c): ?>
                        <tr>
                            <td class="td-name"><?= htmlspecialchars($c->member_name) ?></td>
                            <td class="td-muted"><?= date('h:i A', strtotime($c->time_in)) ?></td>
                            <td>
                                <?php if ($c->time_out): ?>
                                    <span class="badge badge-inactive">Checked Out</span>
                                <?php else: ?>
                                    <span class="badge badge-active">In Gym</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentCheckins)): ?>
                        <tr><td colspan="3">
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fas fa-clock"></i></div>
                                <h3>No check-ins today</h3>
                                <p>Check-ins will appear here throughout the day.</p>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>
