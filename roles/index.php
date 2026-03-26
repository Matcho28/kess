<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/navigation.php';
require_once __DIR__ . '/../includes/users.php';
require_once __DIR__ . '/../includes/departments.php';
require_once __DIR__ . '/../includes/roles.php';

requireSuperAdmin();

$errors = [];
$successMessage = '';

// Handle bulk role assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    
    if ($action === 'bulk_assign_role') {
        $selectedUsers = $_POST['selected_users'] ?? [];
        $newRole = (string) ($_POST['role'] ?? '');
        
        if (empty($selectedUsers)) {
            $errors[] = 'Please select at least one user.';
        }
        
        if (!isValidUserRole($newRole)) {
            $errors[] = 'Please select a valid role.';
        }
        
        if (count($errors) === 0) {
            $updatedCount = bulkAssignRole($selectedUsers, $newRole, getCurrentUserId());
            $successMessage = "Successfully updated roles for {$updatedCount} user(s).";
        }
    }
}

$roleStatistics = getRoleStatistics();
$recentRoleChanges = getRecentRoleChanges(10);
$usersByRole = getUsersByRole();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Role Management - Internal Complaint Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/main.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/layout.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/sidebar.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/users.css')) ?>">
</head>
<body>
<div class="app-shell">
    <?php renderNavigationSidebar('roles'); ?>

    <main class="app-main">
        <div class="page-wrapper">
            <h1 class="page-title">Role Management</h1>
            <p class="page-subtitle">Manage user roles and permissions across the system.</p>

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

            <!-- Role Statistics Cards -->
            <section class="row g-3 mb-4">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="stat-card h-100">
                        <div class="stat-label">Super Admins</div>
                        <div class="stat-value"><?= (int) ($roleStatistics['super_admin']['total'] ?? 0) ?></div>
                        <div class="stat-subtitle"><?= (int) ($roleStatistics['super_admin']['active'] ?? 0) ?> active</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="stat-card h-100">
                        <div class="stat-label">Department Admins</div>
                        <div class="stat-value"><?= (int) ($roleStatistics['department_admin']['total'] ?? 0) ?></div>
                        <div class="stat-subtitle"><?= (int) ($roleStatistics['department_admin']['active'] ?? 0) ?> active</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="stat-card h-100">
                        <div class="stat-label">Total Users</div>
                        <div class="stat-value"><?= (int) ($roleStatistics['total_users'] ?? 0) ?></div>
                        <div class="stat-subtitle">All roles</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="stat-card h-100">
                        <div class="stat-label">Recent Changes</div>
                        <div class="stat-value"><?= (int) ($roleStatistics['recent_changes'] ?? 0) ?></div>
                        <div class="stat-subtitle">Last 7 days</div>
                    </div>
                </div>
            </section>

            <!-- Role Management Tabs -->
            <div class="card module-card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="roleTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">Overview</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="bulk-assign-tab" data-bs-toggle="tab" data-bs-target="#bulk-assign" type="button" role="tab">Bulk Assign</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="changes-tab" data-bs-toggle="tab" data-bs-target="#changes" type="button" role="tab">Recent Changes</button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="roleTabsContent">
                        <!-- Overview Tab -->
                        <div class="tab-pane fade show active" id="overview" role="tabpanel">
                            <h3 class="h5 mb-3">Role Overview</h3>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Role</th>
                                            <th>Description</th>
                                            <th>Total Users</th>
                                            <th>Active Users</th>
                                            <th>Permissions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><span class="badge bg-danger">Super Admin</span></td>
                                            <td>Full system access including user and department management</td>
                                            <td><?= (int) ($roleStatistics['super_admin']['total'] ?? 0) ?></td>
                                            <td><?= (int) ($roleStatistics['super_admin']['active'] ?? 0) ?></td>
                                            <td>
                                                <small class="text-muted">
                                                    Dashboard, Users, Departments, Roles, Chat, Profile
                                                </small>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><span class="badge bg-primary">Department Admin</span></td>
                                            <td>Limited to chat functionality and profile management</td>
                                            <td><?= (int) ($roleStatistics['department_admin']['total'] ?? 0) ?></td>
                                            <td><?= (int) ($roleStatistics['department_admin']['active'] ?? 0) ?></td>
                                            <td>
                                                <small class="text-muted">
                                                    Chat, Profile
                                                </small>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Bulk Assign Tab -->
                        <div class="tab-pane fade" id="bulk-assign" role="tabpanel">
                            <h3 class="h5 mb-3">Bulk Role Assignment</h3>
                            <form method="post" action="<?= e(baseUrl('/roles/index.php')) ?>" id="bulkAssignForm">
                                <input type="hidden" name="action" value="bulk_assign_role">
                                
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="role" class="form-label">Assign Role</label>
                                        <select id="role" name="role" class="form-select" required>
                                            <option value="">Select a role...</option>
                                            <option value="<?= e(ROLE_SUPER_ADMIN) ?>"><?= e(getUserRoleLabel(ROLE_SUPER_ADMIN)) ?></option>
                                            <option value="<?= e(ROLE_DEPARTMENT_ADMIN) ?>"><?= e(getUserRoleLabel(ROLE_DEPARTMENT_ADMIN)) ?></option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">Select Users</label>
                                        <div class="row g-2">
                                            <?php foreach ($usersByRole as $role => $users): ?>
                                                <?php foreach ($users as $user): ?>
                                                    <div class="col-12 col-md-6 col-lg-4">
                                                        <div class="form-check">
                                                            <input class="form-check-input user-checkbox" type="checkbox" 
                                                                   value="<?= (int) $user['id'] ?>" 
                                                                   name="selected_users[]" 
                                                                   id="user_<?= (int) $user['id'] ?>">
                                                            <label class="form-check-label" for="user_<?= (int) $user['id'] ?>">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <div>
                                                                        <div class="fw-semibold"><?= e($user['full_name']) ?></div>
                                                                        <div class="small text-muted"><?= e($user['email']) ?></div>
                                                                    </div>
                                                                    <span class="badge bg-secondary"><?= e(getUserRoleLabel($user['role'])) ?></span>
                                                                </div>
                                                            </label>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllUsers">Select All</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllUsers">Deselect All</button>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Assign Role to Selected Users</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Recent Changes Tab -->
                        <div class="tab-pane fade" id="changes" role="tabpanel">
                            <h3 class="h5 mb-3">Recent Role Changes</h3>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>User</th>
                                            <th>Previous Role</th>
                                            <th>New Role</th>
                                            <th>Changed By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($recentRoleChanges) === 0): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">No recent role changes found.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recentRoleChanges as $change): ?>
                                                <tr>
                                                    <td class="small"><?= e($change['created_at']) ?></td>
                                                    <td>
                                                        <div class="fw-semibold"><?= e($change['user_full_name']) ?></div>
                                                        <div class="small text-muted"><?= e($change['user_email']) ?></div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary"><?= e(getUserRoleLabel($change['old_role'])) ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success"><?= e(getUserRoleLabel($change['new_role'])) ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold"><?= e($change['changed_by_name']) ?></div>
                                                        <div class="small text-muted"><?= e($change['changed_by_email']) ?></div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?= e(baseUrl('/assets/js/sidebar.js')) ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select/Deselect All functionality
    const selectAllBtn = document.getElementById('selectAllUsers');
    const deselectAllBtn = document.getElementById('deselectAllUsers');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            userCheckboxes.forEach(checkbox => checkbox.checked = true);
        });
    }
    
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function() {
            userCheckboxes.forEach(checkbox => checkbox.checked = false);
        });
    }
    
    // Form validation
    const bulkAssignForm = document.getElementById('bulkAssignForm');
    if (bulkAssignForm) {
        bulkAssignForm.addEventListener('submit', function(e) {
            const selectedUsers = document.querySelectorAll('.user-checkbox:checked');
            const roleSelect = document.getElementById('role');
            
            if (selectedUsers.length === 0) {
                e.preventDefault();
                alert('Please select at least one user.');
                return false;
            }
            
            if (!roleSelect.value) {
                e.preventDefault();
                alert('Please select a role.');
                return false;
            }
            
            if (!confirm(`Are you sure you want to change the role for ${selectedUsers.length} user(s)?`)) {
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>
</body>
</html>
