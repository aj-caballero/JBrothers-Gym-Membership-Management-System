<?php
// C:/Users/Kyle/GYM MEMBERSHIP/notifications/index.php
$pageTitle = 'Notifications';
require_once '../includes/header.php';

// A simple script to run to find expired memberships that haven't been tagged explicitly.
// In a real application, this might run via a cron job.
$today = date('Y-m-d');

// 1. Update status to Expired if end_date has passed
$pdo->query("UPDATE memberships SET status = 'Expired' WHERE end_date < '$today' AND status = 'Active'");

// 2. Fetch members needing attention
$sql = "SELECT ms.*, m.full_name, m.email, m.phone, mp.plan_name 
        FROM memberships ms 
        JOIN members m ON ms.member_id = m.id 
        JOIN membership_plans mp ON ms.plan_id = mp.id
        WHERE ms.status = 'Expired' OR ms.end_date BETWEEN '$today' AND DATE_ADD('$today', INTERVAL 7 DAY)
        ORDER BY ms.end_date ASC";
$stmt = $pdo->query($sql);
$alerts = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Membership Expiry Alerts</h3>
    </div>

    <p style="color: var(--text-muted); margin-bottom:20px;">
        This page shows memberships that are <strong>currently expired</strong> or will expire <strong>within 7 days</strong>.
    </p>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Plan</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alerts as $a): ?>
                    <?php 
                        $isExpired = (strtotime($a->end_date) < strtotime($today));
                        $rowColor = $isExpired ? 'rgba(255,0,0,0.05)' : 'rgba(255,193,7,0.05)';
                    ?>
                    <tr style="background-color: <?= $rowColor ?>;">
                        <td><strong><?= htmlspecialchars($a->full_name) ?></strong><br><small><?= htmlspecialchars($a->phone) ?></small></td>
                        <td><?= htmlspecialchars($a->plan_name) ?></td>
                        <td><?= formatDate($a->end_date) ?></td>
                        <td>
                            <?php if ($isExpired): ?>
                                <span class="badge badge-expired">Expired</span>
                            <?php else: ?>
                                <span class="badge badge-pending">Expiring Soon</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="../payments/add.php?member_id=<?= $a->member_id ?>" class="btn btn-sm btn-primary">Renew</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($alerts)): ?>
                    <tr><td colspan="5">All good! No expiring memberships right now.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
