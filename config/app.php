<?php
declare(strict_types=1);

// Application-wide constants and runtime settings.
date_default_timezone_set('Asia/Manila');

define('PROJECT_ROOT', dirname(__DIR__));

$projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..') ?: '');
$documentRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
$baseUrl = '';

if ($projectRoot !== '' && $documentRoot !== '' && strpos($projectRoot, $documentRoot) === 0) {
    $baseUrl = str_replace($documentRoot, '', $projectRoot);
}

$baseUrl = rtrim($baseUrl, '/');
define('BASE_URL', $baseUrl === '/' ? '' : $baseUrl);

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'kiss');
define('DB_USER', 'root');
define('DB_PASS', '');

define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_DEPARTMENT_ADMIN', 'department_admin');

define('MAX_UPLOAD_SIZE_BYTES', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_ATTACHMENT_EXTENSIONS', [
    'pdf',
    'docx',
    'jpg',
    'jpeg',
    'png',
    'gif',
    'webp',
]);
define('ALLOWED_ATTACHMENT_MIME_TYPES', [
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/zip',
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
]);

define('UPLOAD_ROOT', PROJECT_ROOT . DIRECTORY_SEPARATOR . 'uploads');
define('CHAT_UPLOAD_DIR', UPLOAD_ROOT . DIRECTORY_SEPARATOR . 'chat_files');
