<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/navigation.php';
require_once __DIR__ . '/../includes/users.php';
require_once __DIR__ . '/../includes/departments.php';

requireSuperAdmin();

$currentUserId = getCurrentUserId();
$errors = [];
$successMessage = '';

$departmentRows = getAllDepartments();
$departmentIds = array_map(static fn(array $row): int => (int) $row['id'], $departmentRows);

$formMode = 'create';
$formData = [
    'user_id' => 0,
    'full_name' => '',
    'email' => '',
    'department_id' => count($departmentIds) > 0 ? $departmentIds[0] : 0,
    'role' => ROLE_DEPARTMENT_ADMIN,
    'is_active' => 1,
];

$editUserId = (int) ($_GET['edit_id'] ?? 0);
if ($editUserId > 0) {
    $editUser = getUserForManagementById($editUserId);

    if ($editUser !== null) {
        $formMode = 'update';
        $formData = [
            'user_id' => (int) $editUser['id'],
            'full_name' => (string) $editUser['full_name'],
            'email' => (string) $editUser['email'],
            'department_id' => (int) $editUser['department_id'],
            'role' => (string) $editUser['role'],
            'is_active' => (int) $editUser['is_active'],
        ];
    } else {
        $errors[] = 'Selected user was not found.';
    }
}

/**
 * Validates and normalizes common form fields.
 */
