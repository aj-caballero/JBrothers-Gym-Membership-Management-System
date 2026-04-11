<?php
// C:/Users/Kyle/GYM MEMBERSHIP/plans/delete.php
require_once '../config/config.php';
require_once '../config/database.php';

require_admin();

$id = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("DELETE FROM membership_plans WHERE id = ?");
    $stmt->execute([$id]);
} catch (PDOException $e) {
    // If plan is tied to existing memberships, we might get a foreign key constraint error.
    // In a real app we might soft-delete or disable the plan instead.
}

redirect('/plans/index.php');
?>
