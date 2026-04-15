<?php
// attendance/today_logs.php - server-rendered rows for Today's Activity table
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_login();

header('Content-Type: text/html; charset=UTF-8');

$todayStr = date('Y-m-d');
$stmt = $pdo->prepare("SELECT a.time_in, a.time_out, m.full_name, m.photo_path FROM attendance_logs a JOIN members m ON a.member_id = m.id WHERE DATE(a.time_in) = ? ORDER BY a.time_in DESC LIMIT 10");
$stmt->execute([$todayStr]);
$recentLogs = $stmt->fetchAll();

if (empty($recentLogs)) {
    ?>
    <tr><td colspan="4">
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-clock"></i></div>
            <h3>No check-ins today yet</h3>
        </div>
    </td></tr>
    <?php
    exit;
}

foreach ($recentLogs as $log):
    $photoUrl = getMemberPhotoUrl($log->photo_path);
    $parts = explode(' ', $log->full_name);
    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
    ?>
    <tr>
        <td>
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:32px;height:32px;border-radius:50%;overflow:hidden;background:var(--accent);flex-shrink:0;">
                    <?php if ($photoUrl): ?>
                        <img src="<?= htmlspecialchars($photoUrl) ?>" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#000;"><?= htmlspecialchars($initials) ?></div>
                    <?php endif; ?>
                </div>
                <span class="td-name"><?= htmlspecialchars($log->full_name) ?></span>
            </div>
        </td>
        <td><?= date('h:i A', strtotime($log->time_in)) ?></td>
        <td><?= $log->time_out ? date('h:i A', strtotime($log->time_out)) : '—' ?></td>
        <td>
            <?php if ($log->time_out): ?>
                <span class="badge" style="background:#6c757d22;color:#9292a4;border:1px solid #9292a436;">Checked Out</span>
            <?php else: ?>
                <span class="badge badge-active">Active in Gym</span>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>
