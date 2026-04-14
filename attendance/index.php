<?php
// C:/Users/Kyle/GYM MEMBERSHIP/attendance/index.php
$pageTitle = 'Attendance Check-In';
require_once '../includes/header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = (int) $_POST['member_id'];
    
    if ($member_id > 0) {
        // Verify member status
        $memberStmt = $pdo->prepare("SELECT status, full_name FROM members WHERE id = ?");
        $memberStmt->execute([$member_id]);
        $member = $memberStmt->fetch();

        if ($member) {
            if ($member->status !== 'Active') {
                $error = "Access Denied: Membership is " . $member->status . ".";
            } else {
                // Check if already checked in today without checking out
                $today = date('Y-m-d');
                $checkStmt = $pdo->prepare("SELECT id FROM attendance_logs WHERE member_id = ? AND DATE(time_in) = ? AND time_out IS NULL");
                $checkStmt->execute([$member_id, $today]);
                $openLog = $checkStmt->fetch();

                if ($openLog) {
                    // Check Out
                    $outStmt = $pdo->prepare("UPDATE attendance_logs SET time_out = NOW() WHERE id = ?");
                    $outStmt->execute([$openLog->id]);
                    $success = "{$member->full_name} successfully Checked OUT.";
                } else {
                    // Check In
                    $inStmt = $pdo->prepare("INSERT INTO attendance_logs (member_id, time_in) VALUES (?, NOW())");
                    $inStmt->execute([$member_id]);
                    $success = "{$member->full_name} successfully Checked IN.";
                }
            }
        } else {
            $error = "Member not found.";
        }
    }
}

// Get recent logs for today
$todayStr = date('Y-m-d');
$logsStmt = $pdo->query("SELECT a.*, m.full_name FROM attendance_logs a JOIN members m ON a.member_id = m.id WHERE DATE(a.time_in) = '$todayStr' ORDER BY a.time_in DESC LIMIT 10");
$recentLogs = $logsStmt->fetchAll();

// Get active members for dropdown (exclude archived)
$membersStmt = $pdo->query("SELECT id, full_name, phone FROM members WHERE status = 'Active' AND deleted_at IS NULL ORDER BY full_name ASC");
$activeMembers = $membersStmt->fetchAll();
?>

<div class="form-row">
    <div class="form-group" style="flex: 1;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Check In / Out</h3>
            </div>
            
            <?php if ($success): ?>
                <div class="alert" style="background: rgba(40, 167, 69, 0.2); border-color: var(--success); color: var(--success);">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Select Member</label>
                    <select name="member_id" class="form-control searchable-select" required autofocus>
                        <option value="">-- Choose Member --</option>
                        <?php foreach ($activeMembers as $m): ?>
                            <option value="<?= $m->id ?>">
                                <?= htmlspecialchars($m->full_name) ?> (<?= htmlspecialchars($m->phone) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 18px; padding: 15px;">
                    <i class="fas fa-sign-in-alt"></i> Process Attendance
                </button>
            </form>

            <div style="margin-top: 20px; text-align: center;">
                <p><em>System automatically detects whether to check in or out based on today's logs.</em></p>
                <a href="history.php" class="btn btn-sm" style="background:var(--bg-surface-hover); color:#fff;">View Full History</a>
            </div>
        </div>
    </div>

    <div class="form-group" style="flex: 2;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Today's Activity</h3>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentLogs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log->full_name) ?></td>
                                <td><?= date('h:i A', strtotime($log->time_in)) ?></td>
                                <td><?= $log->time_out ? date('h:i A', strtotime($log->time_out)) : '-' ?></td>
                                <td>
                                    <?php if ($log->time_out): ?>
                                        <span class="badge" style="background:#6c757d;">Checked Out</span>
                                    <?php else: ?>
                                        <span class="badge badge-active">Active in Gym</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentLogs)): ?>
                            <tr><td colspan="4">No check-ins today yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
