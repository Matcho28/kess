<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/navigation.php';

requireLogin();

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$currentUser = getCurrentUser();
$currentRole = getCurrentUserRole();
$isSA        = $currentRole === ROLE_SUPER_ADMIN;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Calendar – KESS</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
          rel="stylesheet">

    <!-- FullCalendar v6 -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css"
          rel="stylesheet">

    <!-- App CSS -->
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/main.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/layout.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/sidebar.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/darkmode.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/saas2026.css')) ?>">

    <style>
        /* ── Page layout ─────────────────────────────────────────── */
        body { background: white !important; }

        .calendar-page-wrapper {
            display: flex;
            flex-direction: column;
            height: 100vh;
            padding: 1.5rem;
            overflow: hidden;
        }

        .calendar-header {
            flex-shrink: 0;
            margin-bottom: 1.25rem;
        }

        .calendar-body {
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }

        /* ── Calendar card ───────────────────────────────────────── */
        .calendar-card {
            flex: 1;
            min-height: 0;
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 1.25rem;
            box-shadow: 0 8px 32px rgba(15, 23, 42, 0.08);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .calendar-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.12);
            flex-shrink: 0;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }

        .calendar-card-body {
            flex: 1;
            min-height: 0;
            padding: 1.25rem;
            overflow: hidden;
        }

        /* ── FullCalendar overrides ───────────────────────────────── */
        #kessCalendar {
            height: 100%;
        }

        .fc .fc-toolbar-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--secondary-800);
        }

        .fc .fc-button-primary {
            background: var(--primary-500);
            border-color: var(--primary-500);
            font-size: 0.825rem;
            font-weight: 500;
            border-radius: 0.5rem;
            padding: 0.35rem 0.75rem;
            transition: background 0.2s ease, border-color 0.2s ease;
        }

        .fc .fc-button-primary:hover {
            background: var(--primary-600);
            border-color: var(--primary-600);
        }

        .fc .fc-button-primary:not(:disabled).fc-button-active {
            background: var(--primary-700);
            border-color: var(--primary-700);
        }

        .fc .fc-daygrid-day:hover {
            background: rgba(59, 130, 246, 0.04);
            cursor: pointer;
        }

        .fc .fc-daygrid-day-number {
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--secondary-600);
            padding: 4px 6px;
        }

        .fc .fc-col-header-cell-cushion {
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--secondary-500);
        }

        .fc-event {
            border-radius: 5px;
            font-size: 0.76rem;
            font-weight: 500;
            padding: 2px 5px;
            cursor: pointer;
            transition: filter 0.15s ease;
        }

        .fc-event:hover {
            filter: brightness(0.9);
        }

        /* ── Legend ──────────────────────────────────────────────── */
        .event-legend {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--secondary-600);
        }

        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        /* ── Type badges ─────────────────────────────────────────── */
        .badge-broadcast { background-color: #ef4444; color: #fff; }
        .badge-print     { background-color: #f59e0b; color: #fff; }
        .badge-general   { background-color: #3b82f6; color: #fff; }

        /* ── Modal enhancements ───────────────────────────────────── */
        .modal-content {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.18);
        }

        .modal-header {
            border-bottom: 1px solid rgba(148, 163, 184, 0.15);
            padding: 1.25rem 1.5rem;
        }

        .modal-title {
            font-weight: 700;
            font-size: 1rem;
        }

        .modal-footer {
            border-top: 1px solid rgba(148, 163, 184, 0.15);
            padding: 1rem 1.5rem;
        }

        .form-label {
            font-size: 0.825rem;
            font-weight: 600;
            color: var(--secondary-700);
            margin-bottom: 0.35rem;
        }

        .form-control,
        .form-select {
            border-radius: 0.6rem;
            border: 1px solid rgba(148, 163, 184, 0.3);
            font-size: 0.875rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-400);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
        }

        /* ── Detail modal ────────────────────────────────────────── */
        .detail-label {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--secondary-400);
            margin-bottom: 2px;
        }

        .detail-value {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--secondary-800);
        }

        .detail-description {
            font-size: 0.875rem;
            color: var(--secondary-600);
            line-height: 1.65;
            white-space: pre-wrap;
            word-break: break-word;
        }

        /* ── Dark mode ───────────────────────────────────────────── */
        body.dark-mode .calendar-card {
            background: rgba(30, 41, 59, 0.97);
            border-color: rgba(148, 163, 184, 0.1);
        }

        body.dark-mode .calendar-card-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-color: rgba(148, 163, 184, 0.1);
        }

        body.dark-mode .fc .fc-toolbar-title,
        body.dark-mode .fc .fc-daygrid-day-number,
        body.dark-mode .fc .fc-col-header-cell-cushion,
        body.dark-mode .legend-item {
            color: #94a3b8;
        }

        body.dark-mode .fc-daygrid-day-top a {
            color: #94a3b8;
        }

        body.dark-mode .modal-content {
            background: #1e293b;
            color: #f1f5f9;
        }

        body.dark-mode .form-control,
        body.dark-mode .form-select {
            background: #0f172a;
            border-color: rgba(148, 163, 184, 0.2);
            color: #f1f5f9;
        }

        body.dark-mode .detail-value {
            color: #e2e8f0;
        }

        body.dark-mode .detail-description {
            color: #94a3b8;
        }
    </style>
