<?php
require_once 'config/config.php';
if (isset($_SESSION['user_id'])) {
    redirect('/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — JBrothers Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --accent:     #22c55e;
            --accent-h:   #16a34a;
            --accent-s:   rgba(34,197,94,0.15);
            --accent-r:   rgba(34,197,94,0.25);
            --bg:         #0a0a0f;
            --surface:    #111118;
            --card:       #16161f;
            --elevated:   #1c1c27;
            --border:     rgba(255,255,255,0.07);
            --border-s:   rgba(255,255,255,0.13);
            --text:       #f1f1f3;
            --text-2:     #9292a4;
            --text-m:     #55556a;
            --danger:     #ef4444;
            --danger-bg:  rgba(239,68,68,0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: stretch;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Left Panel ── */
        .login-left {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px 40px;
            background: var(--surface);
            border-right: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: -120px; left: -120px;
            width: 500px; height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(34,197,94,0.08) 0%, transparent 70%);
            pointer-events: none;
        }
        .login-left::after {
            content: '';
            position: absolute;
            bottom: -80px; right: -80px;
            width: 340px; height: 340px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(34,197,94,0.05) 0%, transparent 70%);
            pointer-events: none;
        }

        .login-brand {
            position: relative;
            text-align: center;
            margin-bottom: 52px;
        }
        .login-brand-icon {
            width: 58px; height: 58px;
            background: var(--accent);
            border-radius: 16px;
            display: inline-flex;
            align-items: center; justify-content: center;
            font-size: 26px;
            color: #0a0a0f;
            margin-bottom: 18px;
            box-shadow: 0 0 30px rgba(34,197,94,0.3);
        }
        .login-brand h1 {
            font-size: 26px;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -0.6px;
            margin-bottom: 6px;
        }
        .login-brand p {
            font-size: 14px;
            color: var(--text-2);
            font-weight: 400;
        }

        /* ── Form Card ── */
        .login-card {
            position: relative;
            width: 100%;
            max-width: 380px;
        }

        .login-card h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.3px;
            margin-bottom: 4px;
        }
        .login-card .login-sub {
            font-size: 13.5px;
            color: var(--text-2);
            margin-bottom: 28px;
        }

        .form-group { margin-bottom: 16px; }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--text-2);
            margin-bottom: 7px;
        }

        .input-wrap {
            position: relative;
        }
        .input-wrap i {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-m);
            font-size: 14px;
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            padding: 11px 14px 11px 38px;
            background: var(--elevated);
            border: 1px solid var(--border-s);
            border-radius: 10px;
            color: var(--text);
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control::placeholder { color: var(--text-m); }
        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-r);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            background: var(--accent);
            color: #0a0a0f;
            border: none;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 2px 12px rgba(34,197,94,0.25);
        }
        .btn-login:hover {
            background: var(--accent-h);
            box-shadow: 0 4px 20px rgba(34,197,94,0.35);
            transform: translateY(-1px);
        }
        .btn-login:active { transform: translateY(0); box-shadow: none; }

        .login-hint {
            margin-top: 24px;
            padding: 14px 16px;
            background: var(--elevated);
            border: 1px solid var(--border);
            border-radius: 10px;
        }
        .login-hint p {
            font-size: 12px;
            color: var(--text-m);
            line-height: 1.7;
        }
        .login-hint strong { color: var(--text-2); }

        .alert {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 11px 14px;
            background: var(--danger-bg);
            border: 1px solid rgba(239,68,68,0.2);
            border-radius: 10px;
            color: var(--danger);
            font-size: 13.5px;
            font-weight: 500;
            margin-bottom: 18px;
        }

        .login-footer {
            position: absolute;
            bottom: 24px;
            font-size: 12px;
            color: var(--text-m);
            text-align: center;
        }

        /* ── Right Panel (decorative, hidden on small screens) ── */
        .login-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px;
            background: var(--bg);
            gap: 32px;
        }

        .login-right .feature-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-width: 340px;
            width: 100%;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }
        .feature-icon {
            width: 38px; height: 38px;
            border-radius: 10px;
            background: var(--accent-s);
            color: var(--accent);
            display: flex; align-items: center; justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }
        .feature-text h4 {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 2px;
        }
        .feature-text p {
            font-size: 12.5px;
            color: var(--text-2);
            line-height: 1.5;
        }

        .login-right-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.4px;
            text-align: center;
            max-width: 300px;
            line-height: 1.3;
        }
        .login-right-title span { color: var(--accent); }

        @media (max-width: 768px) {
            .login-right { display: none; }
            .login-left  { border-right: none; }
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 99px; }
    </style>
</head>
<body>

<!-- Left: Login Form -->
<div class="login-left">
    <div class="login-brand">
        <div class="login-brand-icon"><i class="fas fa-dumbbell"></i></div>
        <h1>JBrothers Gym</h1>
        <p>Membership Management System</p>
    </div>

    <div class="login-card">
        <h2>Welcome back</h2>
        <p class="login-sub">Sign in to access your portal</p>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert">
                <i class="fas fa-circle-exclamation"></i>
                <?php
                    if ($_GET['error'] === 'invalid')      echo 'Invalid email or password. Please try again.';
                    elseif ($_GET['error'] === 'unauthorized') echo 'Please sign in to continue.';
                    else echo 'An error occurred. Please try again.';
                ?>
            </div>
        <?php endif; ?>

        <form action="auth/login.php" method="POST" novalidate>
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="you@example.com" required autofocus
                           value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-arrow-right-to-bracket"></i>
                Sign In
            </button>
        </form>

        <div class="login-hint">
            <p><strong>Staff & Admin</strong> — use your work email and assigned password.<br>
               <strong>Members</strong> — use your registered email. Default password is <strong>password</strong>.</p>
        </div>
    </div>

    <div class="login-footer">
        &copy; <?= date('Y') ?> JBrothers Gym. All rights reserved.
    </div>
</div>

<!-- Right: Feature highlights -->
<div class="login-right">
    <p class="login-right-title">Everything you need to run your gym <span>efficiently</span></p>

    <div class="feature-list">
        <div class="feature-item">
            <div class="feature-icon"><i class="fas fa-users"></i></div>
            <div class="feature-text">
                <h4>Member Management</h4>
                <p>Track member profiles, status, and full membership history in one view.</p>
            </div>
        </div>
        <div class="feature-item">
            <div class="feature-icon"><i class="fas fa-clock"></i></div>
            <div class="feature-text">
                <h4>Real-time Attendance</h4>
                <p>Log check-ins and check-outs instantly. Monitor who's in the gym right now.</p>
            </div>
        </div>
        <div class="feature-item">
            <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
            <div class="feature-text">
                <h4>Revenue Analytics</h4>
                <p>Visual revenue breakdowns and member growth trends over the past 6 months.</p>
            </div>
        </div>
        <div class="feature-item">
            <div class="feature-icon"><i class="fas fa-shield-halved"></i></div>
            <div class="feature-text">
                <h4>Role-Based Access</h4>
                <p>Separate portals for admins, staff, and members with granular permissions.</p>
            </div>
        </div>
    </div>
</div>

</body>
</html>
