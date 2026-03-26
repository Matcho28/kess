<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/calendar.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$user         = getCurrentUser();
$userId       = (int) $user['id'];
$role         = (string) $user['role'];
$departmentId = (int) $user['department_id'];

$rawStart = trim((string) ($_GET['start'] ?? ''));
$rawEnd   = trim((string) ($_GET['end']   ?? ''));

if ($rawStart === '' || $rawEnd === '') {
    jsonResponse(['success' => false, 'message' => 'start and end parameters are required.'], 422);
}

// FullCalendar sends ISO-8601 timestamps; extract date portion.
$startDate = DateTime::createFromFormat('Y-m-d', substr($rawStart, 0, 10));
$endDate   = DateTime::createFromFormat('Y-m-d', substr($rawEnd,   0, 10));

if ($startDate === false || $endDate === false) {
    jsonResponse(['success' => false, 'message' => 'Invalid date range. Expected YYYY-MM-DD.'], 422);
}

$startStr = $startDate->format('Y-m-d');
$endStr   = $endDate->format('Y-m-d');

if ($endStr < $startStr) {
    jsonResponse(['success' => false, 'message' => 'end must be on or after start.'], 422);
}

try {
    $events = getCalendarEvents($userId, $role, $departmentId, $startStr, $endStr);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to retrieve calendar events.'], 500);
}

jsonResponse(['success' => true, 'events' => $events]);
