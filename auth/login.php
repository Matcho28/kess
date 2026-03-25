<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    redirectAfterLogin();
}

$errorMessage = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $errorMessage = 'Email and password are required.';
    } else {
        $sql = 'SELECT u.id, u.department_id, u.full_name, u.email, u.password_hash, u.role, d.name AS department_name
                FROM users u
                INNER JOIN departments d ON d.id = u.department_id
                WHERE u.email = ?
                  AND u.is_active = 1
                  AND u.role IN (?, ?)
                LIMIT 1';

        $statement = db()->prepare($sql);
        $statement->execute([$email, ROLE_SUPER_ADMIN, ROLE_DEPARTMENT_ADMIN]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            $errorMessage = 'Invalid credentials. Please try again.';
        } else {
            loginUser($user);
            redirectAfterLogin();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login - KISS-CO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?= e(baseUrl('/assets/css/main.css')) ?>" rel="stylesheet">
    <link href="<?= e(baseUrl('/assets/css/saas2026.css')) ?>" rel="stylesheet">
    <link href="<?= e(baseUrl('/assets/css/darkmode.css')) ?>" rel="stylesheet">
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            --glass-bg: rgba(255, 255, 255, 0.07);
            --glass-border: rgba(255, 255, 255, 0.12);
            --glass-shadow: 0 24px 48px -12px rgba(0, 0, 0, 0.4);
            --input-bg: rgba(255, 255, 255, 0.08);
            --input-border: rgba(255, 255, 255, 0.16);
            --input-focus-bg: rgba(255, 255, 255, 0.12);
            --input-focus-border: rgba(99, 102, 241, 0.6);
            --primary-500: #6366f1;
            --primary-600: #4f46e5;
            --danger-600: #dc2626;
            --text-primary: #f8fafc;
            --text-muted: #cbd5e1;
            --text-dim: #94a3b8;
        }

        body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
            background: url('<?= e(baseUrl('/pictures/69707a87edafa_pio2.png')) ?>') no-repeat center center fixed;
            background-size: cover;
            color: var(--text-primary);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            overflow: hidden;
        }

        .login-container {
            max-width: 420px;
            padding: 1rem;
            perspective: 1200px;
        }

        .login-card {
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            background: var(--glass-bg);
            backdrop-filter: blur(24px);
            box-shadow: var(--glass-shadow);
            padding: 0;
            position: relative;
            width: 100%;
            min-height: 500px;
        }

        .card-face {
            padding: 2rem;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 24px;
            background: var(--glass-bg);
            backdrop-filter: blur(24px);
            box-shadow: var(--glass-shadow);
            border: 1px solid var(--glass-border);
            box-sizing: border-box;
        }

        .card-face-front {
            z-index: 2;
        }

        .card-face-back {
            z-index: 1;
            opacity: 0;
            -webkit-transition: opacity 0.5s ease;
            transition: opacity 0.5s ease;
            pointer-events: none;
        }

        .login-card.flipped .card-face-front {
            opacity: 0;
            -webkit-transition: opacity 0.5s ease;
            transition: opacity 0.5s ease;
            pointer-events: none;
        }

        .login-card.flipped .card-face-back {
            opacity: 1;
            pointer-events: auto;
        }

        .welcome-content {
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 320px;
        }

        .welcome-icon {
            width: 80px;
            height: 80px;
            border-radius: 24px;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 16px 32px -12px rgba(99, 102, 241, 0.6);
            animation: float 3s ease-in-out infinite;
        }

        .welcome-icon i {
            font-size: 2.2rem;
            color: #ffffff;
        }

        .welcome-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .welcome-subtitle {
            font-size: 0.96rem;
            color: var(--text-muted);
            font-weight: 500;
            margin-bottom: 2rem;
            line-height: 1.5;
        }

        .btn-continue {
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            border: none;
            border-radius: 16px;
            padding: 0.9rem 2rem;
            font-size: 0.96rem;
            font-weight: 700;
            color: #ffffff;
            cursor: pointer;
            transition: all 0.22s ease;
            box-shadow: 0 12px 24px -10px rgba(99, 102, 241, 0.5);
        }

        .btn-continue:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 32px -14px rgba(99, 102, 241, 0.6);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .login-icon {
            width: 56px;
            height: 56px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 12px 24px -10px rgba(99, 102, 241, 0.5);
        }

        .login-icon i {
            font-size: 1.6rem;
            color: #ffffff;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            letter-spacing: -0.02em;
        }

        .login-subtitle {
            font-size: 0.92rem;
            color: var(--text-muted);
            font-weight: 500;
            margin: 0;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .form-control {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 16px;
            padding: 0.85rem 1.1rem;
            font-size: 0.96rem;
            color: var(--text-primary);
            transition: all 0.22s ease;
        }

        .form-control::placeholder {
            color: var(--text-dim);
        }

        .form-control:focus {
            border-color: var(--input-focus-border);
            background: var(--input-focus-bg);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
            outline: none;
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            border: none;
            border-radius: 16px;
            padding: 0.9rem;
            font-size: 0.96rem;
            font-weight: 700;
            color: #ffffff;
            cursor: pointer;
            transition: all 0.22s ease;
            box-shadow: 0 12px 24px -10px rgba(99, 102, 241, 0.5);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 32px -14px rgba(99, 102, 241, 0.6);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .btn-login .btn-text {
            display: inline-block;
            transition: opacity 0.3s ease;
        }

        .btn-login.loading .btn-text {
            opacity: 0;
        }

        .btn-login .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto;
        }

        .btn-login.loading .spinner {
            display: block;
        }

        .login-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.4s ease, visibility 0.4s ease;
        }

        .login-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .loading-modal {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2.5rem;
            text-align: center;
            box-shadow: var(--glass-shadow);
            max-width: 320px;
            width: 90%;
            transform: scale(0.9) translateY(20px);
            opacity: 0;
            transition: transform 0.5s cubic-bezier(0.4, 0.0, 0.2, 1), opacity 0.5s ease;
        }

        .login-overlay.active .loading-modal {
            transform: scale(1) translateY(0);
            opacity: 1;
        }

        .loading-icon {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 16px 32px -12px rgba(99, 102, 241, 0.6);
            animation: pulse 2s ease-in-out infinite;
        }

        .loading-icon i {
            font-size: 1.8rem;
            color: #ffffff;
            animation: rotate 2s linear infinite;
        }

        .loading-title {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .loading-message {
            font-size: 0.88rem;
            color: var(--text-muted);
            font-weight: 500;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .loading-progress {
            display: flex;
            gap: 0.4rem;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .progress-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary-500);
            opacity: 0.3;
            animation: progressPulse 1.5s ease-in-out infinite;
        }

        .progress-dot:nth-child(1) { animation-delay: 0s; }
        .progress-dot:nth-child(2) { animation-delay: 0.2s; }
        .progress-dot:nth-child(3) { animation-delay: 0.4s; }

        .loading-status {
            font-size: 0.78rem;
            color: var(--text-dim);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes progressPulse {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.2); }
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 16px;
            padding: 0.85rem 1.1rem;
            font-size: 0.88rem;
            color: var(--danger-600);
            margin-bottom: 1.5rem;
        }

        .divider {
            border: none;
            height: 1px;
            background: var(--glass-border);
            margin: 2rem 0;
        }

        .test-users {
            text-align: center;
            margin-top: 1.5rem;
        }

        .test-users-title {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.75rem;
        }

        .test-user-item {
            font-size: 0.82rem;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 0.25rem;
        }

        .test-user-item i {
            width: 16px;
            margin-right: 0.45rem;
            color: var(--text-dim);
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 0.75rem;
            }
            .login-card {
                padding: 1.5rem;
                border-radius: 20px;
            }
        }
    </style>
