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
$roleOptions = [
    [
        'value' => ROLE_SUPER_ADMIN,
        'label' => 'Admin',
        'description' => 'Full platform access',
    ],
    [
        'value' => ROLE_DEPARTMENT_ADMIN,
        'label' => 'Editor',
        'description' => 'Manage department activity',
    ],
];

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

    if ($action === 'update_user_role') {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $role = (string) ($_POST['role'] ?? '');
        $existingUser = getUserForManagementById($targetUserId);

        if ($existingUser === null) {
            $errors[] = 'Selected user was not found.';
        }

        if (!isValidUserRole($role)) {
            $errors[] = 'Please select a valid user role.';
        }

        if (count($errors) === 0 && $targetUserId === $currentUserId && $role !== ROLE_SUPER_ADMIN) {
            $errors[] = 'You cannot remove your own Super Admin role while logged in.';
        }

        if (count($errors) === 0) {
            updateManagedUserRole($targetUserId, $role);
            $successMessage = 'User role updated successfully.';
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
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/darkmode.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/saas2026.css')) ?>">
    <style>
        body {
            background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%) !important;
        }

        .page-wrapper {
            padding: 1.5rem;
        }

        .users-hero {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .users-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.4rem 0.7rem;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.08);
            color: #2563eb;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 0.75rem;
        }

        .page-title {
            margin-bottom: 0.35rem;
        }

        .page-subtitle {
            margin-bottom: 0;
            max-width: 58ch;
            color: #64748b;
        }

        .surface-card {
            border: 1px solid rgba(148, 163, 184, 0.16);
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.88);
            box-shadow: 0 20px 50px -28px rgba(15, 23, 42, 0.35);
            backdrop-filter: blur(18px);
        }

        .surface-card .card-header {
            border-bottom: 1px solid rgba(148, 163, 184, 0.14);
            background: transparent;
            padding: 1.1rem 1.25rem 0.95rem;
        }

        .surface-card .card-body {
            padding: 1.25rem;
        }

        .users-management-layout {
            display: grid;
            grid-template-columns: minmax(320px, 420px) minmax(0, 1fr);
            gap: 1.25rem;
            align-items: start;
        }

        .search-shell {
            position: relative;
            width: min(100%, 360px);
        }

        .search-shell input {
            height: 48px;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            padding: 0.85rem 1rem 0.85rem 2.75rem;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: none;
        }

        .search-shell input:focus {
            border-color: rgba(37, 99, 235, 0.4);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
        }

        .search-shell svg {
            position: absolute;
            left: 0.95rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }

        .users-list-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .users-list-meta {
            color: #64748b;
            font-size: 0.92rem;
        }

        .users-grid {
            display: grid;
            gap: 0.85rem;
        }

        .user-card-row {
            width: 100%;
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 20px;
            padding: 1rem 1.05rem;
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: 0.95rem;
            align-items: center;
            text-align: left;
            transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease;
            cursor: pointer;
        }

        .user-card-row:hover,
        .user-card-row:focus-visible {
            transform: translateY(-2px);
            border-color: rgba(37, 99, 235, 0.24);
            box-shadow: 0 18px 30px -24px rgba(37, 99, 235, 0.45);
            outline: none;
        }

        .user-avatar {
            width: 46px;
            height: 46px;
            border-radius: 16px;
            background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
            color: #1d4ed8;
            display: grid;
            place-items: center;
            font-size: 0.98rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .user-card-name {
            font-size: 0.98rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.15rem;
        }

        .user-card-meta,
        .user-card-email {
            font-size: 0.85rem;
            color: #64748b;
        }

        .user-card-email {
            margin-bottom: 0.2rem;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .role-badge,
        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.42rem 0.72rem;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .role-badge {
            background: rgba(37, 99, 235, 0.08);
            color: #1d4ed8;
        }

        .status-badge.active {
            background: rgba(16, 185, 129, 0.12);
            color: #047857;
        }

        .status-badge.inactive {
            background: rgba(239, 68, 68, 0.1);
            color: #b91c1c;
        }

        .user-card-aside {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.55rem;
        }

        .empty-users-state {
            border: 1px dashed rgba(148, 163, 184, 0.28);
            border-radius: 20px;
            padding: 2rem 1rem;
            text-align: center;
            color: #64748b;
            background: rgba(248, 250, 252, 0.8);
        }

        .role-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
            z-index: 1050;
        }

        .role-modal.is-open {
            display: flex;
        }

        .role-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.42);
            backdrop-filter: blur(8px);
            opacity: 0;
            transition: opacity 0.24s ease;
        }

        .role-modal.is-open .role-modal-backdrop {
            opacity: 1;
        }

        .role-modal-dialog {
            position: relative;
            width: min(100%, 560px);
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow: 0 30px 80px -36px rgba(15, 23, 42, 0.6);
            padding: 1.4rem;
            transform: translateY(18px) scale(0.98);
            opacity: 0;
            transition: transform 0.24s ease, opacity 0.24s ease;
        }

        .role-modal.is-open .role-modal-dialog {
            transform: translateY(0) scale(1);
            opacity: 1;
        }

        .role-modal-close {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 14px;
            background: rgba(148, 163, 184, 0.12);
            color: #334155;
            display: inline-grid;
            place-items: center;
        }

        .role-modal-close:hover {
            background: rgba(148, 163, 184, 0.18);
        }

        .role-modal-header {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.2rem;
        }

        .role-modal-title {
            margin-bottom: 0.3rem;
            font-size: 1.2rem;
            font-weight: 800;
            color: #0f172a;
        }

        .role-modal-subtitle {
            margin: 0;
            color: #64748b;
            font-size: 0.92rem;
        }

        .role-user-summary {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 0.9rem;
            padding: 1rem;
            border-radius: 20px;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            border: 1px solid rgba(148, 163, 184, 0.16);
            margin-bottom: 1rem;
        }

        .role-user-summary-label {
            color: #64748b;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 700;
            margin-bottom: 0.18rem;
        }

        .role-user-summary-value {
            color: #0f172a;
            font-weight: 700;
            font-size: 0.95rem;
        }

        .role-option-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 0.75rem;
            margin-top: 0.85rem;
        }

        .role-chip {
            position: relative;
        }

        .role-chip input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .role-chip-label {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            padding: 0.95rem;
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: #ffffff;
            cursor: pointer;
            transition: all 0.22s ease;
            min-height: 100%;
        }

        .role-chip input:checked + .role-chip-label {
            border-color: rgba(37, 99, 235, 0.35);
            background: linear-gradient(180deg, rgba(219, 234, 254, 0.7) 0%, rgba(255, 255, 255, 1) 100%);
            box-shadow: 0 14px 24px -22px rgba(37, 99, 235, 0.6);
        }

        .role-chip-title {
            font-size: 0.93rem;
            font-weight: 700;
            color: #0f172a;
        }

        .role-chip-description {
            font-size: 0.8rem;
            color: #64748b;
            line-height: 1.45;
        }

        .role-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 1.25rem;
        }

        .btn-soft-neutral {
            border: 1px solid rgba(148, 163, 184, 0.2);
            background: #ffffff;
            color: #334155;
            border-radius: 14px;
            padding: 0.8rem 1rem;
            font-weight: 700;
        }

        .btn-soft-primary {
            border: none;
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            color: #ffffff;
            border-radius: 14px;
            padding: 0.8rem 1rem;
            font-weight: 700;
            box-shadow: 0 18px 30px -20px rgba(37, 99, 235, 0.8);
        }

        .toast-stack {
            position: fixed;
            top: 1.25rem;
            right: 1.25rem;
            z-index: 1080;
            display: grid;
            gap: 0.75rem;
            pointer-events: none;
        }

        .saas-toast {
            min-width: 280px;
            max-width: 360px;
            padding: 0.95rem 1rem;
            border-radius: 18px;
            color: #0f172a;
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 22px 40px -28px rgba(15, 23, 42, 0.45);
            pointer-events: auto;
            display: none;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .saas-toast.is-visible {
            display: flex;
            animation: toastIn 0.28s ease;
        }

        .saas-toast.success {
            border-color: rgba(16, 185, 129, 0.24);
        }

        .saas-toast.error {
            border-color: rgba(239, 68, 68, 0.22);
        }

        .saas-toast-icon {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            font-weight: 800;
        }

        .saas-toast.success .saas-toast-icon {
            background: rgba(16, 185, 129, 0.12);
            color: #047857;
        }

        .saas-toast.error .saas-toast-icon {
            background: rgba(239, 68, 68, 0.1);
            color: #b91c1c;
        }

        .saas-toast-title {
            font-weight: 800;
            margin-bottom: 0.15rem;
        }

        .saas-toast-message {
            font-size: 0.86rem;
            color: #64748b;
            line-height: 1.5;
        }

        .visually-hidden-panel {
            display: none !important;
        }

        @keyframes toastIn {
            from {
                opacity: 0;
                transform: translateY(-8px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @media (max-width: 1199.98px) {
            .users-management-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767.98px) {
            .page-wrapper {
                padding: 1rem;
            }

            .users-hero {
                flex-direction: column;
                align-items: stretch;
            }

            .search-shell {
                width: 100%;
            }

            .user-card-row {
                grid-template-columns: auto minmax(0, 1fr);
            }

            .user-card-aside {
                grid-column: 1 / -1;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }

            .role-modal-dialog {
                padding: 1rem;
                border-radius: 22px;
            }
        }
    </style>
</head>
<body>
<div class="app-shell">
    <?php renderNavigationSidebar('users'); ?>

    <main class="app-main">
        <div class="page-wrapper">
            <div class="users-hero">
                <div>
                    <div class="users-eyebrow">Super Admin Control</div>
                    <h1 class="page-title">User Role Management</h1>
                    <p class="page-subtitle">Manage access with a clean, minimal workflow. Select a user, review their details, and update their assigned role in a focused modal.</p>
                </div>
                <div class="search-shell">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                    <input type="search" id="userSearchInput" class="form-control" placeholder="Search users by name, email, department, or role">
                </div>
            </div>

            <div class="users-management-layout">
                <div class="card module-card surface-card">
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
                                        <?php foreach ($roleOptions as $roleOption): ?>
                                            <option value="<?= e((string) $roleOption['value']) ?>"<?= (string) $formData['role'] === (string) $roleOption['value'] ? ' selected' : '' ?>><?= e((string) $roleOption['label']) ?></option>
                                        <?php endforeach; ?>
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

                <div class="card module-card surface-card">
                        <div class="card-header">
                            <div class="users-list-panel-header">
                                <div>
                                    <h2 class="h6 mb-1">Team Directory</h2>
                                    <div class="users-list-meta"><span id="userResultsCount"><?= count($users) ?></span> users available for role management</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="users-grid" id="usersGrid">
                                <?php if (count($users) === 0): ?>
                                    <div class="empty-users-state">No users found.</div>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <?php
                                        $isSelf = (int) $user['id'] === $currentUserId;
                                        $isActive = (int) $user['is_active'] === 1;
                                        $initials = strtoupper(substr((string) $user['full_name'], 0, 1));
                                        ?>
                                        <button
                                            type="button"
                                            class="user-card-row"
                                            data-user-trigger
                                            data-user-id="<?= (int) $user['id'] ?>"
                                            data-user-name="<?= e((string) $user['full_name']) ?>"
                                            data-user-email="<?= e((string) $user['email']) ?>"
                                            data-user-department="<?= e((string) $user['department_name']) ?>"
                                            data-user-role="<?= e((string) $user['role']) ?>"
                                            data-user-role-label="<?= e(getUserRoleLabel((string) $user['role'])) ?>"
                                            data-user-status="<?= $isActive ? 'Active' : 'Inactive' ?>"
                                            data-user-self="<?= $isSelf ? '1' : '0' ?>"
                                            data-search="<?= e(strtolower((string) $user['full_name'] . ' ' . (string) $user['email'] . ' ' . (string) $user['department_name'] . ' ' . getUserRoleLabel((string) $user['role']))) ?>"
                                        >
                                            <div class="user-avatar"><?= e($initials) ?></div>
                                            <div>
                                                <div class="user-card-name"><?= e((string) $user['full_name']) ?><?= $isSelf ? ' · You' : '' ?></div>
                                                <div class="user-card-email"><?= e((string) $user['email']) ?></div>
                                                <div class="user-card-meta"><?= e((string) $user['department_name']) ?></div>
                                            </div>
                                            <div class="user-card-aside">
                                                <span class="role-badge"><?= e(getUserRoleLabel((string) $user['role'])) ?></span>
                                                <span class="status-badge <?= $isActive ? 'active' : 'inactive' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span>
                                            </div>
                                        </button>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </main>
</div>

<div class="toast-stack" id="toastStack">
    <?php if ($successMessage !== ''): ?>
        <div class="saas-toast success is-visible" data-toast>
            <div class="saas-toast-icon">✓</div>
            <div>
                <div class="saas-toast-title">Success</div>
                <div class="saas-toast-message"><?= e($successMessage) ?></div>
            </div>
        </div>
    <?php endif; ?>
    <?php if (count($errors) > 0): ?>
        <div class="saas-toast error is-visible" data-toast>
            <div class="saas-toast-icon">!</div>
            <div>
                <div class="saas-toast-title">Update failed</div>
                <div class="saas-toast-message"><?= e(implode(' ', $errors)) ?></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="role-modal" id="roleModal" aria-hidden="true">
    <div class="role-modal-backdrop" data-close-role-modal></div>
    <div class="role-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="roleModalTitle">
        <div class="role-modal-header">
            <div>
                <div class="users-eyebrow mb-2">Role Assignment</div>
                <h2 class="role-modal-title" id="roleModalTitle">Update user role</h2>
                <p class="role-modal-subtitle">Choose one access level and save changes.</p>
            </div>
            <button type="button" class="role-modal-close" data-close-role-modal aria-label="Close role modal">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
            </button>
        </div>

        <div class="role-user-summary">
            <div class="user-avatar" id="roleModalAvatar">U</div>
            <div>
                <div class="role-user-summary-label">Selected user</div>
                <div class="role-user-summary-value" id="roleModalUserName">-</div>
                <div class="user-card-email" id="roleModalUserEmail">-</div>
                <div class="user-card-meta" id="roleModalUserMeta">-</div>
            </div>
        </div>

        <form method="post" action="<?= e(baseUrl('/users/index.php')) ?>" id="roleModalForm">
            <input type="hidden" name="action" value="update_user_role">
            <input type="hidden" name="user_id" id="roleModalUserId" value="0">

            <div>
                <div class="role-user-summary-label">Current selection</div>
                <div class="role-option-grid">
                    <?php foreach ($roleOptions as $index => $roleOption): ?>
                        <label class="role-chip">
                            <input type="radio" name="role" value="<?= e((string) $roleOption['value']) ?>"<?= $index === 0 ? ' checked' : '' ?>>
                            <span class="role-chip-label">
                                <span class="role-chip-title"><?= e((string) $roleOption['label']) ?></span>
                                <span class="role-chip-description"><?= e((string) $roleOption['description']) ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="role-modal-actions">
                <button type="button" class="btn-soft-neutral" data-close-role-modal>Cancel</button>
                <button type="submit" class="btn-soft-primary">Save role</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= e(baseUrl('/assets/js/sidebar.js')) ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('roleModal');
    const modalForm = document.getElementById('roleModalForm');
    const userTriggers = document.querySelectorAll('[data-user-trigger]');
    const closeTriggers = document.querySelectorAll('[data-close-role-modal]');
    const userSearchInput = document.getElementById('userSearchInput');
    const userResultsCount = document.getElementById('userResultsCount');
    const usersGrid = document.getElementById('usersGrid');
    const roleModalUserId = document.getElementById('roleModalUserId');
    const roleModalUserName = document.getElementById('roleModalUserName');
    const roleModalUserEmail = document.getElementById('roleModalUserEmail');
    const roleModalUserMeta = document.getElementById('roleModalUserMeta');
    const roleModalAvatar = document.getElementById('roleModalAvatar');
    const toasts = document.querySelectorAll('[data-toast]');

    const openModal = (trigger) => {
        if (!modal || !modalForm) {
            return;
        }

        const userId = trigger.dataset.userId || '0';
        const userName = trigger.dataset.userName || '-';
        const userEmail = trigger.dataset.userEmail || '-';
        const userDepartment = trigger.dataset.userDepartment || '-';
        const userStatus = trigger.dataset.userStatus || '-';
        const userRole = trigger.dataset.userRole || '';

        roleModalUserId.value = userId;
        roleModalUserName.textContent = userName;
        roleModalUserEmail.textContent = userEmail;
        roleModalUserMeta.textContent = `${userDepartment} · ${userStatus}`;
        roleModalAvatar.textContent = userName.charAt(0).toUpperCase();

        modalForm.querySelectorAll('input[name="role"]').forEach((input) => {
            input.checked = input.value === userRole;
        });

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    const closeModal = () => {
        if (!modal) {
            return;
        }

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    userTriggers.forEach((trigger) => {
        trigger.addEventListener('click', () => openModal(trigger));
    });

    closeTriggers.forEach((trigger) => {
        trigger.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    if (userSearchInput && usersGrid && userResultsCount) {
        const cards = Array.from(usersGrid.querySelectorAll('[data-user-trigger]'));

        const applySearch = () => {
            const query = userSearchInput.value.trim().toLowerCase();
            let visibleCount = 0;

            cards.forEach((card) => {
                const haystack = card.dataset.search || '';
                const isVisible = query === '' || haystack.includes(query);
                card.classList.toggle('visually-hidden-panel', !isVisible);
                if (isVisible) {
                    visibleCount += 1;
                }
            });

            userResultsCount.textContent = String(visibleCount);
        };

        userSearchInput.addEventListener('input', applySearch);
        applySearch();
    }

    toasts.forEach((toast) => {
        window.setTimeout(() => {
            toast.classList.remove('is-visible');
        }, 3200);
    });
});
</script>
</body>
</html>
