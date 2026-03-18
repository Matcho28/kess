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
    <title>Admin Login - Internal Complaint Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?= e(baseUrl('/assets/css/main.css')) ?>" rel="stylesheet">
    <link href="<?= e(baseUrl('/assets/css/saas2026.css')) ?>" rel="stylesheet">
    <link href="<?= e(baseUrl('/assets/css/darkmode.css')) ?>" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: var(--bg-gradient);
        }
        
        .login-container {
            max-width: 430px;
        }
        
        .login-card {
            border: none;
            border-radius: 1.5rem;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            box-shadow: var(--glass-shadow);
            border: 1px solid var(--glass-border);
        }
        
        .form-control-modern {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 0.75rem;
            padding: 0.875rem 1.25rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-control-modern:focus {
            border-color: var(--primary-500);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            background: var(--input-focus-bg);
        }
        
        .btn-primary-saas {
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            border: none;
            border-radius: 0.75rem;
            padding: 0.875rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }
        
        .btn-primary-saas:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
        }
        
        .alert-danger-modern {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 0.75rem;
            color: var(--danger-600);
        }
    </style>
</head>
<body class="d-flex align-items-center py-4">
<main class="container login-container">
    <div class="login-card">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="mb-3">
                    <div class="logo-saas">
                        <i class="fas fa-comments fa-3x" style="color: var(--primary-500);"></i>
                    </div>
                </div>
                <h1 class="h3 fw-bold mb-2">Internal Complaint Chat</h1>
                <p class="text-muted mb-0">Sign in with your Super Admin or Department Admin account.</p>
            </div>

            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger-modern py-3" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= e($errorMessage) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(baseUrl('/auth/login.php')) ?>" novalidate>
                <div class="mb-4">
                    <label for="email" class="form-label fw-semibold">
                        <i class="fas fa-envelope me-2"></i>Email
                    </label>
                    <input type="email" class="form-control form-control-modern" id="email" name="email" value="<?= e($email) ?>" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">
                        <i class="fas fa-lock me-2"></i>Password
                    </label>
                    <input type="password" class="form-control form-control-modern" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary-saas w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Sign In
                </button>
            </form>

            <hr class="my-4" style="border-color: var(--glass-border);">
            <div class="text-center">
                <p class="small text-muted mb-2">
                    <i class="fas fa-info-circle me-1"></i>
                    Seeded test users:
                </p>
                <div class="test-users">
                    <p class="small text-muted mb-1">
                        <i class="fas fa-user-shield me-1"></i>
                        super.admin@org.local / Admin@123
                    </p>
                    <p class="small text-muted mb-1">
                        <i class="fas fa-user-tie me-1"></i>
                        admin.a@org.local / Admin@123
                    </p>
                    <p class="small text-muted mb-0">
                        <i class="fas fa-user-tie me-1"></i>
                        admin.b@org.local / Admin@123
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-Yvpcr1f6F/VdtEaJrxi2ZKJ" crossorigin="anonymous"></script>
</body>
</html>
