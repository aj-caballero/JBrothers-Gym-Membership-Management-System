<?php
// members/view.php
$pageTitle = 'Member Profile';
require_once '../includes/header.php';

$id     = (int)($_GET['id'] ?? 0);
$stmt   = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$id]);
$member = $stmt->fetch();
if (!$member) redirect('/members/index.php');

$stmtMs = $pdo->prepare("SELECT ms.*, mp.plan_name FROM memberships ms JOIN membership_plans mp ON ms.plan_id = mp.id WHERE ms.member_id = ? ORDER BY ms.id DESC LIMIT 1");
$stmtMs->execute([$id]);
$membership = $stmtMs->fetch();

$stmtPay = $pdo->prepare("SELECT * FROM payments WHERE member_id = ? ORDER BY payment_date DESC");
$stmtPay->execute([$id]);
$payments = $stmtPay->fetchAll();

$stmtAtt = $pdo->prepare("SELECT * FROM attendance_logs WHERE member_id = ? ORDER BY time_in DESC LIMIT 50");
$stmtAtt->execute([$id]);
$attendanceLogs = $stmtAtt->fetchAll();

$photoUrl    = getMemberPhotoUrl($member->photo_path);
$membershipId = $member->membership_id ?? '—';
$qrImageUrl  = APP_URL . '/qrcode.php?data=' . rawurlencode($membershipId);
$memberStatusClass = preg_replace('/[^a-z0-9_-]/', '-', strtolower(trim((string)($member->status ?? 'inactive'))));

// Initials for avatar fallback
$parts    = explode(' ', $member->full_name);
$initials = strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
?>

<style>
.member-hero-wrap {
    background: linear-gradient(135deg, var(--bg-elevated) 0%, var(--bg-card) 100%);
    padding: 28px;
}

.member-hero-row {
    display: flex;
    gap: 24px;
    align-items: center;
    flex-wrap: wrap;
}

.member-avatar-block {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    min-width: 110px;
}

.member-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid var(--accent);
    box-shadow: 0 0 20px var(--accent-ring);
    flex-shrink: 0;
}

.member-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.member-avatar-fallback {
    width: 100%;
    height: 100%;
    background: var(--accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 700;
    color: #000;
}

.member-hero-info {
    flex: 1;
    min-width: 240px;
}

.member-meta {
    color: var(--text-muted);
    font-size: 13px;
    margin-bottom: 12px;
}

.member-id-chip {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--bg-input);
    border: 1px solid var(--border-accent);
    border-radius: var(--radius-md);
    padding: 8px 14px;
    width: fit-content;
}

.member-id-chip i {
    color: var(--accent);
    font-size: 16px;
}

.member-id-chip span {
    font-family: monospace;
    font-size: 15px;
    font-weight: 700;
    color: var(--accent);
    letter-spacing: 1px;
}

.member-actions {
    display: flex;
    gap: 8px;
    margin-top: 14px;
    flex-wrap: wrap;
}

.member-hero-qr {
    text-align: center;
    min-width: 160px;
}

.member-hero-qr-box {
    background: white;
    padding: 12px;
    border-radius: var(--radius-md);
    display: inline-block;
    box-shadow: 0 4px 16px rgba(0,0,0,0.4);
}

.member-hero-qr-box img {
    width: 120px;
    height: 120px;
    display: block;
}

.member-hero-qr-note {
    font-size: 11px;
    color: var(--text-muted);
    margin-top: 6px;
}

@media (max-width: 900px) {
    .member-hero-wrap {
        padding: 22px;
    }

    .member-hero-row {
        align-items: flex-start;
        gap: 18px;
    }
}

@media (max-width: 640px) {
    .member-hero-wrap {
        padding: 18px;
    }

    .member-hero-row {
        flex-direction: column;
        align-items: stretch;
    }

    .member-avatar-block {
        min-width: 0;
    }

    .member-hero-qr {
        width: 100%;
        text-align: left;
    }
}
</style>

