<?php
// attendance/scan.php — AJAX endpoint for QR attendance scanning
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$membership_id = trim($_POST['membership_id'] ?? '');
if (empty($membership_id)) {
    echo json_encode(['success' => false, 'message' => 'No QR data received.']);
    exit;
}

// Look up member by membership_id
$stmt = $pdo->prepare("SELECT id, full_name, status, deleted_at FROM members WHERE membership_id = ?");
$stmt->execute([$membership_id]);
$member = $stmt->fetch();

if (!$member) {
    echo json_encode(['success' => false, 'message' => 'Unrecognised QR code. Member not found.']);
    exit;
}
if ($member->deleted_at !== null) {
    echo json_encode(['success' => false, 'message' => 'This membership has been archived.']);
    exit;
}
if ($member->status !== 'Active') {
    echo json_encode(['success' => false, 'message' => "Access Denied: Membership is {$member->status}."]);
    exit;
}

$today      = date('Y-m-d');
$member_id  = $member->id;
$name       = $member->full_name;

// Check for open session today
$checkStmt = $pdo->prepare("SELECT id FROM attendance_logs WHERE member_id = ? AND DATE(time_in) = ? AND time_out IS NULL");
$checkStmt->execute([$member_id, $today]);
$openLog = $checkStmt->fetch();

if ($openLog) {
    // Check out
    $pdo->prepare("UPDATE attendance_logs SET time_out = NOW() WHERE id = ?")->execute([$openLog->id]);
    echo json_encode([
        'success' => true,
        'action'  => 'out',
        'name'    => $name,
        'time'    => date('h:i A'),
        'message' => "$name checked OUT successfully."
    ]);
} else {
    // Check in
    $pdo->prepare("INSERT INTO attendance_logs (member_id, time_in) VALUES (?, NOW())")->execute([$member_id]);
    echo json_encode([
        'success' => true,
        'action'  => 'in',
        'name'    => $name,
        'time'    => date('h:i A'),
        'message' => "$name checked IN successfully."
    ]);
}
?>
