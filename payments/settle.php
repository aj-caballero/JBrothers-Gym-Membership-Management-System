<?php
// payments/settle.php — convert pending payment to paid and activate membership
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_login();

if (!has_permission('payments')) {
    redirect('/dashboard.php?error=unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/payments/index.php');
}

$paymentId = (int) ($_POST['id'] ?? 0);
if ($paymentId <= 0) {
    redirect('/payments/index.php?error=invalid');
}

if (!dbHasColumn($pdo, 'payments', 'requested_plan_id')) {
    redirect('/payments/index.php?error=' . urlencode('Settlement requires migration: missing requested_plan_id column.'));
}

$hasGatewayStatusColumn = dbHasColumn($pdo, 'payments', 'gateway_status');

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT p.* FROM payments p WHERE p.id = ? LIMIT 1 FOR UPDATE");
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch();

    if (!$payment) {
        throw new Exception('Payment record not found.');
    }

    if ($payment->status !== 'Pending') {
        throw new Exception('Only pending payments can be settled.');
    }

    $planId = (int) ($payment->requested_plan_id ?? 0);
    if ($planId <= 0) {
        throw new Exception('No requested plan attached to this pending payment.');
    }

    $planStmt = $pdo->prepare("SELECT duration_days FROM membership_plans WHERE id = ? LIMIT 1");
    $planStmt->execute([$planId]);
    $plan = $planStmt->fetch();
    if (!$plan) {
        throw new Exception('Requested plan no longer exists.');
    }

    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime('+' . (int)$plan->duration_days . ' days'));

    $msStmt = $pdo->prepare("INSERT INTO memberships (member_id, plan_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'Active')");
    $msStmt->execute([$payment->member_id, $planId, $startDate, $endDate]);
    $membershipId = $pdo->lastInsertId();

    $gatewayStatus = $hasGatewayStatusColumn ? ($payment->gateway_status ?? null) : null;
    if ($hasGatewayStatusColumn && $payment->payment_method === 'PayMongo' && $gatewayStatus === 'awaiting_payment_method') {
        $gatewayStatus = 'paid';
    }

    if ($hasGatewayStatusColumn) {
        $updatePay = $pdo->prepare("UPDATE payments SET membership_id = ?, status = 'Paid', gateway_status = ? WHERE id = ?");
        $updatePay->execute([$membershipId, $gatewayStatus, $paymentId]);
    } else {
        $updatePay = $pdo->prepare("UPDATE payments SET membership_id = ?, status = 'Paid' WHERE id = ?");
        $updatePay->execute([$membershipId, $paymentId]);
    }

    $updateMember = $pdo->prepare("UPDATE members SET status = 'Active' WHERE id = ?");
    $updateMember->execute([$payment->member_id]);

    $pdo->commit();
    redirect('/payments/receipt.php?id=' . $paymentId);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirect('/payments/index.php?error=' . urlencode($e->getMessage()));
}
