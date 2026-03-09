<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$attachmentId = (int) ($_GET['id'] ?? 0);
$currentUserId = getCurrentUserId();

if ($attachmentId <= 0) {
    http_response_code(404);
    exit('Attachment not found.');
}

$sql = 'SELECT mf.original_name, mf.file_path, mf.mime_type, mf.file_size
        FROM message_files mf
        INNER JOIN messages m ON m.id = mf.message_id
        WHERE mf.id = ?
          AND (m.sender_id = ? OR m.receiver_id = ?)
        LIMIT 1';

$statement = db()->prepare($sql);
$statement->execute([$attachmentId, $currentUserId, $currentUserId]);
$attachment = $statement->fetch();

if (!$attachment) {
    http_response_code(404);
    exit('Attachment not found or access denied.');
}

$relativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $attachment['file_path']);
$absolutePath = PROJECT_ROOT . DIRECTORY_SEPARATOR . ltrim($relativePath, DIRECTORY_SEPARATOR);

if (!is_file($absolutePath)) {
    http_response_code(404);
    exit('Stored attachment is missing on server.');
}

$downloadName = str_replace(['"', "\r", "\n"], '', (string) $attachment['original_name']);
$mimeType = (string) $attachment['mime_type'];

if ($mimeType === '') {
    $mimeType = 'application/octet-stream';
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . (string) filesize($absolutePath));
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (ob_get_level() > 0) {
    ob_end_clean();
}

readfile($absolutePath);
exit;