<!-- Membership Card Hero -->
<div class="card" style="margin-bottom:20px;overflow:visible;">
    <div class="member-hero-wrap">
        <div class="member-hero-row">
            <!-- Photo -->
            <div class="member-avatar-block">
                <div class="member-avatar">
                    <?php if ($photoUrl): ?>
                        <img src="<?= htmlspecialchars($photoUrl) ?>?v=<?= time() ?>" alt="Member profile photo">
                    <?php else: ?>
                        <div class="member-avatar-fallback"><?= $initials ?></div>
                    <?php endif; ?>
                </div>
                <span class="badge badge-<?= htmlspecialchars($memberStatusClass) ?>">
                    <?= $member->status ?>
                </span>
            </div>

            <!-- Info -->
            <div class="member-hero-info">
                <h2 style="margin:0 0 4px;font-size:22px;color:var(--text-primary);"><?= htmlspecialchars($member->full_name) ?></h2>
                <div class="member-meta"><?= htmlspecialchars($member->email) ?></div>

                <div class="member-id-chip">
                    <i class="fas fa-id-card"></i>
                    <span><?= htmlspecialchars($membershipId) ?></span>
                </div>

                <div class="member-actions">
                    <a href="edit.php?id=<?= $member->id ?>" class="btn btn-sm btn-secondary"><i class="fas fa-pen"></i> Edit</a>
                    <a href="<?= APP_URL ?>/payments/add.php?member_id=<?= $member->id ?>" class="btn btn-sm btn-primary"><i class="fas fa-credit-card"></i> Add Payment</a>
                    <a href="id_card.php?id=<?= $member->id ?>" class="btn btn-sm btn-ghost" target="_blank"><i class="fas fa-id-card"></i> ID Card</a>
                </div>
            </div>

            <!-- QR Code -->
            <div class="member-hero-qr">
                <div class="member-hero-qr-box">
                    <img src="<?= htmlspecialchars($qrImageUrl) ?>" alt="Membership QR code">
                </div>
                <div class="member-hero-qr-note">Scan for attendance</div>
            </div>
        </div>
    </div>
</div>

<div class="form-row">
    <!-- Profile Details -->
    <div style="flex:1;">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Profile Details</h3></div>
            <div style="padding:18px 22px;line-height:2.2;">
                <div style="display:grid;grid-template-columns:140px 1fr;gap:4px 0;">
                    <span style="color:var(--text-muted);font-size:13px;">Phone</span>         <span><?= htmlspecialchars($member->phone ?: '—') ?></span>
                    <span style="color:var(--text-muted);font-size:13px;">Gender</span>        <span><?= htmlspecialchars($member->gender ?: '—') ?></span>
                    <span style="color:var(--text-muted);font-size:13px;">Date of Birth</span> <span><?= formatDate($member->date_of_birth) ?></span>
                    <span style="color:var(--text-muted);font-size:13px;">Join Date</span>     <span><?= formatDate($member->join_date) ?></span>
                    <span style="color:var(--text-muted);font-size:13px;">Address</span>       <span><?= htmlspecialchars($member->address ?: '—') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Membership -->
    <div style="flex:1;">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Current Membership</h3></div>
            <div style="padding:18px 22px;">
                <?php if ($membership): ?>
                    <div style="display:grid;grid-template-columns:110px 1fr;gap:4px 0;line-height:2.2;">
                        <span style="color:var(--text-muted);font-size:13px;">Plan</span>       <strong><?= htmlspecialchars($membership->plan_name) ?></strong>
                        <span style="color:var(--text-muted);font-size:13px;">Valid From</span> <span><?= formatDate($membership->start_date) ?></span>
                        <span style="color:var(--text-muted);font-size:13px;">Valid Until</span><span><?= formatDate($membership->end_date) ?></span>
                        <span style="color:var(--text-muted);font-size:13px;">Status</span>     <span><span class="badge badge-<?= strtolower($membership->status) ?>"><?= $membership->status ?></span></span>
                    </div>
                <?php else: ?>
                    <p style="color:var(--text-muted);">No membership. <a href="<?= APP_URL ?>/payments/add.php?member_id=<?= $member->id ?>">Add one now</a>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Payment History -->
<div class="card">
    <div class="card-header"><h3 class="card-title">Payment History</h3></div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($payments as $pay): ?>
                    <tr>
                        <td><?= formatDate($pay->payment_date) ?></td>
                        <td><?= formatCurrency($pay->amount) ?></td>
                        <td><?= $pay->payment_method ?></td>
                        <td><span class="badge badge-<?= strtolower($pay->status) ?>"><?= $pay->status ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="4"><div class="empty-state"><div class="empty-icon"><i class="fas fa-receipt"></i></div><h3>No payments</h3></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Attendance History -->
<div class="card">
    <div class="card-header"><h3 class="card-title">Attendance History</h3></div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Date</th><th>Time In</th><th>Time Out</th></tr></thead>
            <tbody>
                <?php foreach ($attendanceLogs as $log): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($log->time_in)) ?></td>
                        <td><span class="badge badge-paid"><?= date('h:i A', strtotime($log->time_in)) ?></span></td>
                        <td>
                            <?php if ($log->time_out): ?>
                                <span class="badge badge-inactive"><?= date('h:i A', strtotime($log->time_out)) ?></span>
                            <?php else: ?>
                                <span style="color:var(--text-muted);font-size:13px;">Not logged</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($attendanceLogs)): ?>
                    <tr><td colspan="3"><div class="empty-state"><div class="empty-icon"><i class="fas fa-clock"></i></div><h3>No attendance records</h3></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
