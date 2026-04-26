<?php
// payments/paymongo_webhook.php
// ============================================================
//  Optional but recommended: PayMongo Webhook Listener
//
//  Register this URL in your PayMongo dashboard:
//    https://dashboard.paymongo.com/developers → Webhooks
//    URL: http://yoursite.com/payments/paymongo_webhook.php
//    Events: checkout_session.payment.paid
//
//  Webhooks fire server-to-server even if the browser closes,
//  so they are more reliable than the return URL alone.
// ============================================================

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/paymongo.php';
require_once '../includes/functions.php';
require_once '../includes/paymongo.php';

// ----------------------------------------------------------
//  Reject non-POST requests
// ----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// ----------------------------------------------------------
//  Read raw payload
// ----------------------------------------------------------
$raw = file_get_contents('php://input');
$evt = json_decode($raw, true);

if (!is_array($evt) || !isset($evt['data']['type'])) {
    http_response_code(400);
    exit('Bad Request');
}

// ----------------------------------------------------------
//  Verify webhook signature (PayMongo signs with HMAC-SHA256)
//  Your webhook secret is shown once when you create the webhook.
//  Store it in config/paymongo.php as PAYMONGO_WEBHOOK_SECRET.
// ----------------------------------------------------------
if (defined('PAYMONGO_WEBHOOK_SECRET') && !empty(PAYMONGO_WEBHOOK_SECRET)) {
    $signatureHeader = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';
    // Format: "t=<timestamp>,te=<test_sig>,li=<live_sig>"
    $parts = [];
    foreach (explode(',', $signatureHeader) as $part) {
        [$k, $v] = explode('=', $part, 2) + [1 => ''];
        $parts[$k] = $v;
    }
    $timestamp = $parts['t'] ?? '';
    $sigKey    = (PAYMONGO_MODE === 'live') ? 'li' : 'te';
    $received  = $parts[$sigKey] ?? '';
    $computed  = hash_hmac('sha256', $timestamp . '.' . $raw, PAYMONGO_WEBHOOK_SECRET);
    if (!hash_equals($computed, $received)) {
        http_response_code(401);
        exit('Signature mismatch');
    }
}

// ----------------------------------------------------------
//  Handle events
// ----------------------------------------------------------
$eventType = $evt['data']['attributes']['type'] ?? '';
$eventData = $evt['data']['attributes']['data'] ?? [];

try {
    if ($eventType === 'checkout_session.payment.paid') {
        $sessionId    = $eventData['id'] ?? '';
        $checkoutAttr = $eventData['attributes'] ?? [];

        // Payments are nested under payment_intent.attributes.payments
        $intentAttrs = $checkoutAttr['payment_intent']['attributes'] ?? [];
        $pmPayments  = $intentAttrs['payments'] ?? [];

        if (empty($sessionId)) {
            http_response_code(200);
            exit('OK – no session id');
        }

        // Find our payment record by the gateway_transaction_id (session id)
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE gateway_transaction_id = ? AND status = 'Pending' LIMIT 1");
        $stmt->execute([$sessionId]);
        $payment = $stmt->fetch();

        if (!$payment) {
            // Already processed or not found – idempotent response
            http_response_code(200);
            exit('OK – already processed');
        }

        // The event 'checkout_session.payment.paid' only fires when the session is paid,
        // so we trust it. Optionally verify a specific payment object if present.
        $paidPayment = null;
        foreach ($pmPayments as $p) {
            if (($p['attributes']['status'] ?? '') === 'paid') {
                $paidPayment = $p;
                break;
            }
        }

        // If no payments array (some methods/versions omit it), we still proceed
        // because the event type guarantees payment was made.

        // Activate membership
        $pdo->beginTransaction();

        $planId = (int) ($payment->requested_plan_id ?? 0);
        $planStmt = $pdo->prepare("SELECT duration_days FROM membership_plans WHERE id = ?");
        $planStmt->execute([$planId]);
        $plan = $planStmt->fetch();

        if ($plan && $planId > 0) {
            $startDate = date('Y-m-d');
            $endDate   = date('Y-m-d', strtotime('+' . (int) $plan->duration_days . ' days'));

            $pdo->prepare("INSERT INTO memberships (member_id, plan_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'Active')")
                ->execute([$payment->member_id, $planId, $startDate, $endDate]);
            $membershipId = $pdo->lastInsertId();

            $pdo->prepare("UPDATE payments SET status = 'Paid', membership_id = ?, gateway_status = 'paid', gateway_payload = ? WHERE id = ?")
                ->execute([$membershipId, json_encode($checkoutAttr), $payment->id]);

            $pdo->prepare("UPDATE members SET status = 'Active' WHERE id = ?")
                ->execute([$payment->member_id]);
        } else {
            // Plan missing – just mark payment as paid without activating
            $pdo->prepare("UPDATE payments SET status = 'Paid', gateway_status = 'paid' WHERE id = ?")
                ->execute([$payment->id]);
        }

        $pdo->commit();
        http_response_code(200);
        exit('OK');
    }

    // Unhandled event type – acknowledge gracefully
    http_response_code(200);
    exit('OK – unhandled event');

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[PayMongo Webhook] ' . $e->getMessage());
    http_response_code(500);
    exit('Internal error');
}
