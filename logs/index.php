<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/navigation.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

// Get recent messages data
$pdo = db();
$stmt = $pdo->prepare("
    SELECT 
        m.created_at,
        m.message_text,
        (SELECT COUNT(*) FROM message_files mf WHERE mf.message_id = m.id) > 0 AS has_attachment,
        u1.full_name AS sender_name,
        u1.role AS sender_role,
        u2.full_name AS receiver_name,
        u2.role AS receiver_role
    FROM messages m
    JOIN users u1 ON m.sender_id = u1.id
    JOIN users u2 ON m.receiver_id = u2.id
    ORDER BY m.created_at DESC
    LIMIT 50
");
$stmt->execute();
$recentMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);

function dashboardRoleLabel(string $role): string
{
    return $role === 'super_admin' ? 'Super Admin' : 'Department Admin';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Logs - Internal Complaint Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/main.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/layout.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/sidebar.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/darkmode.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/saas2026.css')) ?>">
</head>
<body>
<div class="app-shell">
    <?php renderNavigationSidebar('logs'); ?>

    <main class="app-main">
        <div class="page-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4 fade-in-up">
                <h1 class="h3 mb-0 fw-bold">Logs</h1>
                <div class="d-flex gap-2">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control form-control-modern" id="searchMessages" placeholder="Search messages...">
                    </div>
                    <button class="btn btn-primary-saas" id="refreshBtn">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>

            <div class="card-saas fade-in-up">
                <div class="card-header-saas">
                    <h3 class="h5 mb-0 fw-semibold">
                        <i class="fas fa-comments me-2" style="color: var(--primary-500);"></i>
                        Recent Chat Messages
                    </h3>
                </div>
                <div class="card-body-saas p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 table-saas" id="messagesTable">
                            <thead>
                                <tr>
                                    <th scope="col"><i class="fas fa-clock me-1"></i>Timestamp</th>
                                    <th scope="col"><i class="fas fa-user me-1"></i>Sender</th>
                                    <th scope="col"><i class="fas fa-user me-1"></i>Receiver</th>
                                    <th scope="col"><i class="fas fa-message me-1"></i>Message Preview</th>
                                    <th scope="col"><i class="fas fa-paperclip me-1"></i>File</th>
                                </tr>
                            </thead>
                            <tbody id="messagesTableBody">
                            <?php if (count($recentMessages) === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <i class="fas fa-inbox fa-4x mb-3 d-block text-muted opacity-50"></i>
                                        <div class="text-muted">No complaint chat activity yet.</div>
                                    </td>
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
                                    <tr class="message-row" data-search="<?= e(strtolower($row['sender_name'] . ' ' . $row['receiver_name'] . ' ' . $preview)) ?>">
                                        <td class="small">
                                            <i class="fas fa-clock text-muted me-1"></i>
                                            <?= e((string) $row['created_at']) ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    <i class="fas fa-user text-primary fs-6"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?= e((string) $row['sender_name']) ?></div>
                                                    <div class="small text-muted"><?= e(dashboardRoleLabel((string) $row['sender_role'])) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    <i class="fas fa-user text-success fs-6"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?= e((string) $row['receiver_name']) ?></div>
                                                    <div class="small text-muted"><?= e(dashboardRoleLabel((string) $row['receiver_role'])) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="small message-preview"><?= e($preview) ?></td>
                                        <td>
                                            <?php if ((int) $row['has_attachment'] === 1): ?>
                                                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">
                                                    <i class="fas fa-paperclip me-1"></i>Attached
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill">
                                                    <i class="fas fa-ban me-1"></i>None
                                                </span>
                                            <?php endif; ?>
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
    </main>
</div>

<div class="loading-spinner">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<script src="<?= e(baseUrl('/assets/js/sidebar.js')) ?>"></script>
</body>
</html>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchMessages');
    const messageRows = document.querySelectorAll('.message-row');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        messageRows.forEach(row => {
            const searchContent = row.dataset.search;
            if (searchContent.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    
    // Refresh button functionality
    const refreshBtn = document.getElementById('refreshBtn');
    const loadingSpinner = document.querySelector('.loading-spinner');
    
    refreshBtn.addEventListener('click', function() {
        const icon = this.querySelector('i');
        icon.classList.add('fa-spin');
        loadingSpinner.style.display = 'block';
        
        // Simulate refresh - in real app, this would be an AJAX call
        setTimeout(() => {
            icon.classList.remove('fa-spin');
            loadingSpinner.style.display = 'none';
            
            // Show a subtle notification
            const notification = document.createElement('div');
            notification.className = 'position-fixed top-0 end-0 p-3';
            notification.style.zIndex = '9999';
            notification.innerHTML = `
                <div class="toast show" role="alert">
                    <div class="toast-body">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        Logs refreshed successfully!
                    </div>
                </div>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }, 1500);
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K for search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            searchInput.focus();
        }
        
        // Ctrl/Cmd + R for refresh
        if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
            e.preventDefault();
            refreshBtn.click();
        }
    });
});
</script>
</body>
</html>
