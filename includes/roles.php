<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/users.php';

/**
 * Returns role statistics including user counts and activity.
 */
function getRoleStatistics(): array
{
    $sql = 'SELECT 
                role,
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
            FROM users 
            GROUP BY role';
    
    $statement = db()->prepare($sql);
    $statement->execute();
    $roleStats = $statement->fetchAll();
    
    $statistics = [
        'super_admin' => ['total' => 0, 'active' => 0],
        'department_admin' => ['total' => 0, 'active' => 0],
        'total_users' => 0,
        'recent_changes' => 0
    ];
    
    foreach ($roleStats as $stat) {
        $role = $stat['role'];
        if (isset($statistics[$role])) {
            $statistics[$role]['total'] = (int) $stat['total'];
            $statistics[$role]['active'] = (int) $stat['active'];
        }
        $statistics['total_users'] += (int) $stat['total'];
    }
    
    // Get recent changes count (last 7 days)
    $recentSql = 'SELECT COUNT(*) as count FROM role_change_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
    $recentStatement = db()->prepare($recentSql);
    $recentStatement->execute();
    $recentResult = $recentStatement->fetch();
    $statistics['recent_changes'] = (int) $recentResult['count'];
    
    return $statistics;
}

/**
 * Returns users grouped by role for management interface.
 */
function getUsersByRole(): array
{
    $sql = 'SELECT 
                u.id,
                u.full_name,
                u.email,
                u.role,
                u.is_active,
                d.name as department_name
            FROM users u
            INNER JOIN departments d ON d.id = u.department_id
            ORDER BY u.role, u.full_name';
    
    $statement = db()->prepare($sql);
    $statement->execute();
    $users = $statement->fetchAll();
    
    $groupedUsers = [
        ROLE_SUPER_ADMIN => [],
        ROLE_DEPARTMENT_ADMIN => []
    ];
    
    foreach ($users as $user) {
        $role = $user['role'];
        if (isset($groupedUsers[$role])) {
            $groupedUsers[$role][] = $user;
        }
    }
    
    return $groupedUsers;
}

/**
 * Returns recent role changes with user details.
 */
function getRecentRoleChanges(int $limit = 10): array
{
    $sql = 'SELECT 
                rcl.created_at,
                rcl.old_role,
                rcl.new_role,
                u.full_name as user_full_name,
                u.email as user_email,
                changer.full_name as changed_by_name,
                changer.email as changed_by_email
            FROM role_change_log rcl
            INNER JOIN users u ON u.id = rcl.user_id
            INNER JOIN users changer ON changer.id = rcl.changed_by_user_id
            ORDER BY rcl.created_at DESC
            LIMIT ?';
    
    $statement = db()->prepare($sql);
    $statement->execute([$limit]);
    return $statement->fetchAll();
}

/**
 * Performs bulk role assignment with validation and logging.
 */
function bulkAssignRole(array $userIds, string $newRole, int $changedByUserId): int
{
    if (!isValidUserRole($newRole)) {
        throw new InvalidArgumentException('Invalid role specified.');
    }
    
    if (empty($userIds)) {
        throw new InvalidArgumentException('No users selected for role assignment.');
    }
    
    $updatedCount = 0;
    $currentUserId = $changedByUserId;
    
    // Start transaction for atomic operations
    db()->beginTransaction();
    
    try {
        foreach ($userIds as $userId) {
            $userId = (int) $userId;
            
            // Skip if user doesn't exist or is trying to change their own role
            if ($userId === $currentUserId) {
                continue;
            }
            
            // Get current user data
            $userSql = 'SELECT role, is_active FROM users WHERE id = ? LIMIT 1';
            $userStatement = db()->prepare($userSql);
            $userStatement->execute([$userId]);
            $currentUser = $userStatement->fetch();
            
            if (!$currentUser) {
                continue; // Skip non-existent users
            }
            
            $oldRole = $currentUser['role'];
            
            // Skip if role is the same
            if ($oldRole === $newRole) {
                continue;
            }
            
            // Prevent removing the last Super Admin
            if ($oldRole === ROLE_SUPER_ADMIN && $newRole !== ROLE_SUPER_ADMIN) {
                $superAdminCountSql = 'SELECT COUNT(*) as count FROM users WHERE role = ? AND is_active = 1';
                $superAdminStatement = db()->prepare($superAdminCountSql);
                $superAdminStatement->execute([ROLE_SUPER_ADMIN]);
                $superAdminCount = (int) $superAdminStatement->fetch()['count'];
                
                if ($superAdminCount <= 1) {
                    continue; // Skip to prevent removing last Super Admin
                }
            }
            
            // Update user role
            $updateSql = 'UPDATE users SET role = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?';
            $updateStatement = db()->prepare($updateSql);
            $updateResult = $updateStatement->execute([$newRole, $userId]);
            
            if ($updateResult) {
                // Log the role change
                logRoleChange($userId, $oldRole, $newRole, $currentUserId);
                $updatedCount++;
            }
        }
        
        db()->commit();
    } catch (Exception $e) {
        db()->rollback();
        throw $e;
    }
    
    return $updatedCount;
}

