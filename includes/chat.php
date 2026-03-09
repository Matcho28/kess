<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

/**
 * Returns the allowed chat partner role for the current account role.
 */
function getExpectedChatPartnerRole(string $currentUserRole): string
{
    if ($currentUserRole === ROLE_SUPER_ADMIN) {
        return ROLE_DEPARTMENT_ADMIN;
    }

    if ($currentUserRole === ROLE_DEPARTMENT_ADMIN) {
        return ROLE_SUPER_ADMIN;
    }

    return '';
}

/**
 * Returns one active chat user with department details when allowed by role.
 */
function getChatUserById(int $currentUserId, string $currentUserRole, int $targetUserId): ?array
{
    $partnerRole = getExpectedChatPartnerRole($currentUserRole);

    if ($partnerRole === '' || $targetUserId <= 0 || $targetUserId === $currentUserId) {
        return null;
    }

    $sql = 'SELECT u.id, u.full_name, u.email, u.department_id, d.name AS department_name
            FROM users u
            INNER JOIN departments d ON d.id = u.department_id
            WHERE u.id = ?
              AND u.id <> ?
              AND u.is_active = 1
              AND u.role = ?
            LIMIT 1';

    $statement = db()->prepare($sql);
    $statement->execute([$targetUserId, $currentUserId, $partnerRole]);
    $user = $statement->fetch();

    if (!$user) {
        return null;
    }

    return [
        'id' => (int) $user['id'],
        'full_name' => (string) $user['full_name'],
        'email' => (string) $user['email'],
        'department_id' => (int) $user['department_id'],
        'department_name' => (string) $user['department_name'],
    ];
}

/**
 * Returns conversation list for the authenticated user.
 */
function getConversationListForUser(int $currentUserId, string $currentUserRole): array
{
    $partnerRole = getExpectedChatPartnerRole($currentUserRole);

    if ($partnerRole === '') {
        return [];
    }

    $sql = 'SELECT
                u.id,
                u.full_name,
                u.email,
                d.name AS department_name,
                (
                    SELECT COUNT(*)
                    FROM messages m_unread
                    WHERE m_unread.sender_id = u.id
                      AND m_unread.receiver_id = ?
                      AND m_unread.read_at IS NULL
                ) AS unread_count,
                (
                    SELECT m_last.message_text
                    FROM messages m_last
                    WHERE (m_last.sender_id = ? AND m_last.receiver_id = u.id)
                       OR (m_last.sender_id = u.id AND m_last.receiver_id = ?)
                    ORDER BY m_last.created_at DESC, m_last.id DESC
                    LIMIT 1
                ) AS last_message_body,
                (
                    SELECT m_last.created_at
                    FROM messages m_last
                    WHERE (m_last.sender_id = ? AND m_last.receiver_id = u.id)
                       OR (m_last.sender_id = u.id AND m_last.receiver_id = ?)
                    ORDER BY m_last.created_at DESC, m_last.id DESC
                    LIMIT 1
                ) AS last_message_time
            FROM users u
            INNER JOIN departments d ON d.id = u.department_id
            WHERE u.id <> ?
              AND u.is_active = 1
              AND u.role = ?
            ORDER BY COALESCE(last_message_time, \'1970-01-01 00:00:00\') DESC, u.full_name ASC';

    $statement = db()->prepare($sql);
    $statement->execute([
        $currentUserId,
        $currentUserId,
        $currentUserId,
        $currentUserId,
        $currentUserId,
        $currentUserId,
        $partnerRole,
    ]);

    $rows = $statement->fetchAll();
    $conversations = [];

    foreach ($rows as $row) {
        $lastMessageTime = $row['last_message_time'] !== null ? (string) $row['last_message_time'] : null;
        $preview = trim((string) ($row['last_message_body'] ?? ''));

        if ($preview === '') {
            $preview = $lastMessageTime !== null ? '[File attachment]' : 'No messages yet';
        }

        $conversations[] = [
            'user_id' => (int) $row['id'],
            'full_name' => (string) $row['full_name'],
            'email' => (string) $row['email'],
            'department_name' => (string) $row['department_name'],
            'unread_count' => (int) $row['unread_count'],
            'last_message_preview' => $preview,
            'last_message_time' => $lastMessageTime,
        ];
    }

    return $conversations;
}

/**
 * Returns total unread messages for notification badge.
 */
function getUnreadMessageCount(int $currentUserId): int
{
    $statement = db()->prepare('SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND read_at IS NULL');
    $statement->execute([$currentUserId]);

    return (int) $statement->fetchColumn();
}

/**
 * Marks unread messages from one chat partner as read.
 */
function markConversationRead(int $currentUserId, int $otherUserId): int
{
    $sql = 'UPDATE messages
            SET read_at = NOW()
            WHERE sender_id = ?
              AND receiver_id = ?
              AND read_at IS NULL';

    $statement = db()->prepare($sql);
    $statement->execute([$otherUserId, $currentUserId]);

    return $statement->rowCount();
}

/**
 * Returns complete message history between two users.
 */
function getMessagesForConversation(int $currentUserId, int $otherUserId): array
{
    $sql = 'SELECT
                m.id,
                m.sender_id,
                m.receiver_id,
                m.message_text,
                m.created_at,
                m.read_at,
                mf.id AS attachment_id,
                mf.original_name,
                mf.file_size,
                mf.mime_type
            FROM messages m
            LEFT JOIN message_files mf ON mf.message_id = m.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?)
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC, m.id ASC, mf.id ASC';

    $statement = db()->prepare($sql);
    $statement->execute([$currentUserId, $otherUserId, $otherUserId, $currentUserId]);

    return hydrateMessageRows($statement->fetchAll());
}

