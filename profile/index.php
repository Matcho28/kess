<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/navigation.php';

requireLogin();
$currentUser = getCurrentUser();
$currentRole = getCurrentUserRole();
$roleLabel = $currentRole === ROLE_SUPER_ADMIN ? 'Super Admin' : 'Department Admin';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile - Internal Complaint Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/main.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/layout.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/sidebar.css')) ?>">
</head>
<body>
<div class="app-shell">
    <?php renderNavigationSidebar('profile'); ?>

    <main class="app-main">
        <div class="page-wrapper">
            <h1 class="page-title">Profile</h1>
            <p class="page-subtitle">Your account details for the internal complaint chat system.</p>

            <div class="card module-card" style="max-width: 720px;">
                <div class="card-header">
                    <h2 class="h6 mb-0">Account Information</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted mb-1">Full Name</label>
                            <div class="fw-semibold"><?= e((string) ($currentUser['full_name'] ?? '')) ?></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted mb-1">Email</label>
                            <div class="fw-semibold"><?= e((string) ($currentUser['email'] ?? '')) ?></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted mb-1">Department</label>
                            <div class="fw-semibold"><?= e((string) ($currentUser['department_name'] ?? '')) ?></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted mb-1">Role</label>
                            <div class="fw-semibold"><?= e($roleLabel) ?></div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-4 mb-0 py-2">
                        For profile updates such as password reset or email changes, contact the Super Admin.
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="<?= e(baseUrl('/assets/js/sidebar.js')) ?>"></script>
</body>
</html>
