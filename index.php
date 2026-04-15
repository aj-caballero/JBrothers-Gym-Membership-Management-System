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

        /* ── Terms Checkbox ── */
        .terms-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 14px;
            margin-bottom: 2px;
        }
        .terms-row input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 17px;
            height: 17px;
            min-width: 17px;
            border: 1.5px solid var(--border-s);
            border-radius: 5px;
            background: var(--elevated);
            cursor: pointer;
            position: relative;
            transition: border-color 0.2s, background 0.2s;
            margin-top: 1px;
        }
        .terms-row input[type="checkbox"]:checked {
            background: var(--accent);
            border-color: var(--accent);
        }
        .terms-row input[type="checkbox"]:checked::after {
            content: '';
            position: absolute;
            left: 4px; top: 1px;
            width: 5px; height: 9px;
            border: 2px solid #0a0a0f;
            border-top: none;
            border-left: none;
            transform: rotate(45deg);
        }
        .terms-row label {
            font-size: 12.5px;
            color: var(--text-2);
            line-height: 1.55;
            cursor: pointer;
            text-transform: none;
            letter-spacing: 0;
            font-weight: 400;
        }
        .terms-row label a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }
        .terms-row label a:hover { text-decoration: underline; }

        /* ── T&C Modal ── */
        .tc-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.72);
            backdrop-filter: blur(6px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .tc-overlay.active { display: flex; }
        .tc-modal {
            background: var(--card);
            border: 1px solid var(--border-s);
            border-radius: 18px;
            width: 100%;
            max-width: 560px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 24px 60px rgba(0,0,0,0.6);
            animation: modalIn 0.25s ease;
        }
        @keyframes modalIn {
            from { opacity:0; transform: scale(0.94) translateY(12px); }
            to   { opacity:1; transform: scale(1)   translateY(0); }
        }
        .tc-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 22px 16px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .tc-modal-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 9px;
        }
        .tc-modal-header h3 i {
            color: var(--accent);
            font-size: 15px;
        }
        .tc-close {
            background: var(--elevated);
            border: 1px solid var(--border);
            color: var(--text-2);
            width: 30px; height: 30px;
            border-radius: 8px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
            transition: background 0.2s, color 0.2s;
        }
        .tc-close:hover { background: var(--border-s); color: var(--text); }
        .tc-modal-body {
            padding: 20px 22px;
            overflow-y: auto;
            flex: 1;
            font-size: 13px;
            color: var(--text-2);
            line-height: 1.75;
        }
        .tc-modal-body h4 {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: var(--accent);
            margin: 18px 0 6px;
        }
        .tc-modal-body h4:first-child { margin-top: 0; }
        .tc-modal-body p { margin-bottom: 8px; }
        .tc-modal-body ul {
            padding-left: 18px;
            margin-bottom: 8px;
        }
        .tc-modal-body ul li { margin-bottom: 4px; }
        .tc-modal-footer {
            padding: 14px 22px;
            border-top: 1px solid var(--border);
            flex-shrink: 0;
            display: flex;
            justify-content: flex-end;
        }
        .tc-accept-btn {
            padding: 9px 22px;
            background: var(--accent);
            color: #0a0a0f;
            border: none;
            border-radius: 9px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
        }
        .tc-accept-btn:hover { background: var(--accent-h); }

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

            <!-- Terms & Conditions Checkbox -->
            <div class="terms-row">
                <input type="checkbox" id="agreeTerms" name="agreeTerms">
                <label for="agreeTerms">
                    I agree to the
                    <a href="#" id="openTerms">Terms and Conditions</a>
                    of JBrothers Gym.
                </label>
            </div>

            <button type="submit" class="btn-login" id="loginBtn" disabled style="opacity:.55;cursor:not-allowed;">
                <i class="fas fa-arrow-right-to-bracket"></i>
                Sign In
            </button>
        </form>

        <div class="login-hint">
            <p><strong>Staff & Admin Portal</strong> — Access is restricted to authorized gym staff and administrators. Use your assigned credentials to log in and manage gym operations.</p>
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
                <p>Admin and staff roles with granular permissions for different gym management functions.</p>
            </div>
        </div>
    </div>
</div>

