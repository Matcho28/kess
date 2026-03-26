(() => {
    'use strict';

    /* ── Configuration ─────────────────────────────────────────────── */
    const cfg      = window.KESS || {};
    const BASE_URL = (typeof cfg.baseUrl === 'string') ? cfg.baseUrl.replace(/\/+$/, '') : '';

    const API = {
        LIST : BASE_URL + '/api/calendar/list.php',
        ADD  : BASE_URL + '/api/calendar/add.php',
    };

    /* ── Module state ───────────────────────────────────────────────── */
    let calendarInstance = null;
    let addEventModalInstance = null;
    let detailModalInstance = null;

    /* ── DOM references ─────────────────────────────────────────────── */
    const refs = {
        calendarEl       : document.getElementById('kessCalendar'),
        addEventModal    : document.getElementById('addEventModal'),
        detailModal      : document.getElementById('eventDetailModal'),
        addEventForm     : document.getElementById('addEventForm'),
        addEventError    : document.getElementById('addEventError'),
        addEventSubmitBtn: document.getElementById('addEventSubmitBtn'),
        btnOpenAddEvent  : document.getElementById('btnOpenAddEvent'),
        eventTitle       : document.getElementById('eventTitle'),
        eventDate        : document.getElementById('eventDate'),
        eventType        : document.getElementById('eventType'),
        eventDescription : document.getElementById('eventDescription'),
        detailTitle      : document.getElementById('detailTitle'),
        detailDate       : document.getElementById('detailDate'),
        detailDepartment : document.getElementById('detailDepartment'),
        detailCreatedBy  : document.getElementById('detailCreatedBy'),
        detailCreatedAt  : document.getElementById('detailCreatedAt'),
        detailTypeBadge  : document.getElementById('detailTypeBadge'),
        detailDescWrap   : document.getElementById('detailDescriptionWrap'),
        detailDescription: document.getElementById('detailDescription'),
        toastEl          : document.getElementById('calendarToast'),
        toastMsg         : document.getElementById('calendarToastMsg'),
    };

    /* ── Guard ──────────────────────────────────────────────────────── */
    if (!refs.calendarEl) return;

    /* ── FullCalendar initialisation ────────────────────────────────── */
    function initCalendar() {
        calendarInstance = new FullCalendar.Calendar(refs.calendarEl, {
            initialView     : 'dayGridMonth',
            height          : '100%',
            firstDay        : 1,
            navLinks        : true,
            dayMaxEvents    : 4,
            nowIndicator    : true,

            headerToolbar: {
                left  : 'prev,next today',
                center: 'title',
                right : 'dayGridMonth,dayGridWeek,listMonth',
            },

            buttonText: {
                today     : 'Today',
                month     : 'Month',
                week      : 'Week',
                listMonth : 'List',
            },

            /* Remote event source */
            events: fetchEvents,

            /* Date-click → open add-event modal */
            dateClick: function (info) {
                if (info.jsEvent) {
                    info.jsEvent.preventDefault();
                    info.jsEvent.stopPropagation();
                }

                openAddModal(info.dateStr);
            },

            /* Make entire day cell clickable */
            dayCellDidMount: function (info) {
                info.el.style.cursor = 'pointer';

                info.el.addEventListener('click', function (e) {
                    if (e.target.closest('.fc-event') || e.target.closest('.fc-more-link')) {
                        return;
                    }

                    if (e.target.closest('.fc-daygrid-day-number')) {
                        return;
                    }

                    const dateStr = normalizeDateString(info.date);
                    if (dateStr) {
                        e.preventDefault();
                        e.stopPropagation();
                        openAddModal(dateStr);
                    }
                });
            },

            /* Event-click → open detail modal */
            eventClick: function (info) {
                info.jsEvent.preventDefault();
                openDetailModal(info.event);
            },

            /* Tooltip on hover */
            eventDidMount: function (info) {
                const desc = (info.event.extendedProps.description || '').trim();
                if (desc) {
                    info.el.title = desc;
                }
            },

            /* Visual feedback when loading */
            loading: function (isLoading) {
                const spinner = document.getElementById('calSpinner');
                if (spinner) {
                    spinner.classList.toggle('d-none', !isLoading);
                }
            },
        });

        calendarInstance.render();
    }

    /* ── Fetch events callback ──────────────────────────────────────── */
    function fetchEvents(info, successCallback, failureCallback) {
        const url = API.LIST
            + '?start=' + encodeURIComponent(info.startStr)
            + '&end='   + encodeURIComponent(info.endStr);

        fetch(url, { credentials: 'same-origin' })
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(function (data) {
                if (data.success) {
                    successCallback(data.events || []);
                } else {
                    failureCallback(new Error(data.message || 'Failed to load events.'));
                }
            })
            .catch(function (err) {
                failureCallback(err);
                showToast('Could not load calendar events. Please refresh.', 'danger');
            });
    }

    /* ── Add event modal ────────────────────────────────────────────── */
    function openAddModal(dateStr) {
        if (!refs.addEventModal) {
            console.error('Add event modal not found in DOM');
            showToast('Modal not available. Please refresh the page.', 'danger');
            return;
        }

        clearFormError();

        if (refs.addEventForm) {
            refs.addEventForm.reset();
        }

        if (refs.eventDate) {
            refs.eventDate.value = normalizeDateString(dateStr);
        }

        if (typeof bootstrap === 'undefined' || typeof bootstrap.Modal === 'undefined') {
            console.error('Bootstrap Modal not loaded');
            showToast('Bootstrap not loaded. Please refresh the page.', 'danger');
            return;
        }

        try {
            if (addEventModalInstance === null) {
                addEventModalInstance = bootstrap.Modal.getOrCreateInstance(refs.addEventModal, {
                    backdrop: 'static',
                    keyboard: true,
                    focus: true
                });
            }

            enableModalInputs(refs.addEventModal);
            addEventModalInstance.show();

            refs.addEventModal.addEventListener('shown.bs.modal', function focusTitle() {
                window.requestAnimationFrame(function () {
                    window.requestAnimationFrame(function () {
                        enableModalInputs(refs.addEventModal);

                        if (refs.eventTitle) {
                            refs.eventTitle.focus();
                            refs.eventTitle.select();
                        }
                    });
                });

                refs.addEventModal.removeEventListener('shown.bs.modal', focusTitle);
            });
        } catch (error) {
            console.error('Error opening modal:', error);
            showToast('Could not open event form. Please refresh the page.', 'danger');
        }
    }

    /* ── Detail modal ───────────────────────────────────────────────── */
    function openDetailModal(event) {
        const props = event.extendedProps || {};

        setText(refs.detailTitle,      event.title);
        setText(refs.detailDate,       formatDate(event.startStr || ''));
        setText(refs.detailDepartment, props.department_name || '—');
        setText(refs.detailCreatedBy,  props.created_by_name || '—');
        setText(refs.detailCreatedAt,  formatDateTime(props.created_at || ''));

        /* Type badge */
        if (refs.detailTypeBadge) {
            refs.detailTypeBadge.textContent  = typeLabel(props.type);
            refs.detailTypeBadge.className    = 'badge rounded-pill ' + typeBadgeClass(props.type);
        }

        /* Description */
        const desc = (props.description || '').trim();
        if (refs.detailDescWrap) {
            refs.detailDescWrap.classList.toggle('d-none', desc === '');
        }
        if (refs.detailDescription) {
            refs.detailDescription.textContent = desc;
        }

        if (detailModalInstance === null && refs.detailModal) {
            detailModalInstance = bootstrap.Modal.getOrCreateInstance(refs.detailModal);
        }

        if (detailModalInstance) {
            detailModalInstance.show();
        }
    }

    /* ── Form submission ────────────────────────────────────────────── */
    function handleFormSubmit(e) {
        e.preventDefault();
        clearFormError();

        const title       = (refs.eventTitle?.value       || '').trim();
        const eventDate   = (refs.eventDate?.value         || '').trim();
        const type        = (refs.eventType?.value         || 'general').trim();
        const description = (refs.eventDescription?.value || '').trim();

        /* Client-side guards */
        if (!title) {
            showFormError('Event title is required.');
            refs.eventTitle?.focus();
            return;
        }

        if (!eventDate) {
            showFormError('Event date is required.');
            refs.eventDate?.focus();
            return;
        }

        setSubmitState(true);

        const payload = JSON.stringify({
            title      : title,
            event_date : eventDate,
            type       : type,
            description: description,
            csrf_token : window.KESS.csrfToken
        });

        fetch(API.ADD, {
            method     : 'POST',
            credentials: 'same-origin',
            headers    : { 'Content-Type': 'application/json' },
            body       : payload,
        })
  .then(async (res) => {
    let data;
    try {
        data = await res.json();
    } catch {
        throw new Error('Invalid server response');
    }

    if (!res.ok) {
        throw new Error(data.message || 'Request failed');
    }

    return data;
})
            .then(function (result) {
                setSubmitState(false);

                if (result.success) {
                    if (addEventModalInstance) {
                        addEventModalInstance.hide();
                    }

                    if (calendarInstance) {
                        calendarInstance.refetchEvents();
                    }

                    showToast('Event saved successfully.', 'success');
                } else {
                    showFormError(result.message || 'Failed to create event.');
                }
            })
            .catch(function (error) {
                setSubmitState(false);
                showFormError(error.message || 'Network error. Please check your connection and try again.');
            });
    }

    /* ── Button: "Add Event" in page header ─────────────────────────── */
    function bindHeaderButton() {
        if (!refs.btnOpenAddEvent) return;
        refs.btnOpenAddEvent.addEventListener('click', function () {
            openAddModal(todayStr());
        });
    }

    /* ── UI helpers ─────────────────────────────────────────────────── */
    function setSubmitState(loading) {
        if (!refs.addEventSubmitBtn) return;
        refs.addEventSubmitBtn.disabled = loading;
        refs.addEventSubmitBtn.innerHTML = loading
            ? '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving…'
            : '<i class="fas fa-save me-1"></i>Save Event';
    }

    function showFormError(msg) {
        if (!refs.addEventError) return;
        refs.addEventError.textContent = msg;
        refs.addEventError.classList.remove('d-none');
        refs.addEventError.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function clearFormError() {
        if (!refs.addEventError) return;
        refs.addEventError.textContent = '';
        refs.addEventError.classList.add('d-none');
    }

    function showToast(message, type) {
        if (!refs.toastEl || !refs.toastMsg) return;

        refs.toastMsg.textContent = message;
        refs.toastEl.className    = 'toast align-items-center text-white border-0 '
            + (type === 'success' ? 'bg-success' : 'bg-danger');

        const toast = bootstrap.Toast.getOrCreateInstance(refs.toastEl, { delay: 3500 });
        toast.show();
    }

    function setText(el, value) {
        if (el) el.textContent = value;
    }

    /* ── Date / label helpers ───────────────────────────────────────── */
    function todayStr() {
        return new Date().toISOString().slice(0, 10);
    }

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString('en-US', {
            year  : 'numeric',
            month : 'long',
            day   : 'numeric',
        });
    }

    function formatDateTime(dtStr) {
        if (!dtStr) return '—';
        const d = new Date(dtStr.replace(' ', 'T'));
        if (isNaN(d.getTime())) return dtStr;
        return d.toLocaleDateString('en-US', {
            year  : 'numeric',
            month : 'short',
            day   : 'numeric',
            hour  : '2-digit',
            minute: '2-digit',
        });
    }

    function typeLabel(type) {
        const labels = { broadcast: 'Broadcast', print: 'Print', general: 'General' };
        return labels[type] || (type ? type.charAt(0).toUpperCase() + type.slice(1) : 'General');
    }

    function typeBadgeClass(type) {
        const map = {
            broadcast: 'badge-broadcast',
            print    : 'badge-print',
            general  : 'badge-general',
        };
        return map[type] || 'bg-secondary';
    }

    /* ── Bootstrap 5 event handling ─────────────────────────────────── */
    function bindModalEvents() {
        /* Reset form when add-event modal closes */
        if (refs.addEventModal) {
            refs.addEventModal.addEventListener('hidden.bs.modal', function () {
                if (refs.addEventForm) refs.addEventForm.reset();
                clearFormError();
            });
        }

        /* Form submission */
        if (refs.addEventForm) {
            refs.addEventForm.addEventListener('submit', handleFormSubmit);
        }
    }

    /* ── Entry point ────────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        initCalendar();
        bindModalEvents();
        bindHeaderButton();
    });

})();
