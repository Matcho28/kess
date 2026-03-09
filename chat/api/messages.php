<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/chat.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse([
        'success' => false,
        'message' => 'Method not allowed.',
    ], 405);
}

$currentUserId = getCurrentUserId();
$currentUserRole = getCurrentUserRole();
$receiverId = (int) ($_GET['receiver_id'] ?? ($_GET['recipient_id'] ?? 0));

if ($receiverId <= 0 || $receiverId === $currentUserId) {
    jsonResponse([
        'success' => false,
        'message' => 'Invalid receiver.',
    ], 422);
}

$recipient = getChatUserById($currentUserId, $currentUserRole, $receiverId);

if ($recipient === null) {
    jsonResponse([
        'success' => false,
        'message' => 'Recipient not found.',
    ], 404);
}

$markedReadCount = markConversationRead($currentUserId, $receiverId);
$messages = getMessagesForConversation($currentUserId, $receiverId);

jsonResponse([
    'success' => true,
    'recipient' => [
        'id' => (int) $recipient['id'],
        'full_name' => (string) $recipient['full_name'],
        'department_name' => (string) $recipient['department_name'],
        'email' => (string) $recipient['email'],
    ],
    'messages' => $messages,
    'marked_read_count' => $markedReadCount,
    'total_unread' => getUnreadMessageCount($currentUserId),
]);
