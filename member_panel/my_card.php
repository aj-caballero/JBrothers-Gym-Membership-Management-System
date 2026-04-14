<?php
// member_panel/my_card.php — Member-facing printable ID card
require_once 'includes/header.php';

$member_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT m.*, ms.end_date as plan_end, mp.plan_name FROM members m LEFT JOIN memberships ms ON ms.member_id = m.id AND ms.status='Active' LEFT JOIN membership_plans mp ON mp.id = ms.plan_id WHERE m.id = ?");
$stmt->execute([$member_id]);
$member = $stmt->fetch();

$settings     = getGymSettings($pdo);
$gymName      = $settings->gym_name ?? 'JBrothers Gym';
$photoUrl     = getMemberPhotoUrl($member->photo_path ?? null);
$membershipId = $member->membership_id ?? '—';
$parts        = explode(' ', $member->full_name ?? 'M');
$initials     = strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));

// Output the standalone card page directly (no sidebar)
// We need to end the current layout and output a clean page
// Since includes/header.php already output HTML, we close it and open fresh
?>
<style>
/* Override layout for card page */
.app-container, .main-content, .content-wrapper, .sidebar, .topbar { display: none !important; }
body { background: #0a0a0f !important; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 20px; padding: 20px; }

.id-card { width: 380px; background: linear-gradient(135deg,#1c1c27 0%,#111118 60%,#16161f 100%); border-radius: 20px; overflow: hidden; border: 1px solid rgba(34,197,94,0.25); box-shadow: 0 20px 60px rgba(0,0,0,0.7), inset 0 1px 0 rgba(255,255,255,0.08); position: relative; }
.card-glow { position:absolute; top:-40px; left:-40px; right:-40px; height:200px; background:radial-gradient(ellipse at 50% 0%,rgba(34,197,94,0.18) 0%,transparent 70%); pointer-events:none; }
.card-header-stripe { background: linear-gradient(90deg,#22c55e,#16a34a); padding: 14px 20px; display:flex; align-items:center; justify-content:space-between; }
.card-header-stripe .gym-name { font-size:14px; font-weight:800; color:#000; }
.card-header-stripe .card-label { font-size:10px; font-weight:700; color:rgba(0,0,0,0.65); text-transform:uppercase; letter-spacing:1.5px; }
.card-body { padding:20px; display:flex; gap:16px; align-items:flex-start; }
.photo-wrap { width:80px; height:80px; border-radius:12px; overflow:hidden; border:2px solid rgba(34,197,94,0.5); flex-shrink:0; background:#1c1c27; }
.photo-wrap img { width:100%; height:100%; object-fit:cover; }
.photo-initials { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:28px; font-weight:700; color:#000; background:#22c55e; }
.member-name { font-size:17px; font-weight:700; color:#f1f1f3; margin-bottom:3px; }
.member-email { font-size:11px; color:#9292a4; margin-bottom:10px; }
.info-row { display:flex; justify-content:space-between; margin-top:6px; }
.info-label { font-size:10px; color:#55556a; text-transform:uppercase; letter-spacing:0.8px; }
.info-value { font-size:12px; color:#f1f1f3; font-weight:600; }
.card-footer { padding:14px 20px; border-top:1px solid rgba(255,255,255,0.06); display:flex; align-items:center; justify-content:space-between; }
.membership-id-label { font-size:9px; color:#55556a; text-transform:uppercase; letter-spacing:1px; margin-bottom:3px; }
.membership-id-value { font-size:14px; font-weight:700; color:#22c55e; font-family:monospace; letter-spacing:1px; }
.qr-block { background:white; padding:6px; border-radius:8px; }
.action-bar { display:flex; gap:12px; flex-wrap:wrap; justify-content:center; }
.btn { display:inline-flex; align-items:center; gap:7px; padding:10px 20px; border-radius:10px; border:none; font-family:'Inter',sans-serif; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; transition:all 0.15s; }
.btn-primary { background:#22c55e; color:#000; }
.btn-secondary { background:rgba(255,255,255,0.08); color:#f1f1f3; border:1px solid rgba(255,255,255,0.12); }
@media print { body { background:white !important; min-height:unset; padding:0; } .action-bar { display:none; } .id-card { box-shadow:none; border:1px solid #ccc; } }
</style>

<div class="id-card" id="id-card-el">
    <div class="card-glow"></div>
    <div class="card-header-stripe">
        <div class="gym-name"><?= htmlspecialchars($gymName) ?></div>
        <div class="card-label">Membership Card</div>
    </div>
    <div class="card-body">
        <div class="photo-wrap">
            <?php if ($photoUrl): ?>
                <img src="<?= htmlspecialchars($photoUrl) ?>?v=<?= time() ?>">
            <?php else: ?>
                <div class="photo-initials"><?= $initials ?></div>
            <?php endif; ?>
        </div>
        <div>
            <div class="member-name"><?= htmlspecialchars($member->full_name) ?></div>
            <div class="member-email"><?= htmlspecialchars($member->email) ?></div>
            <div class="info-row">
                <div><div class="info-label">Status</div><div class="info-value"><?= $member->status ?></div></div>
                <div><div class="info-label">Valid Until</div><div class="info-value"><?= $member->plan_end ? formatDate($member->plan_end) : '—' ?></div></div>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <div>
            <div class="membership-id-label">Membership ID</div>
            <div class="membership-id-value"><?= htmlspecialchars($membershipId) ?></div>
        </div>
        <div class="qr-block"><div id="card-qr"></div></div>
    </div>
</div>

<div class="action-bar">
    <button class="btn btn-primary" onclick="downloadCard()"><i class="fas fa-download"></i> Download</button>
    <button class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
new QRCode(document.getElementById("card-qr"), {
    text: "<?= htmlspecialchars($membershipId) ?>",
    width: 64, height: 64,
    colorDark: "#000000", colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H
});
function downloadCard() {
    html2canvas(document.getElementById('id-card-el'), { scale: 3, useCORS: true })
        .then(canvas => {
            const a = document.createElement('a');
            a.download = 'my-membership-card.png';
            a.href = canvas.toDataURL('image/png');
            a.click();
        });
}
</script>

<?php require_once '../includes/footer.php'; ?>
