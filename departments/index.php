<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/navigation.php';
require_once __DIR__ . '/../includes/departments.php';

requireSuperAdmin();

$errors = [];
$successMessage = '';
$departmentName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create_department') {
        $departmentName = trim((string) ($_POST['department_name'] ?? ''));

        if ($departmentName === '' || strlen($departmentName) < 2) {
            $errors[] = 'Department name is required and should be at least 2 characters.';
        }

        if (strlen($departmentName) > 100) {
            $errors[] = 'Department name should not exceed 100 characters.';
        }

        if (count($errors) === 0 && departmentNameExists($departmentName)) {
            $errors[] = 'Department name already exists.';
        }

        if (count($errors) === 0) {
            createDepartment($departmentName);
            $successMessage = 'Department added successfully.';
            $departmentName = '';
        }
    }
}

$departments = getDepartmentsWithUserCounts();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Departments - Internal Complaint Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/main.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/layout.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/sidebar.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/darkmode.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/saas2026.css')) ?>">
    <style>
        body {
            background: white !important;
        }
    </style>
</head>
<body>
<div class="app-shell">
    <?php renderNavigationSidebar('departments'); ?>

    <main class="app-main">
        <div class="page-wrapper">
            <h1 class="page-title">Departments</h1>
            <p class="page-subtitle">Manage department list used for user assignment and complaint routing.</p>

            <?php if ($successMessage !== ''): ?>
                <div class="alert alert-success py-2"><?= e($successMessage) ?></div>
            <?php endif; ?>

            <?php if (count($errors) > 0): ?>
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-12 col-lg-4">
                    <div class="card module-card">
                        <div class="card-header">
                            <h2 class="h6 mb-0">Add Department</h2>
                        </div>
                        <div class="card-body">
                            <form method="post" action="<?= e(baseUrl('/departments/index.php')) ?>">
                                <input type="hidden" name="action" value="create_department">

                                <div class="mb-3">
                                    <label for="department_name" class="form-label">Department Name</label>
                                    <input type="text" class="form-control" id="department_name" name="department_name" maxlength="100" value="<?= e($departmentName) ?>" required>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">Add Department</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-8">
                    <div class="card module-card">
                        <div class="card-header">
                            <h2 class="h6 mb-0">Department Directory</h2>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">Department</th>
                                            <th scope="col">Total Users</th>
                                            <th scope="col">Active Users</th>
                                            <th scope="col">Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (count($departments) === 0): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No departments available yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($departments as $department): ?>
                                            <tr>
                                                <td class="fw-semibold"><?= e((string) $department['name']) ?></td>
                                                <td><?= (int) $department['user_count'] ?></td>
                                                <td><?= (int) $department['active_user_count'] ?></td>
                                                <td class="small text-muted"><?= e((string) $department['created_at']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="<?= e(baseUrl('/assets/js/sidebar.js')) ?>"></script>
<script src="<?= e(baseUrl('/assets/js/darkmode.js')) ?>"></script>
</body>
</html>
