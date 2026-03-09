<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/**
 * Returns all departments ordered by name.
 */
function getAllDepartments(): array
{
    $statement = db()->prepare('SELECT id, name, created_at FROM departments ORDER BY name ASC');
    $statement->execute();

    return $statement->fetchAll();
}

/**
 * Returns departments with user totals.
 */
function getDepartmentsWithUserCounts(): array
{
    $sql = 'SELECT
                d.id,
                d.name,
                d.created_at,
                COUNT(u.id) AS user_count,
                SUM(CASE WHEN u.is_active = 1 THEN 1 ELSE 0 END) AS active_user_count
            FROM departments d
            LEFT JOIN users u ON u.department_id = d.id
            GROUP BY d.id, d.name, d.created_at
            ORDER BY d.name ASC';

    $statement = db()->prepare($sql);
    $statement->execute();

    return $statement->fetchAll();
}

/**
 * Returns true if a department name already exists.
 */
function departmentNameExists(string $name, ?int $excludeDepartmentId = null): bool
{
    if ($excludeDepartmentId !== null && $excludeDepartmentId > 0) {
        $statement = db()->prepare('SELECT 1 FROM departments WHERE name = ? AND id <> ? LIMIT 1');
        $statement->execute([$name, $excludeDepartmentId]);
    } else {
        $statement = db()->prepare('SELECT 1 FROM departments WHERE name = ? LIMIT 1');
        $statement->execute([$name]);
    }

    return (bool) $statement->fetchColumn();
}

/**
 * Creates a new department.
 */
function createDepartment(string $name): int
{
    $statement = db()->prepare('INSERT INTO departments (name) VALUES (?)');
    $statement->execute([$name]);

    return (int) db()->lastInsertId();
}

/**
 * Returns dashboard summary metrics.
 */
function getDashboardSummary(int $currentUserId): array
{
    $sql = 'SELECT
                (SELECT COUNT(*) FROM departments) AS total_departments,
                (SELECT COUNT(*) FROM users) AS total_users,
                (SELECT COUNT(*) FROM users WHERE role = ? AND is_active = 1) AS active_super_admins,
                (SELECT COUNT(*) FROM users WHERE role = ? AND is_active = 1) AS active_department_admins,
                (SELECT COUNT(*) FROM messages) AS total_messages,
                (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND read_at IS NULL) AS unread_messages';

    $statement = db()->prepare($sql);
    $statement->execute([
        ROLE_SUPER_ADMIN,
        ROLE_DEPARTMENT_ADMIN,
        $currentUserId,
    ]);

    $row = $statement->fetch();

    return $row ?: [
        'total_departments' => 0,
        'total_users' => 0,
        'active_super_admins' => 0,
        'active_department_admins' => 0,
        'total_messages' => 0,
        'unread_messages' => 0,
    ];
}

/**
 * Returns recent complaint chat messages.
 */
function getRecentComplaintMessages(int $limit = 10): array
{
    $safeLimit = max(1, min($limit, 20));

    $sql = 'SELECT
                m.id,
                m.message_text,
                m.created_at,
                sender.full_name AS sender_name,
                sender.role AS sender_role,
                receiver.full_name AS receiver_name,
                receiver.role AS receiver_role,
                CASE WHEN EXISTS (
                    SELECT 1
                    FROM message_files mf
                    WHERE mf.message_id = m.id
                ) THEN 1 ELSE 0 END AS has_attachment
            FROM messages m
            INNER JOIN users sender ON sender.id = m.sender_id
            INNER JOIN users receiver ON receiver.id = m.receiver_id
            ORDER BY m.created_at DESC, m.id DESC
            LIMIT ' . $safeLimit;

    $statement = db()->prepare($sql);
    $statement->execute();

    return $statement->fetchAll();
}
