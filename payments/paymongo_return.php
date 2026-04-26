<?php
// payments/paymongo_return.php
// ============================================================
//  Step 2 of the PayMongo flow:
//   – PayMongo redirects here after checkout (success or cancel)
//   – We re-verify the payment with the PayMongo API (never trust URL params alone)
//   – Update the payment record + activate membership on success
// ============================================================

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/paymongo.php';
require_once '../includes/functions.php';
require_once '../includes/paymongo.php';
require_login();

$paymentId = (int) ($_GET['payment_id'] ?? 0);
$pmStatus  = trim((string) ($_GET['pm_status'] ?? ''));   // 'paid' or 'cancelled'

if ($paymentId <= 0) {
    redirect('/payments/index.php?error=' . urlencode('Invalid return reference.'));
}

// ----------------------------------------------------------
//  Load the pending payment row
// ----------------------------------------------------------
$stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ? LIMIT 1");
$stmt->execute([$paymentId]);
$payment = $stmt->fetch();

if (!$payment) {
    redirect('/payments/index.php?error=' . urlencode('Payment record not found.'));
}

// Already finalised (e.g. user hit back after success page)
if ($payment->status !== 'Pending') {
    redirect('/payments/receipt.php?id=' . $paymentId);
}

$sessionId = $payment->gateway_transaction_id ?? '';

// ----------------------------------------------------------
//  Re-verify with PayMongo API  (do NOT trust URL params alone)
// ----------------------------------------------------------
$verifiedStatus = $pmStatus; // fallback – will be overridden below

try {
    $paymongo = getPayMongoClient();
    $session  = $paymongo->getCheckoutSession($sessionId);

    $attrs = $session['data']['attributes'];
    // PayMongo statuses: 'active','expired','completed'
    // payments[0].status: 'paid','failed','pending'
    $checkoutStatus = $attrs['status'] ?? 'active';
    $payments       = $attrs['payments'] ?? [];

    if ($checkoutStatus === 'completed' && !empty($payments)) {
        $latestPayment  = $payments[0];
        $verifiedStatus = $latestPayment['attributes']['status'] ?? 'failed'; // 'paid' or 'failed'
    } elseif ($checkoutStatus === 'expired') {
        $verifiedStatus = 'failed';
    } else {
        // Still active (user may have closed window without paying)
        $verifiedStatus = ($pmStatus === 'cancelled') ? 'cancelled' : 'awaiting_payment_method';
    }

    // Update gateway payload with the fresh data
    $pdo->prepare("UPDATE payments SET gateway_payload = ? WHERE id = ?")
        ->execute([json_encode($attrs), $paymentId]);

} catch (Exception $e) {
    // API verification failed – log and fall back to URL param
    error_log('[PayMongo] Verification failed for payment #' . $paymentId . ': ' . $e->getMessage());
    // Keep $verifiedStatus = $pmStatus (the URL param) as a best-effort fallback
}

// ----------------------------------------------------------
//  Act on verified status
// ----------------------------------------------------------
try {
    $pdo->beginTransaction();

    if ($verifiedStatus === 'paid') {
        // ── Payment succeeded → create membership + activate member ──
        $planId = (int) ($payment->requested_plan_id ?? 0);
        if ($planId <= 0) {
            throw new \RuntimeException('No membership plan linked to this payment.');
        }

        $planStmt = $pdo->prepare("SELECT duration_days FROM membership_plans WHERE id = ?");
        $planStmt->execute([$planId]);
        $plan = $planStmt->fetch();
        if (!$plan) {
            throw new \RuntimeException('Membership plan no longer exists.');
        }

        $startDate = date('Y-m-d');
        $endDate   = date('Y-m-d', strtotime('+' . (int) $plan->duration_days . ' days'));

        $msStmt = $pdo->prepare("
            INSERT INTO memberships (member_id, plan_id, start_date, end_date, status)
            VALUES (?, ?, ?, ?, 'Active')
        ");
        $msStmt->execute([$payment->member_id, $planId, $startDate, $endDate]);
        $membershipId = $pdo->lastInsertId();

        $pdo->prepare("
            UPDATE payments
            SET status = 'Paid',
                membership_id = ?,
                gateway_status = 'paid'
            WHERE id = ?
        ")->execute([$membershipId, $paymentId]);

        $pdo->prepare("UPDATE members SET status = 'Active' WHERE id = ?")
            ->execute([$payment->member_id]);

        $pdo->commit();
        redirect('/payments/receipt.php?id=' . $paymentId);

    } elseif ($verifiedStatus === 'cancelled' || $verifiedStatus === 'failed') {
        // ── Payment cancelled or failed → mark payment cancelled ──
        $pdo->prepare("
            UPDATE payments
            SET status = 'Cancelled',
                gateway_status = ?
            WHERE id = ?
        ")->execute([$verifiedStatus, $paymentId]);

        $pdo->commit();

        $msg = $verifiedStatus === 'cancelled'
            ? 'Payment was cancelled. No charge was made.'
            : 'Payment failed or was declined. Please try again.';
        redirect('/payments/index.php?error=' . urlencode($msg));

    } else {
        // Still pending / awaiting – just leave as-is and redirect staff to payments list
        $pdo->rollBack();
        redirect('/payments/index.php?info=' . urlencode('Payment is still pending confirmation from PayMongo.'));
    }

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirect('/payments/index.php?error=' . urlencode('Return handler error: ' . $e->getMessage()));
}