<!-- Terms & Conditions Modal -->
<div class="tc-overlay" id="tcOverlay">
    <div class="tc-modal" role="dialog" aria-modal="true" aria-labelledby="tcModalTitle">
        <div class="tc-modal-header">
            <h3 id="tcModalTitle"><i class="fas fa-file-contract"></i> Terms and Conditions</h3>
            <button class="tc-close" id="closeTerms" aria-label="Close"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="tc-modal-body">
            <p>Welcome to <strong>JBrothers Gym Membership Management System</strong>. By accessing and using this platform, you agree to be bound by the following terms and conditions. Please read them carefully.</p>

            <h4>1. Acceptance of Terms</h4>
            <p>By logging into this system, you confirm that you have read, understood, and agree to comply with these Terms and Conditions. If you do not agree, please do not access this system.</p>

            <h4>2. User Accounts &amp; Access</h4>
            <ul>
                <li>Access to this system is granted solely to authorized staff and administrators.</li>
                <li>You are responsible for maintaining the confidentiality of your login credentials.</li>
                <li>You must not share your account details with any other person.</li>
                <li>Any activity conducted under your account is your sole responsibility.</li>
            </ul>

            <h4>3. Membership Data</h4>
            <ul>
                <li>All personal information entered into the system is collected for legitimate gym management purposes.</li>
                <li>Member data including name, contact details, payment records, and attendance logs are stored securely.</li>
                <li>JBrothers Gym will not sell or share member data with third parties without explicit consent, except as required by law.</li>
            </ul>

            <h4>4. Acceptable Use</h4>
            <p>You agree not to:</p>
            <ul>
                <li>Attempt to gain unauthorized access to other accounts or system data.</li>
                <li>Use the system for any unlawful, fraudulent, or malicious purpose.</li>
                <li>Introduce malware, viruses, or any harmful code into the system.</li>
                <li>Misrepresent your identity or impersonate another user.</li>
            </ul>

            <h4>5. Payment &amp; Membership Fees</h4>
            <ul>
                <li>Membership fees are non-refundable once payment has been processed, unless otherwise agreed in writing by management.</li>
                <li>It is the member's responsibility to renew their membership before the expiration date to maintain active status.</li>
                <li>JBrothers Gym reserves the right to modify membership plans and fees with reasonable advance notice.</li>
            </ul>

            <h4>6. Staff Responsibilities</h4>
            <ul>
                <li>Staff must accurately record attendance and membership information.</li>
                <li>Staff are expected to follow all gym policies and maintain member confidentiality.</li>
                <li>JBrothers Gym holds staff accountable for the accurate management of gym data and member interactions.</li>
            </ul>

            <h4>7. Account Suspension &amp; Termination</h4>
            <p>JBrothers Gym reserves the right to suspend or terminate access to this system for any user who violates these Terms and Conditions, engages in misconduct, or poses a security risk to the system or other users.</p>

            <h4>8. Privacy Policy</h4>
            <p>Your use of this system is also governed by our Privacy Policy. All data collected is handled in accordance with applicable data protection regulations. For inquiries, please contact gym management.</p>

            <h4>9. Changes to Terms</h4>
            <p>JBrothers Gym reserves the right to update these Terms and Conditions at any time. Continued use of the system after changes are posted constitutes your acceptance of the revised terms.</p>

            <h4>10. Contact</h4>
            <p>For any questions regarding these terms, please contact the gym administration directly at the front desk or through your membership portal.</p>
        </div>
        <div class="tc-modal-footer">
            <button class="tc-accept-btn" id="acceptTerms">I Understand &amp; Accept</button>
        </div>
    </div>
</div>

<script>
(function () {
    const checkbox   = document.getElementById('agreeTerms');
    const loginBtn   = document.getElementById('loginBtn');
    const openLink   = document.getElementById('openTerms');
    const overlay    = document.getElementById('tcOverlay');
    const closeBtn   = document.getElementById('closeTerms');
    const acceptBtn  = document.getElementById('acceptTerms');

    function setBtn(state) {
        loginBtn.disabled = !state;
        loginBtn.style.opacity = state ? '1' : '.55';
        loginBtn.style.cursor  = state ? 'pointer' : 'not-allowed';
    }

    checkbox.addEventListener('change', () => setBtn(checkbox.checked));

    openLink.addEventListener('click', (e) => {
        e.preventDefault();
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    });

    function closeModal() {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    closeBtn.addEventListener('click', closeModal);

    acceptBtn.addEventListener('click', () => {
        checkbox.checked = true;
        setBtn(true);
        closeModal();
    });

    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) closeModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });
})();
</script>

</body>
</html>
