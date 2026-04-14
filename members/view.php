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

$photoUrl    = getMemberPhotoUrl($member->photo_path);
$membershipId = $member->membership_id ?? '—';

// Initials for avatar fallback
$parts    = explode(' ', $member->full_name);
$initials = strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
?>

<!-- Membership Card Hero -->
<div class="card" style="margin-bottom:20px;overflow:visible;">
    <div style="background:linear-gradient(135deg,var(--bg-elevated) 0%,var(--bg-card) 100%);padding:28px 28px 0;border-radius:var(--radius-lg) var(--radius-lg) 0 0;">
        <div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;">
            <!-- Photo -->
            <div style="position:relative;">
                <div style="width:100px;height:100px;border-radius:50%;overflow:hidden;border:3px solid var(--accent);box-shadow:0 0 20px var(--accent-ring);">
                    <?php if ($photoUrl): ?>
                        <img src="<?= htmlspecialchars($photoUrl) ?>?v=<?= time() ?>" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <div style="width:100%;height:100%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:700;color:#000;"><?= $initials ?></div>
                    <?php endif; ?>
                </div>
                <span class="badge badge-<?= strtolower($member->status) ?>" style="position:absolute;bottom:-6px;left:50%;transform:translateX(-50%);white-space:nowrap;">
                    <?= $member->status ?>
                </span>
            </div>

            <!-- Info -->
            <div style="flex:1;padding-bottom:20px;">
                <h2 style="margin:0 0 4px;font-size:22px;color:var(--text-primary);"><?= htmlspecialchars($member->full_name) ?></h2>
                <div style="color:var(--text-muted);font-size:13px;margin-bottom:12px;"><?= htmlspecialchars($member->email) ?></div>

                <div style="display:flex;align-items:center;gap:10px;background:var(--bg-input);border:1px solid var(--border-accent);border-radius:var(--radius-md);padding:8px 14px;width:fit-content;">
                    <i class="fas fa-id-card" style="color:var(--accent);font-size:16px;"></i>
                    <span style="font-family:monospace;font-size:15px;font-weight:700;color:var(--accent);letter-spacing:1px;"><?= htmlspecialchars($membershipId) ?></span>
                </div>

                <div style="display:flex;gap:8px;margin-top:14px;flex-wrap:wrap;">
                    <a href="edit.php?id=<?= $member->id ?>" class="btn btn-sm btn-secondary"><i class="fas fa-pen"></i> Edit</a>
                    <a href="<?= APP_URL ?>/payments/add.php?member_id=<?= $member->id ?>" class="btn btn-sm btn-primary"><i class="fas fa-credit-card"></i> Add Payment</a>
                    <a href="id_card.php?id=<?= $member->id ?>" class="btn btn-sm btn-ghost" target="_blank"><i class="fas fa-id-card"></i> ID Card</a>
                </div>
            </div>

            <!-- QR Code -->
            <div style="text-align:center;padding-bottom:20px;">
                <div style="background:white;padding:12px;border-radius:var(--radius-md);display:inline-block;box-shadow:0 4px 16px rgba(0,0,0,0.4);">
                    <div id="member-qr"></div>
                </div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:6px;">Scan for attendance</div>
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

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById("member-qr"), {
    text: "<?= htmlspecialchars($membershipId) ?>",
    width: 120,
    height: 120,
    colorDark: "#000000",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H
});
</script>

<?php require_once '../includes/footer.php'; ?>
