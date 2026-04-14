<?php
// attendance/index.php
$pageTitle = 'Attendance Check-In';
require_once '../includes/header.php';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = (int) $_POST['member_id'];

    if ($member_id > 0) {
        $memberStmt = $pdo->prepare("SELECT status, full_name, deleted_at FROM members WHERE id = ?");
        $memberStmt->execute([$member_id]);
        $member = $memberStmt->fetch();

        if ($member) {
            if ($member->deleted_at !== null) {
                $error = "This membership has been archived.";
            } elseif ($member->status !== 'Active') {
                $error = "Access Denied: Membership is " . $member->status . ".";
            } else {
                $today     = date('Y-m-d');
                $checkStmt = $pdo->prepare("SELECT id FROM attendance_logs WHERE member_id = ? AND DATE(time_in) = ? AND time_out IS NULL");
                $checkStmt->execute([$member_id, $today]);
                $openLog   = $checkStmt->fetch();

                if ($openLog) {
                    $pdo->prepare("UPDATE attendance_logs SET time_out = NOW() WHERE id = ?")->execute([$openLog->id]);
                    $success = "{$member->full_name} successfully Checked OUT.";
                } else {
                    $pdo->prepare("INSERT INTO attendance_logs (member_id, time_in) VALUES (?, NOW())")->execute([$member_id]);
                    $success = "{$member->full_name} successfully Checked IN.";
                }
            }
        } else {
            $error = "Member not found.";
        }
    }
}

// Today's logs
$todayStr  = date('Y-m-d');
$logsStmt  = $pdo->query("SELECT a.*, m.full_name, m.photo_path FROM attendance_logs a JOIN members m ON a.member_id = m.id WHERE DATE(a.time_in) = '$todayStr' ORDER BY a.time_in DESC LIMIT 10");
$recentLogs = $logsStmt->fetchAll();

// Active members for dropdown (exclude archived)
$membersStmt  = $pdo->query("SELECT id, full_name, phone FROM members WHERE status = 'Active' AND deleted_at IS NULL ORDER BY full_name ASC");
$activeMembers = $membersStmt->fetchAll();
?>

<!-- Tab Bar -->
<div style="display:flex;gap:4px;margin-bottom:20px;background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:4px;width:fit-content;">
    <button id="tab-manual" class="tab-btn active-tab" onclick="switchTab('manual')">
        <i class="fas fa-list-check"></i> Manual Select
    </button>
    <button id="tab-scanner" class="tab-btn" onclick="switchTab('scanner')">
        <i class="fas fa-qrcode"></i> QR Scanner
    </button>
</div>

<div class="form-row" style="align-items:flex-start;">

    <!-- LEFT: Form panel (manual OR scanner) -->
    <div style="flex:1;">

        <!-- MANUAL TAB -->
        <div id="panel-manual">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Check In / Out</h3></div>

                <?php if ($success): ?>
                    <div class="alert alert-success" style="margin:16px 22px 0;"><i class="fas fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger" style="margin:16px 22px 0;"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" style="padding:22px;">
                    <div class="form-group">
                        <label>Select Member</label>
                        <select name="member_id" class="form-control searchable-select" required>
                            <option value="">— Choose Member —</option>
                            <?php foreach ($activeMembers as $m): ?>
                                <option value="<?= $m->id ?>">
                                    <?= htmlspecialchars($m->full_name) ?> (<?= htmlspecialchars($m->phone) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;font-size:16px;padding:13px;">
                        <i class="fas fa-sign-in-alt"></i> Process Attendance
                    </button>
                </form>

                <div style="padding:0 22px 18px;text-align:center;">
                    <p style="color:var(--text-muted);font-size:13px;"><em>Auto-detects check-in or check-out based on today's logs.</em></p>
                    <a href="history.php" class="btn btn-ghost btn-sm" style="margin-top:8px;">View Full History</a>
                </div>
            </div>
        </div>

        <!-- QR SCANNER TAB -->
        <div id="panel-scanner" style="display:none;">
            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-qrcode" style="color:var(--accent);"></i> QR Code Scanner</h3></div>

                <!-- Feedback banner -->
                <div id="scan-feedback" style="display:none;margin:16px 22px 0;padding:14px 18px;border-radius:var(--radius-md);font-size:14px;font-weight:600;display:flex;align-items:center;gap:10px;"></div>

                <div style="padding:22px;">
                    <div id="qr-reader" style="border-radius:var(--radius-md);overflow:hidden;border:2px solid var(--border-strong);background:#000;"></div>

                    <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
                        <button id="start-scan-btn" class="btn btn-primary" onclick="startScanner()">
                            <i class="fas fa-camera"></i> Start Camera
                        </button>
                        <button id="stop-scan-btn" class="btn btn-ghost" onclick="stopScanner()" style="display:none;">
                            <i class="fas fa-stop"></i> Stop
                        </button>
                    </div>

                    <p style="color:var(--text-muted);font-size:12px;margin-top:12px;">
                        <i class="fas fa-info-circle"></i> Point camera at a member's QR code. Check-in or out is automatic.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Today's Activity -->
    <div style="flex:2;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Today's Activity</h3>
                <span style="font-size:12px;color:var(--text-muted);"><?= date('l, M d Y') ?></span>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Member</th><th>Time In</th><th>Time Out</th><th>Status</th></tr>
                    </thead>
                    <tbody id="activity-table">
                        <?php foreach ($recentLogs as $log): ?>
                            <tr>
                                <td>
                                    <?php
                                    $photoUrl = getMemberPhotoUrl($log->photo_path);
                                    $logParts = explode(' ', $log->full_name);
                                    $logInitials = strtoupper(substr($logParts[0],0,1) . (isset($logParts[1]) ? substr($logParts[1],0,1) : ''));
                                    ?>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div style="width:32px;height:32px;border-radius:50%;overflow:hidden;background:var(--accent);flex-shrink:0;">
                                            <?php if ($photoUrl): ?>
                                                <img src="<?= htmlspecialchars($photoUrl) ?>" style="width:100%;height:100%;object-fit:cover;">
                                            <?php else: ?>
                                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#000;"><?= $logInitials ?></div>
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
                        <?php if (empty($recentLogs)): ?>
                            <tr><td colspan="4"><div class="empty-state"><div class="empty-icon"><i class="fas fa-clock"></i></div><h3>No check-ins today yet</h3></div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.tab-btn {
    padding: 8px 18px;
    border: none;
    border-radius: var(--radius-md);
    background: transparent;
    color: var(--text-secondary);
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: all 0.15s;
}
.tab-btn:hover { background: var(--bg-hover); color: var(--text-primary); }
.active-tab { background: var(--accent-soft) !important; color: var(--accent-text) !important; }
</style>

<!-- html5-qrcode library -->
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrCode = null;
let scanCooldown = false;

function switchTab(tab) {
    document.getElementById('panel-manual').style.display  = tab === 'manual'  ? 'block' : 'none';
    document.getElementById('panel-scanner').style.display = tab === 'scanner' ? 'block' : 'none';
    document.getElementById('tab-manual').classList.toggle('active-tab',  tab === 'manual');
    document.getElementById('tab-scanner').classList.toggle('active-tab', tab === 'scanner');
    if (tab === 'manual') stopScanner();
}

function startScanner() {
    if (html5QrCode) return;
    html5QrCode = new Html5Qrcode("qr-reader");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 250, height: 250 } },
        onScanSuccess,
        () => {}   // silence per-frame errors
    ).then(() => {
        document.getElementById('start-scan-btn').style.display = 'none';
        document.getElementById('stop-scan-btn').style.display  = 'inline-flex';
    }).catch(err => {
        showFeedback(false, 'Camera error: ' + err);
    });
}

