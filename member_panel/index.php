<?php
// member_panel/index.php
$pageTitle = 'Dashboard';
require_once 'includes/header.php';

$member_id = $_SESSION['user_id'];

// Get member info (including new fields)
$stmtMe = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmtMe->execute([$member_id]);
$me = $stmtMe->fetch();

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

$photoUrl     = getMemberPhotoUrl($me->photo_path ?? null);
$membershipId = $me->membership_id ?? '—';
$parts        = explode(' ', $me->full_name ?? 'M');
$initials     = strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
?>

<!-- Membership Card Widget -->
<div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,#1c1c27 0%,#111118 100%);">
    <div style="padding:22px;">
        <div style="display:flex;gap:20px;align-items:center;flex-wrap:wrap;">
            <!-- Avatar -->
            <div style="width:72px;height:72px;border-radius:50%;overflow:hidden;border:2px solid var(--accent);box-shadow:0 0 16px var(--accent-ring);flex-shrink:0;">
                <?php if ($photoUrl): ?>
                    <img src="<?= htmlspecialchars($photoUrl) ?>?v=<?= time() ?>" style="width:100%;height:100%;object-fit:cover;">
                <?php else: ?>
                    <div style="width:100%;height:100%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700;color:#000;"><?= $initials ?></div>
                <?php endif; ?>
            </div>

            <!-- Info -->
            <div style="flex:1;min-width:160px;">
                <div style="font-size:18px;font-weight:700;color:var(--text-primary);"><?= htmlspecialchars($me->full_name) ?></div>
                <div style="font-size:12px;color:var(--text-muted);margin-bottom:8px;"><?= htmlspecialchars($me->email) ?></div>
                <div style="display:flex;align-items:center;gap:8px;background:var(--bg-input);border:1px solid var(--border-accent);border-radius:var(--radius-md);padding:6px 12px;width:fit-content;">
                    <i class="fas fa-id-card" style="color:var(--accent);font-size:13px;"></i>
                    <span style="font-family:monospace;font-size:13px;font-weight:700;color:var(--accent);letter-spacing:1px;"><?= htmlspecialchars($membershipId) ?></span>
                </div>
            </div>

            <!-- QR Code -->
            <div style="text-align:center;">
                <div style="background:white;padding:10px;border-radius:var(--radius-md);display:inline-block;box-shadow:0 4px 16px rgba(0,0,0,0.4);">
                    <div id="member-qr"></div>
                </div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:5px;">Your Attendance QR</div>
            </div>

            <!-- Card link -->
            <div>
                <a href="my_card.php" class="btn btn-primary btn-sm" target="_blank"><i class="fas fa-id-card"></i> My ID Card</a>
            </div>
        </div>
    </div>
</div>

<!-- Plan + Attendance -->
<div class="stats-grid" style="grid-template-columns:1fr 1fr;">
    <div class="card">
        <div class="card-header"><h3 class="card-title">My Membership Plan</h3></div>
        <div class="card-body">
            <?php if ($membership): ?>
                <h4 style="margin-top:0;color:var(--accent-text);"><?= htmlspecialchars($membership->plan_name) ?></h4>
                <p style="color:var(--text-secondary);font-size:13px;"><?= htmlspecialchars($membership->description) ?></p>
                <p><strong>Status:</strong> <span class="badge badge-<?= strtolower($membership->status) ?>"><?= htmlspecialchars($membership->status) ?></span></p>
                <p><strong>Valid Until:</strong> <?= formatDate($membership->end_date) ?></p>
            <?php else: ?>
                <p style="color:var(--text-muted);">No active membership plan.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Recent Attendance</h3></div>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Date</th><th>Time In</th><th>Time Out</th></tr></thead>
                <tbody>
                    <?php foreach ($attendance as $log): ?>
                        <tr>
                            <td><?= formatDate(date('Y-m-d', strtotime($log->time_in))) ?></td>
                            <td><?= date('h:i A', strtotime($log->time_in)) ?></td>
                            <td><?= $log->time_out ? date('h:i A', strtotime($log->time_out)) : 'Still in' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($attendance)): ?>
                        <tr><td colspan="3" style="text-align:center;color:var(--text-muted);">No attendance records.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById("member-qr"), {
    text: "<?= htmlspecialchars($membershipId) ?>",
    width: 100, height: 100,
    colorDark: "#000000", colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H
});
</script>

<?php require_once '../includes/footer.php'; ?>
