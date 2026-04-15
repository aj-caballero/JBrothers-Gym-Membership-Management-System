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
$qrImageUrl   = APP_URL . '/qrcode.php?data=' . rawurlencode($membershipId);
$parts        = explode(' ', $member->full_name ?? 'M');
$initials     = strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));

// Output the standalone card page directly (no sidebar)
// We need to end the current layout and output a clean page
// Since includes/header.php already output HTML, we close it and open fresh
?>
<style>
/* Override layout for card page */
.sidebar, .topbar { display: none !important; }
body { background: #f3f4f6 !important; color: #111827; }
.app-container, .main-content { display: block !important; min-height: 100vh; }
.main-content { margin-left: 0 !important; width: 100% !important; }
.content-wrapper {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 20px;
    padding: 20px;
}

.id-card {
    width: 380px;
    background: #ffffff;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #d1d5db;
    box-shadow: 0 12px 28px rgba(17,24,39,0.08);
}
.card-glow { display: none; }
.card-header-stripe {
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    padding: 14px 20px;
    display:flex;
    align-items:center;
    justify-content:space-between;
}
.card-header-stripe .gym-name { font-size:14px; font-weight:700; color:#111827; }
.card-header-stripe .card-label { font-size:10px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:1.2px; }
.card-body { padding:18px 20px; display:flex; gap:14px; align-items:flex-start; }
.photo-wrap { width:78px; height:78px; border-radius:10px; overflow:hidden; border:1px solid #d1d5db; flex-shrink:0; background:#f9fafb; }
.photo-wrap img { width:100%; height:100%; object-fit:cover; }
.photo-initials { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:26px; font-weight:700; color:#111827; background:#e5e7eb; }
.member-name { font-size:17px; font-weight:700; color:#111827; margin-bottom:4px; line-height:1.2; }
.member-email { font-size:11px; color:#6b7280; margin-bottom:10px; }
.info-row { display:flex; gap:24px; margin-top:4px; }
.info-label { font-size:10px; color:#6b7280; text-transform:uppercase; letter-spacing:0.7px; }
.info-value { font-size:12px; color:#111827; font-weight:600; }
.card-footer { padding:14px 20px; border-top:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between; }
.membership-id-label { font-size:9px; color:#6b7280; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px; }
.membership-id-value { font-size:14px; font-weight:700; color:#111827; font-family:monospace; letter-spacing:0.8px; }
.qr-block {
    background:#ffffff;
    padding:6px;
    border-radius:8px;
    border:1px solid #d1d5db;
    width:108px;
    height:108px;
    display:flex;
    align-items:center;
    justify-content:center;
}
.qr-block img { width:96px; height:96px; display:block; }
.action-bar { display:flex; gap:12px; flex-wrap:wrap; justify-content:center; }
.btn {
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:10px 18px;
    border-radius:8px;
    border:1px solid #d1d5db;
    font-family:'Inter',sans-serif;
    font-size:13px;
    font-weight:600;
    cursor:pointer;
    text-decoration:none;
    transition:all 0.15s;
}
.btn-primary { background:#111827; color:#ffffff; border-color:#111827; }
.btn-secondary { background:#ffffff; color:#111827; }
.btn:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(17,24,39,0.10); }

@media (max-width: 480px) {
    .id-card { width: 100%; max-width: 380px; }
    .content-wrapper { padding: 16px; }
    .card-body { gap: 12px; }
    .info-row { gap: 16px; }
}

@media print {
    @page { size: landscape; margin: 10mm; }

    body {
        background: white !important;
        min-height: unset;
        padding: 0;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .content-wrapper {
        min-height: auto;
        padding: 0;
        justify-content: flex-start;
    }

    .action-bar { display: none; }
    .card-glow { display: none; }

    .id-card {
        width: 95mm;
        box-shadow: none;
        border: 1px solid #d1d5db;
        background: #ffffff !important;
    }

    .card-header-stripe {
        background: #ffffff !important;
        border-bottom: 1px solid #e5e7eb;
    }

    .card-header-stripe .gym-name,
    .card-header-stripe .card-label,
    .member-name,
    .member-email,
    .info-label,
    .info-value,
    .membership-id-label,
    .membership-id-value {
        color: #111111 !important;
    }

    .membership-id-value {
        font-weight: 700;
        letter-spacing: 0.6px;
    }

    .card-footer {
        border-top: 1px solid #e5e7eb;
    }

    .photo-wrap {
        border-color: #d1d5db;
    }
}
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
        <div class="qr-block">
            <img src="<?= htmlspecialchars($qrImageUrl) ?>" alt="Membership QR code">
        </div>
    </div>
</div>

<div class="action-bar">
    <button class="btn btn-primary" onclick="downloadCard()"><i class="fas fa-download"></i> Download</button>
    <button class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
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