</head>
<body class="d-flex align-items-center py-4">
<!-- Loading Overlay -->
<div class="login-overlay" id="loadingOverlay">
    <div class="loading-modal">
        <div class="loading-icon">
            <i class="fas fa-cog"></i>
        </div>
        <h2 class="loading-title">Initializing System</h2>
        <p class="loading-message">Verifying credentials and preparing your workspace...</p>
        <div class="loading-progress">
            <div class="progress-dot"></div>
            <div class="progress-dot"></div>
            <div class="progress-dot"></div>
        </div>
        <div class="loading-status" id="loadingStatus">Authenticating...</div>
    </div>
</div>

<main class="container login-container">
    <div class="login-card" id="loginCard">
        <!-- Welcome Side (Front) -->
        <div class="card-face card-face-front">
            <div class="welcome-content">
                <div class="welcome-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h1 class="welcome-title">Welcome to KISS-CO</h1>
                <p class="welcome-subtitle">Kapitolyo Information Sharing System - Centralized Operations</p>
                <button class="btn-continue" onclick="flipCard()">Continue to Login</button>
            </div>
        </div>
        
        <!-- Login Form Side (Back) -->
        <div class="card-face card-face-back">
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h1 class="login-title">KISS-CO</h1>
                <p class="login-subtitle">Kapitolyo Information Sharing System - Centralized Operations</p>
            </div>

            <?php if ($errorMessage !== ''): ?>
                <div class="alert-danger" role="alert">
                    <?= e($errorMessage) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(baseUrl('/auth/login.php')) ?>" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" placeholder="you@example.org" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                </div>
                <button type="submit" class="btn-login" id="loginBtn">
    <span class="btn-text">Sign In</span>
    <div class="spinner"></div>
