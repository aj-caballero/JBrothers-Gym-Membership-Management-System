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
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_name'] = $user->full_name;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['user_email'] = $user->email;

        // Redirect to dashboard
        redirect('/dashboard.php');
    } else {
        // Invalid credentials
        redirect('/index.php?error=invalid');
    }
} else {
    redirect('/index.php');
}
?>
