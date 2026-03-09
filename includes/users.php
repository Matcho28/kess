<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/**
 * Returns true when role is allowed in the system.
 */
function isValidUserRole(string $role): bool
{
    return in_array($role, [ROLE_SUPER_ADMIN, ROLE_DEPARTMENT_ADMIN], true);
}

/**
 * Converts role code to display label.
 */
function getUserRoleLabel(string $role): string
{
    if ($role === ROLE_SUPER_ADMIN) {
        return 'Super Admin';
    }

    return 'Department Admin';
}

/**
 * Lists users with department details for admin management screens.
 */
function getAllUsersForManagement(): array
{
    $sql = 'SELECT
                u.id,
                u.full_name,
                u.email,
                u.role,
                u.is_active,
                u.created_at,
                u.department_id,
                d.name AS department_name
            FROM users u
            INNER JOIN departments d ON d.id = u.department_id
            ORDER BY
                CASE WHEN u.role = ? THEN 0 ELSE 1 END,
                u.full_name ASC';

    $statement = db()->prepare($sql);
    $statement->execute([ROLE_SUPER_ADMIN]);

    return $statement->fetchAll();
}

/**
 * Returns one user by id for edit screens.
 */
function getUserForManagementById(int $userId): ?array
{
    $sql = 'SELECT
                u.id,
                u.full_name,
                u.email,
                u.role,
                u.is_active,
                u.department_id,
                d.name AS department_name
            FROM users u
            INNER JOIN departments d ON d.id = u.department_id
            WHERE u.id = ?
            LIMIT 1';

    $statement = db()->prepare($sql);
    $statement->execute([$userId]);

    $row = $statement->fetch();

    return $row ?: null;
}

/**
 * Checks whether an email already exists in users table.
 */
function userEmailExists(string $email, ?int $excludeUserId = null): bool
{
    if ($excludeUserId !== null && $excludeUserId > 0) {
        $sql = 'SELECT 1 FROM users WHERE email = ? AND id <> ? LIMIT 1';
        $statement = db()->prepare($sql);
        $statement->execute([$email, $excludeUserId]);
    } else {
        $sql = 'SELECT 1 FROM users WHERE email = ? LIMIT 1';
        $statement = db()->prepare($sql);
        $statement->execute([$email]);
    }

    return (bool) $statement->fetchColumn();
}

/**
 * Creates a new user account.
 */
function createManagedUser(
    int $departmentId,
    string $fullName,
    string $email,
    string $role,
    string $password,
    int $isActive
): int {
    if (!isValidUserRole($role)) {
        throw new InvalidArgumentException('Selected role is invalid.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $sql = 'INSERT INTO users (department_id, full_name, email, password_hash, role, is_active)
            VALUES (?, ?, ?, ?, ?, ?)';

    $statement = db()->prepare($sql);
    $statement->execute([
        $departmentId,
        $fullName,
        $email,
        $passwordHash,
        $role,
        $isActive,
    ]);

    return (int) db()->lastInsertId();
}

/**
 * Updates an existing user account.
 */
function updateManagedUser(
    int $userId,
    int $departmentId,
    string $fullName,
    string $email,
    string $role,
    ?string $newPassword,
    int $isActive
): void {
    if (!isValidUserRole($role)) {
        throw new InvalidArgumentException('Selected role is invalid.');
    }

    $passwordToUse = trim((string) $newPassword);

    if ($passwordToUse !== '') {
        $passwordHash = password_hash($passwordToUse, PASSWORD_DEFAULT);

        $sql = 'UPDATE users
                SET department_id = ?,
                    full_name = ?,
                    email = ?,
                    role = ?,
                    is_active = ?,
                    password_hash = ?
                WHERE id = ?';

        $statement = db()->prepare($sql);
        $statement->execute([
            $departmentId,
            $fullName,
            $email,
            $role,
            $isActive,
            $passwordHash,
            $userId,
        ]);

        return;
    }

    $sql = 'UPDATE users
            SET department_id = ?,
                full_name = ?,
                email = ?,
                role = ?,
                is_active = ?
            WHERE id = ?';

    $statement = db()->prepare($sql);
    $statement->execute([
        $departmentId,
        $fullName,
        $email,
        $role,
        $isActive,
        $userId,
    ]);
}

/**
 * Toggles account active state.
 */
function updateUserActiveState(int $userId, int $isActive): void
{
    $statement = db()->prepare('UPDATE users SET is_active = ? WHERE id = ?');
    $statement->execute([$isActive, $userId]);
}
