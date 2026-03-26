<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

/**
 * Returns FullCalendar-compatible events within a date range.
 *
 * Super admin sees all department events.
 * Department admin sees their own department events plus all broadcast events.
 */
function getCalendarEvents(
    int    $userId,
    string $role,
    int    $departmentId,
    string $start,
    string $end
): array {
    $pdo = db();

    if ($role === ROLE_SUPER_ADMIN) {
        $stmt = $pdo->prepare(
            'SELECT ce.id, ce.title, ce.description, ce.event_date, ce.type, ce.metadata,
                    ce.created_at, u.full_name AS created_by_name, d.name AS department_name
             FROM   calendar_events ce
             INNER JOIN users       u ON u.id = ce.created_by
             INNER JOIN departments d ON d.id = ce.department_id
             WHERE  ce.event_date BETWEEN :start AND :end
             ORDER  BY ce.event_date ASC, ce.created_at ASC'
        );
        $stmt->execute([':start' => $start, ':end' => $end]);
    } else {
        $stmt = $pdo->prepare(
            'SELECT ce.id, ce.title, ce.description, ce.event_date, ce.type, ce.metadata,
                    ce.created_at, u.full_name AS created_by_name, d.name AS department_name
             FROM   calendar_events ce
             INNER JOIN users       u ON u.id = ce.created_by
             INNER JOIN departments d ON d.id = ce.department_id
             WHERE  ce.event_date BETWEEN :start AND :end
               AND  (ce.department_id = :dept_id OR ce.type = \'broadcast\')
             ORDER  BY ce.event_date ASC, ce.created_at ASC'
        );
        $stmt->execute([':start' => $start, ':end' => $end, ':dept_id' => $departmentId]);
    }

    $events = [];
    foreach ($stmt->fetchAll() as $row) {
        $events[] = formatCalendarEvent($row);
    }

    return $events;
}

/**
 * Inserts a new calendar event and returns the formatted record.
 */
function createCalendarEvent(
    int     $departmentId,
    int     $createdBy,
    string  $eventDate,
    string  $title,
    string  $description,
    string  $type,
    ?array  $metadata
): array {
    $pdo      = db();
    $metaJson = ($metadata !== null) ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;

    $stmt = $pdo->prepare(
        'INSERT INTO calendar_events
             (department_id, created_by, event_date, title, description, type, metadata)
         VALUES
             (:department_id, :created_by, :event_date, :title, :description, :type, :metadata)'
    );

    $stmt->execute([
        ':department_id' => $departmentId,
        ':created_by'    => $createdBy,
        ':event_date'    => $eventDate,
        ':title'         => $title,
        ':description'   => $description !== '' ? $description : null,
        ':type'          => $type,
        ':metadata'      => $metaJson,
    ]);

    $newId = (int) $pdo->lastInsertId();

    $fetch = $pdo->prepare(
        'SELECT ce.id, ce.title, ce.description, ce.event_date, ce.type, ce.metadata,
                ce.created_at, u.full_name AS created_by_name, d.name AS department_name
         FROM   calendar_events ce
         INNER JOIN users       u ON u.id = ce.created_by
         INNER JOIN departments d ON d.id = ce.department_id
         WHERE  ce.id = :id'
    );
    $fetch->execute([':id' => $newId]);

    $row = $fetch->fetch();

    if ($row === false) {
        throw new RuntimeException('Could not retrieve the newly created event.');
    }

    return formatCalendarEvent($row);
}

/**
 * Maps a DB row to a FullCalendar-compatible event object.
 */
function formatCalendarEvent(array $row): array
{
    static $colorMap = [
        'broadcast' => '#ef4444',
        'print'     => '#f59e0b',
        'general'   => '#3b82f6',
    ];

    $type  = (string) ($row['type'] ?? 'general');
    $color = $colorMap[$type] ?? '#64748b';

    $metaRaw  = $row['metadata'] ?? null;
    $metadata = ($metaRaw !== null && $metaRaw !== '')
        ? json_decode((string) $metaRaw, true)
        : null;

    return [
        'id'              => (int) $row['id'],
        'title'           => (string) $row['title'],
        'start'           => (string) $row['event_date'],
        'allDay'          => true,
        'backgroundColor' => $color,
        'borderColor'     => $color,
        'textColor'       => '#ffffff',
        'extendedProps'   => [
            'description'     => (string) ($row['description'] ?? ''),
            'type'            => $type,
            'department_name' => (string) ($row['department_name'] ?? ''),
            'created_by_name' => (string) ($row['created_by_name'] ?? ''),
            'created_at'      => (string) ($row['created_at'] ?? ''),
            'metadata'        => $metadata,
        ],
    ];
}
