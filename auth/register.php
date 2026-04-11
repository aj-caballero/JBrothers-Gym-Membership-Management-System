<?php
require_once '../config/config.php';
require_once '../config/database.php';

require_admin(); // Only admins can register new users (staff/admin)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'staff';

    // Basic validation
    if (empty($full_name) || empty($email) || empty($password)) {
        redirect('/admin/users.php?error=empty');
    }

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        redirect('/admin/users.php?error=exists');
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$full_name, $email, $phone, $hashedPassword, $role])) {
        redirect('/admin/users.php?success=added');
    } else {
        redirect('/admin/users.php?error=failed');
    }
} else {
    redirect('/admin/users.php');
}
?>