/**
 * Logs role changes to the audit trail.
 */
function logRoleChange(int $userId, string $oldRole, string $newRole, int $changedByUserId): void
{
    $sql = 'INSERT INTO role_change_log (user_id, old_role, new_role, changed_by_user_id, created_at) 
            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)';
    
    $statement = db()->prepare($sql);
    $statement->execute([$userId, $oldRole, $newRole, $changedByUserId]);
}

/**
 * Creates the role change log table if it doesn't exist.
 */
function ensureRoleChangeLogTable(): void
{
    $sql = 'CREATE TABLE IF NOT EXISTS role_change_log (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                old_role VARCHAR(50) NOT NULL,
                new_role VARCHAR(50) NOT NULL,
                changed_by_user_id INT UNSIGNED NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_role_change_user (user_id),
                INDEX idx_role_change_date (created_at),
                INDEX idx_role_change_changed_by (changed_by_user_id)
            ) ENGINE=InnoDB';
    
    db()->exec($sql);
}

/**
 * Returns role permission matrix for display.
 */
function getRolePermissions(): array
{
    return [
        ROLE_SUPER_ADMIN => [
            'dashboard' => true,
            'users' => true,
            'departments' => true,
            'roles' => true,
            'chat' => true,
            'profile' => true,
            'logs' => true
        ],
        ROLE_DEPARTMENT_ADMIN => [
            'dashboard' => false,
            'users' => false,
            'departments' => false,
            'roles' => false,
            'chat' => true,
            'profile' => true,
            'logs' => false
        ]
    ];
}

/**
 * Validates if a user can be assigned a specific role.
 */
function canAssignRole(int $targetUserId, string $newRole, int $currentUserId): bool
{
    // Cannot change own role
    if ($targetUserId === $currentUserId) {
        return false;
    }
    
    // Get target user's current role
    $sql = 'SELECT role FROM users WHERE id = ? LIMIT 1';
    $statement = db()->prepare($sql);
    $statement->execute([$targetUserId]);
    $targetUser = $statement->fetch();
    
    if (!$targetUser) {
        return false;
    }
    
    $oldRole = $targetUser['role'];
    
    // If roles are the same, no change needed
    if ($oldRole === $newRole) {
        return false;
    }
    
    // Prevent removing the last Super Admin
    if ($oldRole === ROLE_SUPER_ADMIN && $newRole !== ROLE_SUPER_ADMIN) {
        $superAdminCountSql = 'SELECT COUNT(*) as count FROM users WHERE role = ? AND is_active = 1';
        $superAdminStatement = db()->prepare($superAdminCountSql);
        $superAdminStatement->execute([ROLE_SUPER_ADMIN]);
        $superAdminCount = (int) $superAdminStatement->fetch()['count'];
        
        if ($superAdminCount <= 1) {
            return false;
        }
    }
    
    return true;
}

// Initialize the role change log table
ensureRoleChangeLogTable();