</head>
<body>
<div class="app-shell">
    <?php renderNavigationSidebar('calendar'); ?>

    <main class="app-main">
        <div class="calendar-page-wrapper">

            <!-- Page header -->
            <div class="calendar-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h1 class="h4 fw-bold mb-0">
                        <i class="fas fa-calendar-alt me-2" style="color: var(--primary-500);"></i>
                        Calendar
                    </h1>
                    <p class="text-muted mb-0" style="font-size:0.82rem;">
                        <?= $isSA ? 'All departments' : e($currentUser['department_name'] ?? '') ?>
                        &mdash; Click any date to add an event
                    </p>
                </div>

                <button type="button"
                        class="btn btn-primary btn-sm d-flex align-items-center gap-2"
                        id="btnOpenAddEvent">
                    <i class="fas fa-plus"></i>
                    Add Event
                </button>
            </div>

            <!-- Calendar body -->
            <div class="calendar-body">
                <div class="calendar-card">
                    <div class="calendar-card-header">
                        <div class="event-legend">
                            <span class="legend-item">
                                <span class="legend-dot" style="background:#3b82f6;"></span>General
                            </span>
                            <span class="legend-item">
                                <span class="legend-dot" style="background:#f59e0b;"></span>Print
                            </span>
                            <span class="legend-item">
                                <span class="legend-dot" style="background:#ef4444;"></span>Broadcast
                            </span>
                        </div>
                        <small class="text-muted">Timezone: Asia/Manila</small>
                    </div>
                    <div class="calendar-card-body">
                        <div id="kessCalendar"></div>
                    </div>
                </div>
            </div>

        </div><!-- /.calendar-page-wrapper -->
    </main>
</div><!-- /.app-shell -->

<!-- =========================================================
     Modal: Add Event
     ========================================================= -->
