<?php
// members/restore.php — Restore archived member
require_once '../config/config.php';
require_once '../config/database.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare("UPDATE members SET deleted_at = NULL WHERE id = ?");
    $stmt->execute([$id]);
}

redirect('/members/archived.php?success=restored');
?>
