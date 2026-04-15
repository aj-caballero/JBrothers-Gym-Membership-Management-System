<?php
// C:/Users/Kyle/GYM MEMBERSHIP/includes/functions.php

function formatDate($dateString) {
    if (!$dateString) return '-';
    return date('M d, Y', strtotime($dateString));
}

function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

function getGymSettings($pdo) {
    $stmt = $pdo->query("SELECT * FROM gym_settings LIMIT 1");
    return $stmt->fetch();
}

function getActiveMembersCount($pdo) {
    $stmt = $pdo->query("SELECT COUNT(id) as count FROM members WHERE status = 'Active' AND deleted_at IS NULL");
    return $stmt->fetch()->count;
}

function getTotalRevenue($pdo) {
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM payments WHERE status = 'Paid'");
    return $stmt->fetch()->total ?? 0;
}

/**
 * Generate the next unique membership ID: GYM-YYYY-XXXXX
 */
function generateMembershipId($pdo) {
    $year = date('Y');
    // Find the highest existing numeric suffix for this year
    $stmt = $pdo->prepare("SELECT membership_id FROM members WHERE membership_id LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute(["GYM-$year-%"]);
    $last = $stmt->fetchColumn();
    if ($last) {
        $parts = explode('-', $last);
        $seq = (int)end($parts) + 1;
    } else {
        // Fall back to counting all members + 1 to avoid collisions after year roll-over
        $count = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
        $seq = (int)$count + 1;
    }
    return 'GYM-' . $year . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
}

/**
 * Return the public URL for a member's photo, or a default avatar URL.
 */
function getMemberPhotoUrl($photo_path) {
    if ($photo_path && file_exists(__DIR__ . '/../' . $photo_path)) {
        return APP_URL . '/' . ltrim($photo_path, '/');
    }
    return null; // caller renders initials avatar
}

/**
 * Check whether a table column exists (cached for current request).
 */
function dbHasColumn($pdo, $table, $column) {
    static $cache = [];
    $key = strtolower($table . '.' . $column);
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $sql = "SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$table, $column]);
        $cache[$key] = ((int)$stmt->fetchColumn()) > 0;
    } catch (Exception $e) {
        $cache[$key] = false;
    }

    return $cache[$key];
}

/**
 * Detect whether payments.payment_method enum already supports MangoPay.
 */
function paymentsSupportsMangoPay($pdo) {
    static $result = null;
    if ($result !== null) {
        return $result;
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM payments LIKE 'payment_method'");
        $col = $stmt->fetch();
        $type = strtolower((string)($col->Type ?? ''));
        $result = strpos($type, "'mangopay'") !== false;
    } catch (Exception $e) {
        $result = false;
    }

    return $result;
}

/**
 * Simulate a MangoPay card payment for local testing/training.
 */
function simulateMangoPayPayment($amount, $memberName, $memberEmail, $forcedOutcome = 'succeeded') {
    $forcedOutcome = strtolower(trim((string) $forcedOutcome));
    $allowedOutcomes = ['succeeded', 'pending', 'failed'];
    if (!in_array($forcedOutcome, $allowedOutcomes, true)) {
        $forcedOutcome = 'succeeded';
    }

    $txnId = 'MGP-SIM-' . date('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $walletId = 'WLT-SIM-' . strtoupper(substr(sha1((string)$memberEmail), 0, 10));

    $statusMap = [
        'succeeded' => 'SUCCEEDED',
        'pending'   => 'PENDING',
        'failed'    => 'FAILED',
    ];

    $messages = [
        'SUCCEEDED' => 'MangoPay simulation approved.',
        'PENDING'   => 'MangoPay simulation is pending confirmation.',
        'FAILED'    => 'MangoPay simulation declined this payment.',
    ];

    $gatewayStatus = $statusMap[$forcedOutcome];

    return [
        'success' => $gatewayStatus === 'SUCCEEDED',
        'gateway' => 'MangoPay',
        'gateway_status' => $gatewayStatus,
        'transaction_id' => $txnId,
        'wallet_id' => $walletId,
        'amount' => (float) $amount,
        'currency' => 'PHP',
        'member' => [
            'name' => (string) $memberName,
            'email' => (string) $memberEmail,
        ],
        'processed_at' => date('Y-m-d H:i:s'),
        'message' => $messages[$gatewayStatus],
    ];
}
?>
