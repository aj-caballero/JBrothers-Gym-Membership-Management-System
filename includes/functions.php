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
    $stmt = $pdo->query("SELECT COUNT(id) as count FROM members WHERE status = 'Active'");
    return $stmt->fetch()->count;
}

function getTotalRevenue($pdo) {
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM payments WHERE status = 'Paid'");
    return $stmt->fetch()->total ?? 0;
}
?>
