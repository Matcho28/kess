<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/calendar.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$user         = getCurrentUser();
$userId       = (int) $user['id'];
$departmentId = (int) $user['department_id'];
$role         = (string) $user['role'];

// Accept both application/json and multipart/form-data
$contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
$isJson      = str_contains($contentType, 'application/json');

if ($isJson) {
    $raw  = (string) file_get_contents('php://input');
    $body = json_decode($raw, true);
    if (!is_array($body)) {
        jsonResponse(['success' => false, 'message' => 'Invalid JSON body.'], 400);
    }
} else {
    $body = $_POST;
}

$title       = trim((string) ($body['title']       ?? ''));
$description = trim((string) ($body['description'] ?? ''));
$eventDate   = trim((string) ($body['event_date']  ?? ''));
$type        = trim((string) ($body['type']        ?? 'general'));
$metaRaw     = $body['metadata'] ?? null;

// --- Validation ---

if ($title === '') {
    jsonResponse(['success' => false, 'message' => 'Event title is required.'], 422);
}

if (mb_strlen($title, 'UTF-8') > 255) {
    jsonResponse(['success' => false, 'message' => 'Title must not exceed 255 characters.'], 422);
}

if ($eventDate === '') {
    jsonResponse(['success' => false, 'message' => 'Event date is required.'], 422);
}

$parsedDate = DateTime::createFromFormat('Y-m-d', $eventDate);
if ($parsedDate === false || $parsedDate->format('Y-m-d') !== $eventDate) {
    jsonResponse(['success' => false, 'message' => 'A valid event date (YYYY-MM-DD) is required.'], 422);
}

$allowedTypes = ['broadcast', 'print', 'general'];
if (!in_array($type, $allowedTypes, true)) {
    jsonResponse(['success' => false, 'message' => 'Invalid event type. Must be broadcast, print, or general.'], 422);
}

if ($type === 'broadcast' && $role !== ROLE_SUPER_ADMIN) {
    jsonResponse(['success' => false, 'message' => 'Only super admins may create broadcast events.'], 403);
}

if (mb_strlen($description, 'UTF-8') > 5000) {
    jsonResponse(['success' => false, 'message' => 'Description must not exceed 5000 characters.'], 422);
}

// Parse optional metadata
$metadata = null;
if ($metaRaw !== null && $metaRaw !== '') {
    if (is_array($metaRaw)) {
        $metadata = $metaRaw;
    } elseif (is_string($metaRaw)) {
        $decoded  = json_decode($metaRaw, true);
        $metadata = is_array($decoded) ? $decoded : null;
    }
}

// --- Persist ---

try {
    $event = createCalendarEvent(
        $departmentId,
        $userId,
        $eventDate,
        $title,
        $description,
        $type,
        $metadata
    );
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to save the event. Please try again.'], 500);
}

jsonResponse([
    'success' => true,
    'message' => 'Event created successfully.',
    'event'   => $event,
], 201);
