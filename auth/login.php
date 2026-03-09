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
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(145deg, #eef2f6 0%, #f7f9fb 100%);
        }
    </style>
</head>
<body class="d-flex align-items-center py-4">
<main class="container" style="max-width: 430px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4 p-md-5">
            <h1 class="h4 fw-semibold mb-2">Internal Complaint Chat</h1>
            <p class="text-muted mb-4">Sign in with your Super Admin or Department Admin account.</p>

            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger py-2" role="alert"><?= e($errorMessage) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(baseUrl('/auth/login.php')) ?>" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Sign In</button>
            </form>

            <hr class="my-4">
            <p class="small text-muted mb-1">Seeded test users:</p>
            <p class="small text-muted mb-0">super.admin@org.local / Admin@123</p>
            <p class="small text-muted mb-0">admin.a@org.local / Admin@123</p>
            <p class="small text-muted mb-0">admin.b@org.local / Admin@123</p>
        </div>
    </div>
</main>
</body>
</html>