function stopScanner() {
    if (html5QrCode) {
        html5QrCode.stop().then(() => {
            html5QrCode.clear();
            html5QrCode = null;
            document.getElementById('start-scan-btn').style.display = 'inline-flex';
            document.getElementById('stop-scan-btn').style.display  = 'none';
        });
    }
}

function onScanSuccess(decodedText) {
    if (scanCooldown) return;
    scanCooldown = true;
    setTimeout(() => scanCooldown = false, 2500); // 2.5s cooldown

    fetch('scan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'membership_id=' + encodeURIComponent(decodedText)
    })
    .then(r => r.json())
    .then(data => {
        showFeedback(data.success, data.message, data.action);
        if (data.success) {
            prependActivityRow(data);
        }
    })
    .catch(() => showFeedback(false, 'Server error. Please try again.'));
}

function showFeedback(success, message, action) {
    const el = document.getElementById('scan-feedback');
    el.style.display = 'flex';
    if (success) {
        const isIn = action === 'in';
        el.style.background  = isIn ? 'rgba(34,197,94,0.15)'  : 'rgba(59,130,246,0.15)';
        el.style.color       = isIn ? '#22c55e' : '#3b82f6';
        el.style.border      = '1px solid ' + (isIn ? 'rgba(34,197,94,0.3)' : 'rgba(59,130,246,0.3)');
        el.innerHTML = `<i class="fas fa-circle-check"></i> ${message}`;
    } else {
        el.style.background = 'rgba(239,68,68,0.15)';
        el.style.color      = '#ef4444';
        el.style.border     = '1px solid rgba(239,68,68,0.3)';
        el.innerHTML = `<i class="fas fa-circle-exclamation"></i> ${message}`;
    }
    setTimeout(() => el.style.display = 'none', 5000);
}

function prependActivityRow(data) {
    const tbody = document.getElementById('activity-table');
    const tr = document.createElement('tr');
    tr.style.animation = 'fadeIn 0.4s ease';
    tr.innerHTML = `
        <td><div style="display:flex;align-items:center;gap:10px;">
            <div style="width:32px;height:32px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#000;">
                ${data.name.split(' ').map(w=>w[0]).join('').substring(0,2).toUpperCase()}
            </div>
            <span class="td-name">${data.name}</span>
        </div></td>
        <td>${data.action === 'in' ? data.time : '—'}</td>
        <td>${data.action === 'out' ? data.time : '—'}</td>
        <td>${data.action === 'in'
            ? '<span class="badge badge-active">Active in Gym</span>'
            : '<span class="badge" style="background:#6c757d22;color:#9292a4;border:1px solid #9292a436;">Checked Out</span>'}</td>
    `;
    // Remove empty-state row if present
    const empty = tbody.querySelector('.empty-state');
    if (empty) empty.closest('tr').remove();
    tbody.insertBefore(tr, tbody.firstChild);
}
</script>

<?php require_once '../includes/footer.php'; ?>
