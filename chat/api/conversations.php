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

jsonResponse([
    'success' => true,
    'conversations' => getConversationListForUser($currentUserId, $currentUserRole),
    'total_unread' => getUnreadMessageCount($currentUserId),
]);
