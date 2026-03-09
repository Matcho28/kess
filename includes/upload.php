<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

/**
 * Stores one chat attachment on disk and returns metadata for database saving.
 *
 * @throws RuntimeException when validation or upload fails
 */
function storeChatAttachment(array $file): array
{
    if (!isset($file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Attachment upload failed.');
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > MAX_UPLOAD_SIZE_BYTES) {
        throw new RuntimeException('Attachment must be between 1 byte and 10 MB.');
    }

    $originalName = basename((string) ($file['name'] ?? 'file'));
    $safeOriginalName = preg_replace('/[^A-Za-z0-9._ -]/', '_', $originalName) ?: 'file';

    $extension = strtolower((string) pathinfo($safeOriginalName, PATHINFO_EXTENSION));
    if ($extension === '' || !in_array($extension, ALLOWED_ATTACHMENT_EXTENSIONS, true)) {
        throw new RuntimeException('Unsupported file type. Allowed: PDF, DOCX, JPG, PNG, GIF, WEBP.');
    }

    if (!is_dir(CHAT_UPLOAD_DIR) && !mkdir(CHAT_UPLOAD_DIR, 0775, true) && !is_dir(CHAT_UPLOAD_DIR)) {
        throw new RuntimeException('Cannot create upload folder.');
    }

    $storedName = bin2hex(random_bytes(16));
    if ($extension !== '') {
        $storedName .= '.' . $extension;
    }

    $absolutePath = CHAT_UPLOAD_DIR . DIRECTORY_SEPARATOR . $storedName;
    $tmpName = (string) ($file['tmp_name'] ?? '');

    if (!is_uploaded_file($tmpName)) {
        throw new RuntimeException('Invalid uploaded file.');
    }

    if (!move_uploaded_file($tmpName, $absolutePath)) {
        throw new RuntimeException('Unable to move uploaded file.');
    }

    $mimeType = 'application/octet-stream';

    if (function_exists('finfo_open')) {
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($fileInfo !== false) {
            $detectedType = finfo_file($fileInfo, $absolutePath);
            if (is_string($detectedType) && $detectedType !== '') {
                $mimeType = $detectedType;
            }

            finfo_close($fileInfo);
        }
    } elseif (function_exists('mime_content_type')) {
        $detectedType = mime_content_type($absolutePath);
        if (is_string($detectedType) && $detectedType !== '') {
            $mimeType = $detectedType;
        }
    }

    $fallbackMimeAllowed = $mimeType === 'application/octet-stream'
        && in_array($extension, ['pdf', 'docx'], true);

    if (!in_array($mimeType, ALLOWED_ATTACHMENT_MIME_TYPES, true) && !$fallbackMimeAllowed) {
        @unlink($absolutePath);
        throw new RuntimeException('Detected file content is not allowed.');
    }

    return [
        'original_name' => $safeOriginalName,
        'stored_name' => $storedName,
        'file_path' => 'uploads/chat_files/' . $storedName,
        'absolute_path' => $absolutePath,
        'mime_type' => $mimeType,
        'file_size' => $size,
    ];
}