</button>
            </form>

            <div class="divider"></div>

            <div class="test-users">
                <div class="test-users-title">Test Users</div>
                <div class="test-user-item">
                    <i class="fas fa-user-shield"></i>
                    super.admin@org.local / Admin@123
                </div>
                <div class="test-user-item">
                    <i class="fas fa-user-tie"></i>
                    admin.a@org.local / Admin@123
                </div>
                <div class="test-user-item">
                    <i class="fas fa-user-tie"></i>
                    admin.b@org.local / Admin@123
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function flipCard() {
    document.getElementById('loginCard').classList.add('flipped');
}

// Login animation
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('form[method="post"]');
    const loginBtn = document.getElementById('loginBtn');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const loadingStatus = document.getElementById('loadingStatus');
    
    if (loginForm && loginBtn && loadingOverlay) {
        loginForm.addEventListener('submit', function(e) {
            // Prevent immediate submission
            e.preventDefault();
            
            // Show loading state
            loginBtn.classList.add('loading');
            
            // Force reflow to ensure transition works
            loadingOverlay.offsetHeight;
            
            loadingOverlay.classList.add('active');
            
            // Animate status messages
            const statusMessages = [
                'Authenticating...',
                'Verifying credentials...',
                'Initializing workspace...',
                'Loading user profile...',
                'Preparing dashboard...'
            ];
            
            let messageIndex = 0;
            const statusInterval = setInterval(() => {
                if (messageIndex < statusMessages.length) {
                    loadingStatus.textContent = statusMessages[messageIndex];
                    messageIndex++;
                }
            }, 600);
            
            // After 3 seconds, fade out and submit
            setTimeout(() => {
                clearInterval(statusInterval);
                
                // Fade out animation
                loadingOverlay.classList.remove('active');
                
                // Wait for fade out to complete before submitting
                setTimeout(() => {
                    loginForm.submit();
                }, 400);
            }, 3000);
        });
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-Yvpcr1f6F/VdtEaJrxi2ZKJ" crossorigin="anonymous"></script>
</body>
</html>
