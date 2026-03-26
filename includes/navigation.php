<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

/**
 * Returns role-specific navigation links.
 */
function getNavigationIconSvg(string $key): string
{
    $icons = [
        'dashboard' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 13.2c0-.38.15-.74.42-1.01l7.57-7.57a1.43 1.43 0 0 1 2.02 0l7.57 7.57c.27.27.42.63.42 1.01v6.37A1.43 1.43 0 0 1 19.57 21H4.43A1.43 1.43 0 0 1 3 19.57V13.2Zm2 .59V19h4.5v-4.5h5V19H19v-5.21l-7-7-7 7Z" fill="currentColor"/></svg>',
        'logs' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 3h18v2H3V3Zm0 4h18v2H3V7Zm0 4h18v2H3v-2Zm0 4h12v2H3v-2Zm0 4h8v2H3v-2Z" fill="currentColor"/></svg>',
        'chats' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5.5 4A3.5 3.5 0 0 0 2 7.5v7A3.5 3.5 0 0 0 5.5 18H7v3.09c0 .28.15.53.4.66.24.13.54.11.76-.05L12.83 18H18.5a3.5 3.5 0 0 0 3.5-3.5v-7A3.5 3.5 0 0 0 18.5 4h-13Zm0 2h13A1.5 1.5 0 0 1 20 7.5v7a1.5 1.5 0 0 1-1.5 1.5h-6.02a1 1 0 0 0-.62.22L9 18.47V17a1 1 0 0 0-1-1H5.5A1.5 1.5 0 0 1 4 14.5v-7A1.5 1.5 0 0 1 5.5 6Z" fill="currentColor"/></svg>',
        'users' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M16 11a4 4 0 1 0-3.99-4A4 4 0 0 0 16 11Zm0-6a2 2 0 1 1-2 2 2 2 0 0 1 2-2ZM8 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0-6a2 2 0 1 1-2 2 2 2 0 0 1 2-2Zm8 7c-2.67 0-8 1.34-8 4v1a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-1c0-2.66-5.33-4-8-4Zm-6 4c.38-.88 2.74-2 6-2s5.62 1.12 6 2H10Zm-2-3c-2.33 0-7 1.17-7 3.5V19a1 1 0 0 0 2 0v-1.5c0-.93 2.33-1.5 5-1.5a1 1 0 0 0 0-2Z" fill="currentColor"/></svg>',
        'departments' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 3h9a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm1 2v14h7V5H5Zm11 3h4a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Zm1 2v9h2v-9h-2ZM7 8h3v2H7V8Zm0 4h3v2H7v-2Zm0 4h3v2H7v-2Z" fill="currentColor"/></svg>',
        'profile' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 12a4.5 4.5 0 1 0-4.5-4.5A4.5 4.5 0 0 0 12 12Zm0-7a2.5 2.5 0 1 1-2.5 2.5A2.5 2.5 0 0 1 12 5Zm0 8c-4.05 0-7.5 2.13-7.5 5v1a1 1 0 0 0 1 1h13a1 1 0 0 0 1-1v-1c0-2.87-3.45-5-7.5-5Zm-5.43 5c.55-1.49 2.76-3 5.43-3s4.88 1.51 5.43 3H6.57Z" fill="currentColor"/></svg>',
        'logout' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10 4a1 1 0 0 1 1-1h6a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3h-6a1 1 0 0 1 0-2h6a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-6a1 1 0 0 1-1-1Zm1.71 13.71a1 1 0 0 1-1.42-1.42L12.59 14H5a1 1 0 0 1 0-2h7.59l-2.3-2.29a1 1 0 1 1 1.42-1.42l4 4a1 1 0 0 1 0 1.42l-4 4Z" fill="currentColor"/></svg>',
    ];

    return $icons[$key] ?? '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8" fill="currentColor"/></svg>';
}

/**
 * Returns role-specific navigation items with grouped structure.
 */
