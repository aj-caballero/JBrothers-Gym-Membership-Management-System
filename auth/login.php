<?php
require_once '../config/config.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        redirect('/index.php?error=invalid');
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user->password)) {
        // Log success
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $logStmt = $pdo->prepare("INSERT INTO login_logs (user_id, email_attempt, ip_address, user_agent, status) VALUES (?, ?, ?, ?, 'Success')");
        $logStmt->execute([$user->id, $email, $ip, $ua]);

        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_name'] = $user->full_name;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_permissions'] = ($user->role === 'admin') ? ['all'] : explode(',', $user->permissions ?? '');

        // Redirect to dashboard
        redirect('/dashboard.php');
    } else {
        // Log failure
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $userId = $user ? $user->id : null;
        $logStmt = $pdo->prepare("INSERT INTO login_logs (user_id, email_attempt, ip_address, user_agent, status) VALUES (?, ?, ?, ?, 'Failed')");
        $logStmt->execute([$userId, $email, $ip, $ua]);

        // Invalid credentials
        redirect('/index.php?error=invalid');
    }
} else {
    redirect('/index.php');
}
?>
