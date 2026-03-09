<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/helpers.php';

/**
 * Checks if a user is currently logged in.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user']['id']);
}

/**
 * Saves authenticated user data to the session.
 */
function loginUser(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'full_name' => (string) $user['full_name'],
        'email' => (string) $user['email'],
        'department_id' => (int) $user['department_id'],
        'department_name' => (string) ($user['department_name'] ?? ''),
        'role' => (string) ($user['role'] ?? ROLE_DEPARTMENT_ADMIN),
    ];
}

/**
 * Returns logged-in user details.
 */
function getCurrentUser(): array
{
    return $_SESSION['user'] ?? [];
}

/**
 * Returns logged-in user id (0 when not logged in).
 */
function getCurrentUserId(): int
{
    return (int) ($_SESSION['user']['id'] ?? 0);
}

/**
 * Returns logged-in user role.
 */
function getCurrentUserRole(): string
{
    return (string) ($_SESSION['user']['role'] ?? '');
}

/**
 * Checks if the current account is a super admin.
 */
function isSuperAdmin(): bool
{
    return getCurrentUserRole() === ROLE_SUPER_ADMIN;
}

/**
 * Checks if the current account is a department admin.
 */
function isDepartmentAdmin(): bool
{
    return getCurrentUserRole() === ROLE_DEPARTMENT_ADMIN;
}

/**
 * Blocks access when user is not authenticated.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('/auth/login.php');
    }
}

/**
 * Blocks access unless current account is a super admin.
 */
function requireSuperAdmin(): void
{
    requireLogin();

    if (!isSuperAdmin()) {
        http_response_code(403);
        exit('Access denied. Super Admin only.');
    }
}

/**
 * Redirects user to the correct landing page based on role.
 */
function redirectAfterLogin(): void
{
    if (isSuperAdmin()) {
        redirect('/dashboard/index.php');
    }

    redirect('/chat/index.php');
}

/**
 * Clears current session and logs the user out.
 */
function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    session_destroy();
}
