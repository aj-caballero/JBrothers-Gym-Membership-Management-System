<?php
// plans/restore.php — Restore archived plan
require_once '../config/config.php';
require_once '../config/database.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare("UPDATE membership_plans SET deleted_at = NULL WHERE id = ?");
    $stmt->execute([$id]);
}

redirect('/plans/archived.php?success=restored');
?>