<div class="modal fade"
     id="addEventModal"
     tabindex="-1"
     aria-labelledby="addEventModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="addEventModalLabel">
                    <i class="fas fa-calendar-plus me-2 text-primary"></i>New Calendar Event
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="addEventForm" novalidate autocomplete="off">
                <div class="modal-body px-4 py-3">

                    <div class="alert alert-danger d-none mb-3" id="addEventError" role="alert"></div>

                    <!-- Title -->
                    <div class="mb-3">
                        <label class="form-label" for="eventTitle">
                            Title <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="eventTitle"
                               name="title"
                               class="form-control"
                               maxlength="255"
                               placeholder="e.g. Department Meeting"
                               required>
                    </div>

                    <!-- Date -->
                    <div class="mb-3">
                        <label class="form-label" for="eventDate">
                            Date <span class="text-danger">*</span>
                        </label>
                        <input type="date"
                               id="eventDate"
                               name="event_date"
                               class="form-control"
                               required>
                    </div>

                    <!-- Type -->
                    <div class="mb-3">
                        <label class="form-label" for="eventType">Event Type</label>
                        <select id="eventType" name="type" class="form-select">
                            <option value="general" selected>General</option>
                            <option value="print">Print</option>
                            <?php if ($isSA): ?>
                            <option value="broadcast">Broadcast (all departments)</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Description -->
                    <div class="mb-1">
                        <label class="form-label" for="eventDescription">Description</label>
                        <textarea id="eventDescription"
                                  name="description"
                                  class="form-control"
                                  rows="3"
                                  maxlength="5000"
                                  placeholder="Optional details…"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit"
                            id="addEventSubmitBtn"
                            class="btn btn-primary btn-sm d-flex align-items-center gap-2">
                        <i class="fas fa-save"></i>Save Event
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- =========================================================
     Modal: Event Detail
     ========================================================= -->
<div class="modal fade"
     id="eventDetailModal"
     tabindex="-1"
     aria-labelledby="eventDetailModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="eventDetailModalLabel">
                    <i class="fas fa-calendar-day me-2 text-primary"></i>Event Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body px-4 py-3">

                <!-- Title + badge -->
                <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                    <h6 class="fw-bold mb-0 detail-value" id="detailTitle" style="font-size:1rem;"></h6>
                    <span class="badge rounded-pill flex-shrink-0" id="detailTypeBadge"></span>
                </div>

                <div class="row g-3">
                    <div class="col-6">
                        <div class="detail-label">Date</div>
                        <div class="detail-value" id="detailDate"></div>
                    </div>
                    <div class="col-6">
                        <div class="detail-label">Department</div>
                        <div class="detail-value" id="detailDepartment"></div>
                    </div>
                    <div class="col-6">
                        <div class="detail-label">Created by</div>
                        <div class="detail-value" id="detailCreatedBy"></div>
                    </div>
                    <div class="col-6">
                        <div class="detail-label">Created at</div>
                        <div class="detail-value" id="detailCreatedAt"></div>
                    </div>
                </div>

                <!-- Description -->
                <div class="mt-3 d-none" id="detailDescriptionWrap">
                    <div class="detail-label mb-1">Description</div>
                    <div class="detail-description" id="detailDescription"></div>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>

<!-- =========================================================
     Toast notification
     ========================================================= -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
    <div id="calendarToast"
         class="toast align-items-center text-white border-0"
         role="alert"
         aria-live="assertive"
         aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="calendarToastMsg"></div>
            <button type="button"
                    class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast"
                    aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- =========================================================
     Scripts
     ========================================================= -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmDxbZhq5yPAJmVkjNhsEEiDHUzO"
        crossorigin="anonymous"></script>

<!-- FullCalendar v6 -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<script src="<?= e(baseUrl('/assets/js/sidebar.js')) ?>"></script>
<script src="<?= e(baseUrl('/assets/js/darkmode.js')) ?>"></script>

<script>
    window.KESS = window.KESS || {};
    window.KESS.baseUrl         = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;
    window.KESS.isSuperAdmin    = <?= json_encode($isSA) ?>;
    window.KESS.currentUserId   = <?= (int) $currentUser['id'] ?>;
    window.KESS.departmentId    = <?= (int) $currentUser['department_id'] ?>;
    window.KESS.csrfToken       = <?= json_encode($_SESSION['csrf_token']) ?>;
</script>

<script src="<?= e(baseUrl('/assets/js/calendar.js')) ?>"></script>

</body>
</html>
