<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/navigation.php';
require_once __DIR__ . '/../includes/departments.php';

requireSuperAdmin();

$currentUserId = getCurrentUserId();
$summary = getDashboardSummary($currentUserId);
$recentMessages = getRecentComplaintMessages(10);

/**
 * Converts role code to user-facing label.
 */
function dashboardRoleLabel(string $role): string
{
    return $role === ROLE_SUPER_ADMIN ? 'Super Admin' : 'Department Admin';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Internal Complaint Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/main.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/layout.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/sidebar.css')) ?>">
</head>
<body>
<div class="app-shell">
    <?php renderNavigationSidebar('dashboard'); ?>

    <main class="app-main">
        <div class="page-wrapper">
            <h1 class="page-title">Super Admin Dashboard</h1>
            <p class="page-subtitle">Overview of departments, users, and complaint chat activity.</p>

            <section class="row g-3 mb-4">
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="stat-card h-100">
                        <div class="stat-label">Departments</div>
                        <div class="stat-value"><?= (int) $summary['total_departments'] ?></div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="stat-card h-100">
                        <div class="stat-label">Total Users</div>
                        <div class="stat-value"><?= (int) $summary['total_users'] ?></div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="stat-card h-100">
                        <div class="stat-label">Active Super Admins</div>
                        <div class="stat-value"><?= (int) $summary['active_super_admins'] ?></div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="stat-card h-100">
                        <div class="stat-label">Active Dept Admins</div>
                        <div class="stat-value"><?= (int) $summary['active_department_admins'] ?></div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="stat-card h-100">
                        <div class="stat-label">Total Messages</div>
                        <div class="stat-value"><?= (int) $summary['total_messages'] ?></div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="stat-card h-100">
                        <div class="stat-label">Unread for You</div>
                        <div class="stat-value"><?= (int) $summary['unread_messages'] ?></div>
                    </div>
                </div>
            </section>

            <section class="card module-card">
                <div class="card-header">
                    <h2 class="h6 mb-0">Recent Complaint Chat Messages</h2>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Timestamp</th>
                                    <th scope="col">Sender</th>
                                    <th scope="col">Receiver</th>
                                    <th scope="col">Message Preview</th>
                                    <th scope="col">File</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (count($recentMessages) === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No complaint chat activity yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentMessages as $row): ?>
                                    <?php
                                    $preview = trim((string) ($row['message_text'] ?? ''));
                                    if ($preview === '') {
                                        $preview = '[File attachment only]';
                                    }
                                    if (strlen($preview) > 90) {
                                        $preview = substr($preview, 0, 87) . '...';
                                    }
                                    ?>
                                    <tr>
                                        <td class="small"><?= e((string) $row['created_at']) ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= e((string) $row['sender_name']) ?></div>
                                            <div class="small text-muted"><?= e(dashboardRoleLabel((string) $row['sender_role'])) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= e((string) $row['receiver_name']) ?></div>
                                            <div class="small text-muted"><?= e(dashboardRoleLabel((string) $row['receiver_role'])) ?></div>
                                        </td>
                                        <td class="small"><?= e($preview) ?></td>
                                        <td>
                                            <?php if ((int) $row['has_attachment'] === 1): ?>
                                                <span class="badge text-bg-info">Attached</span>
                                            <?php else: ?>
                                                <span class="badge text-bg-secondary">None</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>
<script src="<?= e(baseUrl('/assets/js/sidebar.js')) ?>"></script>
</body>
</html>
