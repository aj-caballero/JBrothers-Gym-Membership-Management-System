<?php
// payments/paymongo_checkout.php
// ============================================================
//  Step 1 of the PayMongo flow:
//   – Receives payment_id (a pending payment already saved in DB)
//   – Creates a PayMongo Checkout Session
//   – Saves the session ID on the payment row
//   – Redirects staff/member to the PayMongo-hosted checkout page
// ============================================================

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/paymongo.php';
require_once '../includes/functions.php';
require_once '../includes/paymongo.php';
require_login();

if (!has_permission('payments')) {
    redirect('/dashboard.php?error=unauthorized');
}

$paymentId = (int) ($_GET['payment_id'] ?? 0);
if ($paymentId <= 0) {
    redirect('/payments/index.php?error=' . urlencode('Invalid payment reference.'));
}

// ----------------------------------------------------------
//  Load the pending payment + member + plan info
// ----------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT p.*, m.full_name, m.email, m.phone,
           mp.plan_name
    FROM payments p
    JOIN members m ON p.member_id = m.id
    LEFT JOIN membership_plans mp ON p.requested_plan_id = mp.id
    WHERE p.id = ? AND p.status = 'Pending'
    LIMIT 1
");
$stmt->execute([$paymentId]);
$payment = $stmt->fetch();

if (!$payment) {
    redirect('/payments/index.php?error=' . urlencode('Pending payment not found or already processed.'));
}

// ----------------------------------------------------------
//  Build return URLs  (PayMongo will append ?payment_intent_id=xxx)
// ----------------------------------------------------------
$baseReturn = APP_URL . '/payments/paymongo_return.php?payment_id=' . $paymentId;
$successUrl = $baseReturn . '&pm_status=paid';
$cancelUrl  = $baseReturn . '&pm_status=cancelled';

// ----------------------------------------------------------
//  Build the checkout session
// ----------------------------------------------------------
try {
    $paymongo = getPayMongoClient();

    $amountCentavos = PayMongoAPI::pesoToCentavos((float) $payment->amount);

    $lineItems = [[
        'name'        => 'Gym Membership – ' . ($payment->plan_name ?? 'Plan'),
        'amount'      => $amountCentavos,
        'currency'    => 'PHP',
        'quantity'    => 1,
    ]];

    $billing = [
        'name'  => $payment->full_name ?? '',
        'email' => $payment->email ?? '',
        'phone' => $payment->phone ?? '',
    ];

    $referenceNumber = 'GYM-PAY-' . str_pad($paymentId, 6, '0', STR_PAD_LEFT);

    $session = $paymongo->createCheckoutSession(
        $lineItems,
        $billing,
        $successUrl,
        $cancelUrl,
        $referenceNumber
    );

    $sessionId  = $session['data']['id'];
    $checkoutUrl = $session['data']['attributes']['checkout_url'];

    // Save the PayMongo session ID on the payment record so we can verify on return
    $pdo->prepare("
        UPDATE payments
        SET gateway = 'PayMongo',
            gateway_transaction_id = ?,
            gateway_status = 'awaiting_payment_method',
            gateway_payload = ?
        WHERE id = ?
    ")->execute([
        $sessionId,
        json_encode($session['data']['attributes']),
        $paymentId,
    ]);

    // Redirect to PayMongo's hosted checkout page
    header('Location: ' . $checkoutUrl);
    exit();

} catch (Exception $e) {
    // Clean up the pending payment so staff can retry
    $pdo->prepare("UPDATE payments SET status = 'Cancelled', gateway_status = 'checkout_creation_failed' WHERE id = ?")
        ->execute([$paymentId]);

    redirect('/payments/index.php?error=' . urlencode('PayMongo error: ' . $e->getMessage()));
}
