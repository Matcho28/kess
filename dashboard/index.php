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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/main.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/layout.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/sidebar.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/darkmode.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/saas2026.css')) ?>">
    <style>
        .dashboard-stats {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .stat-card-modern {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #10b981, #3b82f6, #8b5cf6, #ec4899);
            background-size: 300% 100%;
            animation: gradientShift 3s ease infinite;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .stat-card-modern:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
            color: #ffffff;
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.2);
            transition: all 0.3s ease;
        }
        
        .stat-icon:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 12px 24px rgba(16, 185, 129, 0.3);
        }
        
        .stat-value-modern {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.25rem;
            transition: color 0.3s ease;
            background: linear-gradient(135deg, #1e293b, #3b82f6);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-label-modern {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .modern-card {
            background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.08);
            transition: all 0.3s ease;
            animation: fadeInUp 0.8s ease-out;
            position: relative;
            overflow: hidden;
        }
        
        .modern-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899);
        }
        
        .modern-card:hover {
            box-shadow: 0 8px 30px rgba(59, 130, 246, 0.15);
            transform: translateY(-2px);
        }
        
        .modern-card .card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-bottom: 1px solid rgba(59, 130, 246, 0.15);
        }
        
        .table-modern {
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .table-modern thead {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            color: #ffffff;
        }
        
        .table-modern thead th {
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
            padding: 1.25rem 1rem;
            border: none;
        }
        
        .table-modern tbody tr {
            transition: all 0.2s ease;
        }
        
        .table-modern tbody tr:hover {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
            transform: scale(1.01);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
        }
        
        .badge-modern {
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        
        .search-box {
            position: relative;
            margin-bottom: 0;
            flex-shrink: 0;
        }
        
        .search-box input {
            padding-left: 2.5rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(148, 163, 184, 0.2);
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            min-width: 250px;
        }
        
        body.dark-mode .search-box input {
            background: rgba(30, 41, 59, 0.9);
            border-color: rgba(148, 163, 184, 0.3);
            color: #f1f5f9;
        }
        
        .search-box input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(25, 195, 125, 0.1);
            outline: none;
        }
        
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            pointer-events: none;
            z-index: 5;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .chart-container {
            position: relative;
            height: 250px;
            margin-bottom: 0;
        }
        
        .stat-card-saas {
            min-height: 160px;
            padding: 1.25rem;
        }
        
        .stat-value-saas {
            font-size: 2.2rem;
        }
        
        .card-saas {
            margin-bottom: 0;
            height: 100%;
        }
        
        .card-body-saas {
            padding: 1.25rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .page-wrapper {
            padding: 1.5rem;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .dashboard-stats {
            margin-bottom: 1.5rem;
            flex-shrink: 0;
        }
        
        .charts-section {
            flex: 1;
            min-height: 0;
        }
        
        .app-main {
            height: 100vh;
            overflow: hidden;
        }
        
        .loading-spinner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
        }
        
        .dark-mode-toggle {
            position: fixed;
            top: 2rem;
            right: 2rem;
            z-index: 1000;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(25, 195, 125, 0.3);
        }
        
        .dark-mode-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(25, 195, 125, 0.4);
        }
        
        @media (max-width: 768px) {
            .stat-value-modern {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
<div class="app-shell">
    <?php renderNavigationSidebar('dashboard'); ?>

    <main class="app-main">
        <div class="page-wrapper">
            <div class="d-flex align-items-center mb-3 fade-in-up">
                <h1 class="h3 mb-0 fw-bold">Dashboard</h1>
            </div>

            <div class="dashboard-stats row g-2">
                <div class="col">
                    <div class="stat-card-saas fade-in-up h-100 d-flex flex-column justify-content-center text-center">
                        <div class="mb-2">
                            <i class="fas fa-building fa-lg" style="color: var(--primary-500);"></i>
                        </div>
                        <div class="stat-value-saas" data-value="<?= (int) $summary['total_departments'] ?>">0</div>
                        <div class="stat-label-saas">Departments</div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-card-saas fade-in-up h-100 d-flex flex-column justify-content-center text-center" style="animation-delay: 0.1s;">
                        <div class="mb-2">
                            <i class="fas fa-users fa-lg" style="color: var(--accent-500);"></i>
                        </div>
                        <div class="stat-value-saas" data-value="<?= (int) $summary['total_users'] ?>">0</div>
                        <div class="stat-label-saas">Total Users</div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-card-saas fade-in-up h-100 d-flex flex-column justify-content-center text-center" style="animation-delay: 0.2s;">
                        <div class="mb-2">
                            <i class="fas fa-user-shield fa-lg" style="color: var(--warning-500);"></i>
                        </div>
                        <div class="stat-value-saas" data-value="<?= (int) $summary['active_super_admins'] ?>">0</div>
                        <div class="stat-label-saas">Active Super Admins</div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-card-saas fade-in-up h-100 d-flex flex-column justify-content-center text-center" style="animation-delay: 0.3s;">
                        <div class="mb-2">
                            <i class="fas fa-user-tie fa-lg" style="color: var(--danger-500);"></i>
                        </div>
                        <div class="stat-value-saas" data-value="<?= (int) $summary['active_department_admins'] ?>">0</div>
                        <div class="stat-label-saas">Active Dept Admins</div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-card-saas fade-in-up h-100 d-flex flex-column justify-content-center text-center" style="animation-delay: 0.4s;">
                        <div class="mb-2">
                            <i class="fas fa-comments fa-lg" style="color: var(--primary-500);"></i>
                        </div>
                        <div class="stat-value-saas" data-value="<?= (int) $summary['total_messages'] ?>">0</div>
                        <div class="stat-label-saas">Total Messages</div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-card-saas fade-in-up h-100 d-flex flex-column justify-content-center text-center" style="animation-delay: 0.5s;">
                        <div class="mb-2">
                            <i class="fas fa-envelope fa-lg" style="color: var(--accent-500);"></i>
                        </div>
                        <div class="stat-value-saas" data-value="<?= (int) $summary['unread_messages'] ?>">0</div>
                        <div class="stat-label-saas">Unread for You</div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-section">
                <div class="row g-2 h-100">
                    <div class="col-12 col-lg-6">
                        <div class="card-saas fade-in-up h-100" style="animation-delay: 0.6s;">
                            <div class="card-header-saas">
                                <h3 class="h6 mb-0 fw-semibold">
                                    <i class="fas fa-chart-pie me-2" style="color: var(--primary-500);"></i>
                                    User Distribution
                                </h3>
                            </div>
                            <div class="card-body-saas">
                                <div class="chart-container">
                                    <canvas id="userChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="card-saas fade-in-up h-100" style="animation-delay: 0.7s;">
                            <div class="card-header-saas">
                                <h3 class="h6 mb-0 fw-semibold">
                                    <i class="fas fa-chart-bar me-2" style="color: var(--accent-500);"></i>
                                    Activity Overview
                                </h3>
                            </div>
                            <div class="card-body-saas">
                                <div class="chart-container">
                                    <canvas id="activityChart"></canvas>
                                </div>
                            </div>
                        </div>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= e(baseUrl('/assets/js/sidebar.js')) ?>"></script>
<script src="<?= e(baseUrl('/assets/js/darkmode.js')) ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate statistics counters
    function animateCounter(element, target, duration = 2000) {
        const start = 0;
        const increment = target / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current).toLocaleString();
        }, 16);
    }
    
    // Initialize counters
    document.querySelectorAll('.stat-value-saas').forEach(element => {
        const target = parseInt(element.dataset.value);
        if (target > 0) {
            setTimeout(() => animateCounter(element, target), 300);
        } else {
            element.textContent = '0';
        }
    });
    
    // Add hover effects to stat cards
    document.querySelectorAll('.stat-card-saas').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(-5px) scale(1)';
        });
    });
    
    // Initialize Charts
    const isDarkMode = document.body.classList.contains('dark-mode');
    const textColor = isDarkMode ? '#f1f5f9' : '#1e293b';
    const gridColor = isDarkMode ? 'rgba(148, 163, 184, 0.1)' : 'rgba(148, 163, 184, 0.2)';
    
    // User Distribution Chart (Doughnut)
    const userCtx = document.getElementById('userChart').getContext('2d');
    const userChart = new Chart(userCtx, {
        type: 'doughnut',
        data: {
            labels: ['Super Admins', 'Department Admins', 'Other Users'],
            datasets: [{
                data: [
                    <?= (int) $summary['active_super_admins'] ?>,
                    <?= (int) $summary['active_department_admins'] ?>,
                    <?= (int) $summary['total_users'] - (int) $summary['active_super_admins'] - (int) $summary['active_department_admins'] ?>
                ],
                backgroundColor: [
                    'rgba(25, 195, 125, 0.8)',
                    'rgba(37, 99, 235, 0.8)',
                    'rgba(148, 163, 184, 0.8)'
                ],
                borderColor: [
                    'rgba(25, 195, 125, 1)',
                    'rgba(37, 99, 235, 1)',
                    'rgba(148, 163, 184, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: textColor,
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true
            }
        }
    });
    
    // Activity Overview Chart (Bar)
    const activityCtx = document.getElementById('activityChart').getContext('2d');
    const activityChart = new Chart(activityCtx, {
        type: 'bar',
        data: {
            labels: ['Departments', 'Total Users', 'Messages', 'Unread'],
            datasets: [{
                label: 'Count',
                data: [
                    <?= (int) $summary['total_departments'] ?>,
                    <?= (int) $summary['total_users'] ?>,
                    <?= (int) $summary['total_messages'] ?>,
                    <?= (int) $summary['unread_messages'] ?>
                ],
                backgroundColor: [
                    'rgba(25, 195, 125, 0.7)',
                    'rgba(37, 99, 235, 0.7)',
                    'rgba(251, 146, 60, 0.7)',
                    'rgba(239, 68, 68, 0.7)'
                ],
                borderColor: [
                    'rgba(25, 195, 125, 1)',
                    'rgba(37, 99, 235, 1)',
                    'rgba(251, 146, 60, 1)',
                    'rgba(239, 68, 68, 1)'
                ],
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: gridColor
                    },
                    ticks: {
                        color: textColor
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: textColor
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.parsed.y}`;
                        }
                    }
                }
            },
            animation: {
                delay: (context) => {
                    let delay = 0;
                    if (context.type === 'data' && context.mode === 'default') {
                        delay = context.dataIndex * 100 + context.datasetIndex * 50;
                    }
                    return delay;
                }
            }
        }
    });
    
    // Listen for dark mode changes
    window.addEventListener('darkModeChanged', function(e) {
        const newIsDarkMode = e.detail.isDarkMode;
        const newTextColor = newIsDarkMode ? '#f1f5f9' : '#1e293b';
        const newGridColor = newIsDarkMode ? 'rgba(148, 163, 184, 0.1)' : 'rgba(148, 163, 184, 0.2)';
        
        // Update user chart
        userChart.options.plugins.legend.labels.color = newTextColor;
        userChart.update();
        
        // Update activity chart
        activityChart.options.scales.y.grid.color = newGridColor;
        activityChart.options.scales.y.ticks.color = newTextColor;
        activityChart.options.scales.x.ticks.color = newTextColor;
        activityChart.update();
    });
});
</script>
</body>
</html>