function getNavigationItemsForRole(string $role): array
{
    if ($role === ROLE_SUPER_ADMIN) {
        return [
            // DASHBOARD & GENERAL (Top Level)
            [
                'section' => 'DASHBOARD & GENERAL',
                'items' => [
                    ['key' => 'dashboard', 'label' => 'Dashboard', 'path' => '/dashboard/index.php'],
                    ['key' => 'logs', 'label' => 'Logs', 'path' => '/logs/index.php'],
                    ['key' => 'chats', 'label' => 'Chats', 'path' => '/chat/index.php'],
                    ['key' => 'users', 'label' => 'User Management', 'path' => '/users/index.php'],
                    ['key' => 'departments', 'label' => 'Departments', 'path' => '/departments/index.php'],
                    ['key' => 'profile', 'label' => 'Profile', 'path' => '/profile/index.php']
                ]
            ],
            // SYSTEM MODULES
            [
                'section' => 'SYSTEM MODULES',
                'items' => []
            ],
            // ADMINISTRATIVE SUPPORT SERVICES
            [
                'section' => 'ADMINISTRATIVE SUPPORT SERVICES',
                'items' => [
                    ['key' => 'request-form', 'label' => 'Request Form', 'path' => '#'],
                    ['key' => 'request-records', 'label' => 'Request Records (view and print)', 'path' => '#']
                ]
            ],
            // BROADCAST UNIT
            [
                'section' => 'BROADCAST UNIT',
                'items' => [
                    ['key' => 'interactive-calendar', 'label' => 'Interactive Calendar', 'path' => '#']
                ]
            ],
            // RESEARCH UNIT
            [
                'section' => 'RESEARCH UNIT',
                'items' => [
                    ['key' => 'client-satisfaction', 'label' => 'Client Satisfaction Form', 'path' => '#']
                ]
            ],
            // PRINT AND OTHER MEDIA SERVICES UNIT
            [
                'section' => 'PRINT AND OTHER MEDIA SERVICES UNIT',
                'items' => [
                    ['key' => 'print-calendar', 'label' => 'Interactive Calendar', 'path' => '#']
                ]
            ],
            // LOGOUT
            [
                'section' => '',
                'items' => [
                    ['key' => 'logout', 'label' => 'Logout', 'path' => '/auth/logout.php']
                ]
            ]
        ];
    }

    return [
        // GENERAL (for Department Admin)
        [
            'section' => 'GENERAL',
            'items' => [
                ['key' => 'chats', 'label' => 'Chats', 'path' => '/chat/index.php'],
                ['key' => 'profile', 'label' => 'Profile', 'path' => '/profile/index.php']
            ]
        ],
        // LOGOUT
        [
            'section' => '',
            'items' => [
                ['key' => 'logout', 'label' => 'Logout', 'path' => '/auth/logout.php']
            ]
        ]
    ];
}

/**
 * Renders the left application navigation sidebar with grouped sections.
 */
function renderNavigationSidebar(string $activeKey): void
{
    $currentUser = getCurrentUser();
    $currentRole = getCurrentUserRole();
    $roleLabel = $currentRole === ROLE_SUPER_ADMIN ? 'Super Admin' : 'Department Admin';

    $sections = getNavigationItemsForRole($currentRole);

    echo '<aside class="main-nav" id="appSidebar" aria-expanded="true">';
    echo '<div class="main-nav-topbar">';
    echo '<div class="main-nav-header">';
    echo '<div class="main-nav-brand-wrap">';
    echo '<div class="main-nav-brand-mark">K</div>';
    echo '<div class="main-nav-brand-copy">';
    echo '<div class="main-nav-brand">KESS Admin</div>';
    echo '<div class="main-nav-role">' . e($roleLabel) . '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '<button type="button" class="main-nav-toggle" id="appSidebarToggle" aria-label="Toggle sidebar" aria-controls="appSidebar" aria-expanded="true">';
    echo '<span></span><span></span><span></span>';
    echo '</button>';
    echo '</div>';

    echo '<div class="main-nav-body">';
    echo '<nav class="main-nav-links" aria-label="Main navigation">';

    foreach ($sections as $section) {
        // Skip empty sections
        if (empty($section['items'])) {
            continue;
        }

        // Render section title if exists
        if (!empty($section['section'])) {
            echo '<div class="nav-section">';
            echo '<div class="nav-section-title">' . e($section['section']) . '</div>';
        }

        // Render section items
        foreach ($section['items'] as $item) {
            $isActive = $item['key'] === $activeKey;
            $classes = 'main-nav-link' . ($isActive ? ' active' : '');
            $icon = getNavigationIconSvg((string) $item['key']);

            echo '<a class="' . e($classes) . '" href="' . e(baseUrl($item['path'])) . '" data-tooltip="' . e((string) $item['label']) . '" title="' . e((string) $item['label']) . '">';
            echo '<span class="main-nav-link-icon">' . $icon . '</span>';
            echo '<span class="main-nav-link-label">' . e($item['label']) . '</span>';
            echo '</a>';
        }

        // Close section if opened
        if (!empty($section['section'])) {
            echo '</div>';
        }
    }

    echo '</nav>';
    echo '</div>';
    echo '</aside>';
}
