<?php
require_once 'config/config.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    redirect('/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Iron Forge Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            margin: 0;
            font-family: 'Inter', sans-serif;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
            color: #fff;
        }
        .login-container h1 {
            margin-top: 0;
            font-weight: 700;
            color: #00ffcc;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #ccc;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: #00ffcc;
        }
        .btn-primary {
            width: 100%;
            padding: 12px;
            background: #00ffcc;
            color: #1a1a2e;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-primary:hover {
            background: #00e6b8;
        }
        .alert {
            padding: 10px;
            background: rgba(255, 0, 0, 0.2);
            border: 1px solid rgba(255, 0, 0, 0.5);
            color: #ffcccc;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h1>Iron Forge Gym</h1>
    <p>Sign in to access your dashboard</p>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert">
            <?php 
                if ($_GET['error'] == 'invalid') echo "Invalid email or password.";
                if ($_GET['error'] == 'unauthorized') echo "You must log in to continue.";
            ?>
        </div>
    <?php endif; ?>

    <form action="auth/login.php" method="POST">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn-primary">Login</button>
    </form>
</div>

</body>
</html>
