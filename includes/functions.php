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
?>
