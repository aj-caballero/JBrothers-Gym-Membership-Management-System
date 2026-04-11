<?php
// C:/Users/Kyle/GYM MEMBERSHIP/members/delete.php
require_once '../config/config.php';
require_once '../config/database.php';

require_admin(); // Security check

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");
$stmt->execute([$id]);

redirect('/members/index.php');
?>
