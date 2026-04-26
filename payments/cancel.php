<?php
// payments/cancel.php — cancel a pending payment without activating membership
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
    redirect('/payments/index.php?error=Invalid payment id.');
}

try {
    $pdo->beginTransaction();

    $hasGatewayStatusColumn = dbHasColumn($pdo, 'payments', 'gateway_status');

    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ? LIMIT 1 FOR UPDATE");
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch();

    if (!$payment) {
        throw new Exception('Payment record not found.');
    }

    if ($payment->status !== 'Pending') {
        throw new Exception('Only pending payments can be cancelled.');
    }

    if (!empty($payment->membership_id)) {
        throw new Exception('This payment is already linked to a membership and cannot be cancelled.');
    }

    $gatewayStatus = $hasGatewayStatusColumn ? ($payment->gateway_status ?? null) : null;
    if ($hasGatewayStatusColumn && $payment->payment_method === 'PayMongo') {
        $gatewayStatus = 'cancelled';
    }

    if ($hasGatewayStatusColumn) {
        $update = $pdo->prepare("UPDATE payments SET status = 'Cancelled', gateway_status = ? WHERE id = ?");
        $update->execute([$gatewayStatus, $paymentId]);
    } else {
        $update = $pdo->prepare("UPDATE payments SET status = 'Cancelled' WHERE id = ?");
        $update->execute([$paymentId]);
    }

    $pdo->commit();
    redirect('/payments/index.php?cancelled=1');
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirect('/payments/index.php?error=' . urlencode($e->getMessage()));
}
