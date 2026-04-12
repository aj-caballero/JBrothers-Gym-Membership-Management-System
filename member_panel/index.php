<?php
// C:/Users/Kyle/GYM MEMBERSHIP/member_panel/index.php
$pageTitle = 'Dashboard';
require_once 'includes/header.php';

$member_id = $_SESSION['user_id'];

// Get active or recent membership plan
$stmt = $pdo->prepare("SELECT ms.*, mp.plan_name, mp.description 
                       FROM memberships ms 
                       JOIN membership_plans mp ON ms.plan_id = mp.id 
                       WHERE ms.member_id = ? 
                       ORDER BY ms.end_date DESC LIMIT 1");
$stmt->execute([$member_id]);
$membership = $stmt->fetch();

// Get recent attendance
$stmtAtt = $pdo->prepare("SELECT * FROM attendance_logs WHERE member_id = ? ORDER BY time_in DESC LIMIT 5");
$stmtAtt->execute([$member_id]);
$attendance = $stmtAtt->fetchAll();
?>

<div class="stats-grid" style="grid-template-columns: 1fr 1fr;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">My Membership Plan</h3>
        </div>
        <div class="card-body">
            <?php if ($membership): ?>
                <h4 style="margin-top:0; color:var(--primary);"><?= htmlspecialchars($membership->plan_name) ?></h4>
                <p><?= htmlspecialchars($membership->description) ?></p>
                <p><strong>Status:</strong> <span class="badge badge-<?= strtolower($membership->status) ?>"><?= htmlspecialchars($membership->status) ?></span></p>
                <p><strong>Valid Until:</strong> <?= formatDate($membership->end_date) ?></p>
            <?php else: ?>
                <p>You do not have any active or recent membership plans.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Attendance</h3>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance as $log): ?>
                        <tr>
                            <td><?= formatDate(date('Y-m-d', strtotime($log->time_in))) ?></td>
                            <td><?= date('h:i A', strtotime($log->time_in)) ?></td>
                            <td><?= $log->time_out ? date('h:i A', strtotime($log->time_out)) : 'Not logged out' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($attendance)): ?>
                        <tr><td colspan="3">No attendance records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
