<?php
// attendance/scan.php — AJAX endpoint for QR attendance scanning
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_login();

header('Content-Type: application/json');

function jsonResponse($success, $message, $extra = []) {
    echo json_encode(array_merge([
        'success' => (bool) $success,
        'message' => $message,
    ], $extra));
    exit;
}

function parseMembershipCandidates($raw) {
    $raw = (string) $raw;
    // Remove control characters that can come from camera decoders.
    $raw = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $raw);
    $raw = trim($raw);
    if ($raw === '') {
        return [];
    }

    $candidates = [$raw];

    // Support QR content that is a URL with query params (e.g. ?membership_id=...)
    if (filter_var($raw, FILTER_VALIDATE_URL)) {
        $query = parse_url($raw, PHP_URL_QUERY);
        if ($query) {
            parse_str($query, $params);
            foreach (['membership_id', 'member_id', 'id', 'mid'] as $key) {
                if (!empty($params[$key])) {
                    $candidates[] = trim((string) $params[$key]);
                }
            }
        }
    }

    // Support QR content in JSON format
    $json = json_decode($raw, true);
    if (is_array($json)) {
        foreach (['membership_id', 'member_id', 'id', 'mid'] as $key) {
            if (!empty($json[$key])) {
                $candidates[] = trim((string) $json[$key]);
            }
        }
    }

    // Support prefixed formats like MEMBER:GYM-2026-00001
    if (preg_match('/^(?:member(?:ship)?(?:_id)?|id)\s*[:=-]\s*(.+)$/i', $raw, $m)) {
        $candidates[] = trim($m[1]);
    }

    // Extract canonical membership pattern when embedded in larger text.
    if (preg_match('/(GYM-\d{4}-\d{3,})/i', $raw, $m)) {
        $candidates[] = strtoupper(trim($m[1]));
    }

    // Normalize and deduplicate candidates
    $normalized = [];
    foreach ($candidates as $candidate) {
        $candidate = trim((string) $candidate, " \t\n\r\0\x0B\"'");
        if ($candidate !== '') {
            $normalized[] = $candidate;
        }
    }

    return array_values(array_unique($normalized));
}

function fetchTodayRecentLogs($pdo) {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare(
        "SELECT a.time_in, a.time_out, m.full_name, m.photo_path
         FROM attendance_logs a
         JOIN members m ON a.member_id = m.id
         WHERE DATE(a.time_in) = ?
         ORDER BY a.time_in DESC
         LIMIT 10"
    );
    $stmt->execute([$today]);

    $rows = [];
    foreach ($stmt->fetchAll() as $log) {
        $rows[] = [
            'name'      => $log->full_name,
            'time_in'   => date('h:i A', strtotime($log->time_in)),
            'time_out'  => $log->time_out ? date('h:i A', strtotime($log->time_out)) : null,
            'photo_url' => getMemberPhotoUrl($log->photo_path),
        ];
    }

    return $rows;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'today_logs') {
    jsonResponse(true, 'Latest logs fetched.', [
        'recent_logs' => fetchTodayRecentLogs($pdo),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request.');
}

$rawQr = trim($_POST['membership_id'] ?? $_POST['qr_data'] ?? '');
if ($rawQr === '') {
    jsonResponse(false, 'No QR data received.');
}

$candidates = parseMembershipCandidates($rawQr);
$member = null;

foreach ($candidates as $candidate) {
    $isNumericId = ctype_digit($candidate);
    $stmt = $pdo->prepare(
        "SELECT id, full_name, status, deleted_at
         FROM members
         WHERE membership_id = ? OR (? = 1 AND id = ?)
         LIMIT 1"
    );
    $stmt->execute([$candidate, $isNumericId ? 1 : 0, $isNumericId ? (int) $candidate : 0]);
    $member = $stmt->fetch();
    if ($member) {
        break;
    }
}

if (!$member) {
    jsonResponse(false, 'Unrecognised QR code. Member not found.');
}
if ($member->deleted_at !== null) {
    jsonResponse(false, 'This membership has been archived.');
}
if ($member->status !== 'Active') {
    jsonResponse(false, "Access Denied: Membership is {$member->status}.");
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
    $recentLogs = fetchTodayRecentLogs($pdo);
    jsonResponse(true,
        "$name checked OUT successfully.", [
        'action'  => 'out',
        'name'    => $name,
        'time'    => date('h:i A'),
        'recent_logs' => $recentLogs,
    ]);
} else {
    // Check in
    $pdo->prepare("INSERT INTO attendance_logs (member_id, time_in) VALUES (?, NOW())")->execute([$member_id]);
    $recentLogs = fetchTodayRecentLogs($pdo);
    jsonResponse(true,
        "$name checked IN successfully.", [
        'action'  => 'in',
        'name'    => $name,
        'time'    => date('h:i A'),
        'recent_logs' => $recentLogs,
    ]);
}
?>
