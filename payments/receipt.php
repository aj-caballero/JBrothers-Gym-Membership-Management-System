<?php
// C:/Users/Kyle/GYM MEMBERSHIP/payments/receipt.php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_login();

$id = $_GET['id'] ?? 0;
$settings = getGymSettings($pdo);

$hasRequestedPlanColumn = dbHasColumn($pdo, 'payments', 'requested_plan_id');

$sql = "SELECT p.*, m.full_name, m.email, "
    . ($hasRequestedPlanColumn
        ? "COALESCE(mp.plan_name, mp_req.plan_name) AS plan_name, "
        : "mp.plan_name AS plan_name, ")
    . "ms.start_date, ms.end_date
       FROM payments p
       JOIN members m ON p.member_id = m.id
       LEFT JOIN memberships ms ON p.membership_id = ms.id
       LEFT JOIN membership_plans mp ON ms.plan_id = mp.id "
    . ($hasRequestedPlanColumn ? "LEFT JOIN membership_plans mp_req ON p.requested_plan_id = mp_req.id " : "")
    . "WHERE p.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$pay = $stmt->fetch();

if (!$pay) {
    die("Receipt not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?= str_pad($pay->id, 6, '0', STR_PAD_LEFT) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f4f4; color: #333; margin: 0; padding: 20px; }
        .receipt-container { background: #fff; max-width: 400px; margin: 0 auto; padding: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-radius: 8px; }
        .header { text-align: center; border-bottom: 2px dashed #ccc; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { margin: 0 0 5px 0; color: #1a1a2e; font-size: 24px; }
        .header p { margin: 0; color: #666; font-size: 14px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; }
        .divider { border-bottom: 1px solid #eee; margin: 20px 0; }
        .total-row { font-size: 18px; font-weight: bold; border-top: 2px dashed #ccc; padding-top: 15px; margin-top: 15px; }
        .footer { text-align: center; font-size: 12px; color: #888; margin-top: 30px; }
        .btn-print { display: block; width: 100%; padding: 15px; background: #1a1a2e; color: #fff; text-align: center; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px; cursor: pointer; border: none; }
        @media print { .btn-print, .back-link { display: none; } body { background: #fff; } .receipt-container { box-shadow: none; } }
    </style>
</head>
<body>

<div class="receipt-container">
    <div class="header">
        <h1><?= htmlspecialchars($settings->gym_name ?? 'Gym Membership System') ?></h1>
        <p><?= htmlspecialchars($settings->address ?? '123 Gym Street') ?></p>
        <p><?= htmlspecialchars($settings->contact_phone ?? '000-000-0000') ?></p>
    </div>

    <div class="row">
        <span>Receipt No:</span>
        <strong>#<?= str_pad($pay->id, 6, '0', STR_PAD_LEFT) ?></strong>
    </div>
    <div class="row">
        <span>Date:</span>
        <strong><?= date('M d, Y h:i A', strtotime($pay->payment_date)) ?></strong>
    </div>
    <div class="row">
        <span>Member:</span>
        <strong><?= htmlspecialchars($pay->full_name) ?></strong>
    </div>

    <div class="divider"></div>

    <div class="row" style="font-weight: 600;">
        <span>Item Description</span>
        <span>Amount</span>
    </div>
    
    <div class="row" style="margin-top: 10px;">
        <span style="max-width: 60%;">
            Membership Plan: <?= htmlspecialchars($pay->plan_name ?? 'N/A') ?>
            <?php if ($pay->start_date): ?>
                <br><small style="color: #666;"><?= formatDate($pay->start_date) ?> to <?= formatDate($pay->end_date) ?></small>
            <?php endif; ?>
        </span>
        <span><?= formatCurrency($pay->amount) ?></span>
    </div>

    <div class="row total-row">
        <span>TOTAL PAID</span>
        <span><?= formatCurrency($pay->amount) ?></span>
    </div>

    <div class="row mt-2">
        <span>Payment Method:</span>
        <span><?= htmlspecialchars($pay->payment_method) ?></span>
    </div>

    <?php if (property_exists($pay, 'gateway_transaction_id') && !empty($pay->gateway_transaction_id)): ?>
        <div class="row mt-2">
            <span>Gateway:</span>
            <span><?= htmlspecialchars((property_exists($pay, 'gateway') ? $pay->gateway : 'MangoPay') ?? 'MangoPay') ?></span>
        </div>
        <div class="row mt-2">
            <span>Gateway Ref:</span>
            <span><?= htmlspecialchars($pay->gateway_transaction_id) ?></span>
        </div>
        <div class="row mt-2">
            <span>Gateway Status:</span>
            <span><?= htmlspecialchars($pay->gateway_status ?? 'N/A') ?></span>
        </div>
    <?php endif; ?>

    <div class="footer">
        <p>Thank you for your payment!</p>
        <p>System Generated Receipt.</p>
    </div>

    <button onclick="window.print()" class="btn-print">Print Receipt</button>
    <div style="text-align: center; margin-top: 15px;" class="back-link">
        <a href="index.php" style="color: #666;">Back to Payments</a>
    </div>
</div>

</body>
</html>
