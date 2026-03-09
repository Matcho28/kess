<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/chat.php';
require_once __DIR__ . '/../../includes/upload.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'message' => 'Method not allowed.',
    ], 405);
}

$currentUserId = getCurrentUserId();
$currentUserRole = getCurrentUserRole();
$receiverId = (int) ($_POST['receiver_id'] ?? ($_POST['recipient_id'] ?? 0));
$messageBody = trim((string) ($_POST['message_text'] ?? ($_POST['message'] ?? '')));

if ($receiverId <= 0 || $receiverId === $currentUserId) {
    jsonResponse([
        'success' => false,
        'message' => 'Invalid receiver.',
    ], 422);
}

if (strlen($messageBody) > 5000) {
    jsonResponse([
        'success' => false,
        'message' => 'Message is too long. Maximum length is 5000 characters.',
    ], 422);
}

$recipient = getChatUserById($currentUserId, $currentUserRole, $receiverId);

if ($recipient === null) {
    jsonResponse([
        'success' => false,
        'message' => 'Recipient not found.',
    ], 404);
}

$attachmentMeta = null;
$hasAttachment = isset($_FILES['attachment'])
    && (int) ($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

if ($hasAttachment) {
    try {
        $attachmentMeta = storeChatAttachment($_FILES['attachment']);
    } catch (RuntimeException $exception) {
        jsonResponse([
            'success' => false,
            'message' => $exception->getMessage(),
        ], 422);
    }
}

if ($messageBody === '' && $attachmentMeta === null) {
    jsonResponse([
        'success' => false,
        'message' => 'Type a message or attach a file before sending.',
    ], 422);
}

try {
    $savedMessage = createChatMessage($currentUserId, $receiverId, $messageBody, $attachmentMeta);
} catch (Throwable $exception) {
    jsonResponse([
        'success' => false,
        'message' => 'Unable to send the message right now. Please try again.',
    ], 500);
}

jsonResponse([
    'success' => true,
    'message' => 'Message sent successfully.',
    'chat_message' => $savedMessage,
], 201);