/**
 * Saves one message and optional file attachment.
 *
 * @throws Throwable when database save fails
 */
function createChatMessage(int $senderId, int $receiverId, string $body, ?array $attachmentMeta = null): array
{
    $cleanBody = trim($body);

    if ($cleanBody === '' && $attachmentMeta === null) {
        throw new InvalidArgumentException('Message text or attachment is required.');
    }

    $pdo = db();
    $messageId = 0;

    try {
        $pdo->beginTransaction();

        $messageStatement = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)');
        $messageStatement->execute([
            $senderId,
            $receiverId,
            $cleanBody !== '' ? $cleanBody : null,
        ]);

        $messageId = (int) $pdo->lastInsertId();

        if ($attachmentMeta !== null) {
            $attachmentStatement = $pdo->prepare(
                'INSERT INTO message_files (message_id, original_name, stored_name, file_path, mime_type, file_size)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );

            $attachmentStatement->execute([
                $messageId,
                (string) $attachmentMeta['original_name'],
                (string) $attachmentMeta['stored_name'],
                (string) $attachmentMeta['file_path'],
                (string) $attachmentMeta['mime_type'],
                (int) $attachmentMeta['file_size'],
            ]);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if ($attachmentMeta !== null) {
            $absolutePath = (string) ($attachmentMeta['absolute_path'] ?? '');
            if ($absolutePath !== '' && is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }

        throw $exception;
    }

    $message = getMessageByIdForUser($messageId, $senderId);

    if ($message === null) {
        throw new RuntimeException('Unable to load newly created message.');
    }

    return $message;
}

/**
 * Returns one message if it belongs to the current user.
 */
function getMessageByIdForUser(int $messageId, int $currentUserId): ?array
{
    $sql = 'SELECT
                m.id,
                m.sender_id,
                m.receiver_id,
                m.message_text,
                m.created_at,
                m.read_at,
                mf.id AS attachment_id,
                mf.original_name,
                mf.file_size,
                mf.mime_type
            FROM messages m
            LEFT JOIN message_files mf ON mf.message_id = m.id
            WHERE m.id = ?
              AND (m.sender_id = ? OR m.receiver_id = ?)
            ORDER BY mf.id ASC';

    $statement = db()->prepare($sql);
    $statement->execute([$messageId, $currentUserId, $currentUserId]);

    $messages = hydrateMessageRows($statement->fetchAll());

    return $messages[0] ?? null;
}

/**
 * Converts SQL rows to nested message + attachments JSON-friendly structure.
 */
function hydrateMessageRows(array $rows): array
{
    $messages = [];

    foreach ($rows as $row) {
        $messageId = (int) $row['id'];

        if (!isset($messages[$messageId])) {
            $messages[$messageId] = [
                'id' => $messageId,
                'sender_id' => (int) $row['sender_id'],
                'receiver_id' => (int) $row['receiver_id'],
                'message_text' => (string) ($row['message_text'] ?? ''),
                'created_at' => (string) $row['created_at'],
                'read_at' => $row['read_at'] !== null ? (string) $row['read_at'] : null,
                'attachments' => [],
            ];
        }

        if ($row['attachment_id'] !== null) {
            $attachmentId = (int) $row['attachment_id'];

            $messages[$messageId]['attachments'][] = [
                'id' => $attachmentId,
                'original_name' => (string) $row['original_name'],
                'file_size' => (int) $row['file_size'],
                'mime_type' => (string) $row['mime_type'],
                'download_url' => baseUrl('/chat/download.php?id=' . $attachmentId),
            ];
        }
    }

    return array_values($messages);
}