function normalizeManagedUserInput(array $departmentIds, bool $passwordRequired): array
{
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $departmentId = (int) ($_POST['department_id'] ?? 0);
    $role = (string) ($_POST['role'] ?? '');
    $isActive = (int) ($_POST['is_active'] ?? 0) === 1 ? 1 : 0;
    $password = (string) ($_POST['password'] ?? '');

    $errors = [];

    if ($fullName === '' || strlen($fullName) < 2) {
        $errors[] = 'Full name is required and must be at least 2 characters.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }

    if (!in_array($departmentId, $departmentIds, true)) {
        $errors[] = 'Please select a valid department.';
    }

    if (!isValidUserRole($role)) {
        $errors[] = 'Please select a valid user role.';
    }

    if ($passwordRequired && strlen($password) < 8) {
        $errors[] = 'Password is required and must be at least 8 characters.';
    }

    if (!$passwordRequired && $password !== '' && strlen($password) < 8) {
        $errors[] = 'If provided, password must be at least 8 characters.';
    }

    return [
        'full_name' => $fullName,
        'email' => $email,
        'department_id' => $departmentId,
        'role' => $role,
        'is_active' => $isActive,
        'password' => $password,
        'errors' => $errors,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create_user') {
        $input = normalizeManagedUserInput($departmentIds, true);

        $formMode = 'create';
        $formData = [
            'user_id' => 0,
            'full_name' => (string) $input['full_name'],
            'email' => (string) $input['email'],
            'department_id' => (int) $input['department_id'],
            'role' => (string) $input['role'],
            'is_active' => (int) $input['is_active'],
        ];

        $errors = array_merge($errors, $input['errors']);

        if (count($errors) === 0 && userEmailExists((string) $input['email'])) {
            $errors[] = 'Email already exists. Use a different email address.';
        }

        if (count($errors) === 0) {
            createManagedUser(
                (int) $input['department_id'],
                (string) $input['full_name'],
                (string) $input['email'],
                (string) $input['role'],
                (string) $input['password'],
                (int) $input['is_active']
            );

            $successMessage = 'User account created successfully.';
            $formData = [
                'user_id' => 0,
                'full_name' => '',
                'email' => '',
                'department_id' => count($departmentIds) > 0 ? $departmentIds[0] : 0,
                'role' => ROLE_DEPARTMENT_ADMIN,
                'is_active' => 1,
            ];
        }
    }

    if ($action === 'update_user') {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $existingUser = getUserForManagementById($targetUserId);

        if ($existingUser === null) {
            $errors[] = 'Selected user was not found.';
        }

        $input = normalizeManagedUserInput($departmentIds, false);

        $formMode = 'update';
        $formData = [
            'user_id' => $targetUserId,
            'full_name' => (string) $input['full_name'],
            'email' => (string) $input['email'],
            'department_id' => (int) $input['department_id'],
            'role' => (string) $input['role'],
            'is_active' => (int) $input['is_active'],
        ];

        $errors = array_merge($errors, $input['errors']);

        if (count($errors) === 0 && userEmailExists((string) $input['email'], $targetUserId)) {
            $errors[] = 'Email already exists. Use a different email address.';
        }

        if (count($errors) === 0 && $targetUserId === $currentUserId && (int) $input['is_active'] !== 1) {
            $errors[] = 'You cannot deactivate your own active session account.';
        }

        if (count($errors) === 0 && $targetUserId === $currentUserId && (string) $input['role'] !== ROLE_SUPER_ADMIN) {
            $errors[] = 'You cannot remove your own Super Admin role while logged in.';
        }

        if (count($errors) === 0) {
            updateManagedUser(
                $targetUserId,
                (int) $input['department_id'],
                (string) $input['full_name'],
                (string) $input['email'],
                (string) $input['role'],
                (string) $input['password'],
                (int) $input['is_active']
            );

            $successMessage = 'User information updated successfully.';
        }
    }

    if ($action === 'toggle_user') {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $newState = (int) ($_POST['new_state'] ?? 0) === 1 ? 1 : 0;

        if ($targetUserId <= 0) {
            $errors[] = 'Invalid user selected for status update.';
        }

        if ($targetUserId === $currentUserId && $newState !== 1) {
            $errors[] = 'You cannot deactivate your own active session account.';
        }

        if (count($errors) === 0) {
            updateUserActiveState($targetUserId, $newState);
            $successMessage = $newState === 1 ? 'User activated.' : 'User deactivated.';
        }
    }
}

$users = getAllUsersForManagement();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Management - Internal Complaint Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/main.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/layout.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/sidebar.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/users.css')) ?>">
</head>
<body>
<div class="app-shell">
    <?php renderNavigationSidebar('users'); ?>

    <main class="app-main">
        <div class="page-wrapper">
            <h1 class="page-title">User Management</h1>
            <p class="page-subtitle">Create, update, and control Super Admin / Department Admin accounts.</p>

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
                <div class="col-12 col-xl-5">
                    <div class="card module-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h2 class="h6 mb-0"><?= $formMode === 'update' ? 'Edit User' : 'Add New User' ?></h2>
                            <?php if ($formMode === 'update'): ?>
                                <a href="<?= e(baseUrl('/users/index.php')) ?>" class="btn btn-sm btn-outline-secondary">Cancel Edit</a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <form method="post" action="<?= e(baseUrl('/users/index.php' . ($formMode === 'update' ? '?edit_id=' . (int) $formData['user_id'] : ''))) ?>">
                                <input type="hidden" name="action" value="<?= $formMode === 'update' ? 'update_user' : 'create_user' ?>">
                                <input type="hidden" name="user_id" value="<?= (int) $formData['user_id'] ?>">

                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e((string) $formData['full_name']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= e((string) $formData['email']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="department_id" class="form-label">Department</label>
                                    <select id="department_id" name="department_id" class="form-select" required>
                                        <?php foreach ($departmentRows as $department): ?>
                                            <?php $selected = (int) $formData['department_id'] === (int) $department['id']; ?>
                                            <option value="<?= (int) $department['id'] ?>"<?= $selected ? ' selected' : '' ?>>
                                                <?= e((string) $department['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select id="role" name="role" class="form-select" required>
                                        <option value="<?= e(ROLE_SUPER_ADMIN) ?>"<?= (string) $formData['role'] === ROLE_SUPER_ADMIN ? ' selected' : '' ?>>Super Admin</option>
                                        <option value="<?= e(ROLE_DEPARTMENT_ADMIN) ?>"<?= (string) $formData['role'] === ROLE_DEPARTMENT_ADMIN ? ' selected' : '' ?>>Department Admin</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <?= $formMode === 'update' ? 'New Password (optional)' : 'Password' ?>
                                    </label>
                                    <input type="password" class="form-control" id="password" name="password" <?= $formMode === 'create' ? 'required' : '' ?>>
                                    <div class="form-text">Minimum 8 characters.</div>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"<?= (int) $formData['is_active'] === 1 ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">Active account</label>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    <?= $formMode === 'update' ? 'Save Changes' : 'Create User' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-7">
                    <div class="card module-card">
                        <div class="card-header">
                            <h2 class="h6 mb-0">Existing Users</h2>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">Name</th>
                                            <th scope="col">Email</th>
                                            <th scope="col">Department</th>
                                            <th scope="col">Role</th>
                                            <th scope="col">Status</th>
                                            <th scope="col" class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (count($users) === 0): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">No users found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                            <?php
                                            $isSelf = (int) $user['id'] === $currentUserId;
                                            $isActive = (int) $user['is_active'] === 1;
                                            $toggleTo = $isActive ? 0 : 1;
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold"><?= e((string) $user['full_name']) ?></div>
                                                    <?php if ($isSelf): ?>
                                                        <div class="small text-muted">Current session</div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small"><?= e((string) $user['email']) ?></td>
                                                <td class="small"><?= e((string) $user['department_name']) ?></td>
                                                <td class="small"><?= e(getUserRoleLabel((string) $user['role'])) ?></td>
                                                <td>
                                                    <span class="status-pill <?= $isActive ? 'active' : 'inactive' ?>">
                                                        <?= $isActive ? 'Active' : 'Inactive' ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <a class="btn btn-sm btn-outline-primary" href="<?= e(baseUrl('/users/index.php?edit_id=' . (int) $user['id'])) ?>">Edit</a>

                                                    <form method="post" action="<?= e(baseUrl('/users/index.php')) ?>" class="d-inline">
                                                        <input type="hidden" name="action" value="toggle_user">
                                                        <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                                        <input type="hidden" name="new_state" value="<?= $toggleTo ?>">
                                                        <button type="submit" class="btn btn-sm <?= $isActive ? 'btn-outline-danger' : 'btn-outline-success' ?>"<?= $isSelf && $toggleTo === 0 ? ' disabled' : '' ?>>
                                                            <?= $isActive ? 'Deactivate' : 'Activate' ?>
                                                        </button>
                                                    </form>
                                                </td>
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
</body>
</html>
