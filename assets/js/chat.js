(() => {
    const config = window.KESS || {};
    const BASE_URL = normalizeBaseUrl(config.baseUrl || '');
    const POLL_INTERVAL_MS = Number(config.pollIntervalMs) > 0 ? Number(config.pollIntervalMs) : 3000;
    const CURRENT_USER_ID = Number(config.currentUserId || 0);
    const EMPTY_CONVERSATION_HINT = String(config.emptyConversationHint || 'Choose an admin from the left panel.');
    const DEFAULT_TITLE = 'Complaints Chat - Internal Complaint Chat';

    const refs = {
        chatSidebar: document.getElementById('chatSidebar'),
        chatSidebarToggle: document.getElementById('chatSidebarToggle'),
        conversationList: document.getElementById('conversationList'),
        totalUnreadBadge: document.getElementById('totalUnreadBadge'),
        activeConversationName: document.getElementById('activeConversationName'),
        activeConversationMeta: document.getElementById('activeConversationMeta'),
        messageContainer: document.getElementById('messageContainer'),
        chatForm: document.getElementById('chatForm'),
        recipientId: document.getElementById('recipientId'),
        messageInput: document.getElementById('messageInput'),
        chatInputWrap: document.querySelector('.chat-input-wrap'),
        attachmentTrigger: document.getElementById('attachmentTrigger'),
        attachmentInput: document.getElementById('attachmentInput'),
        attachmentRemoveBtn: document.getElementById('attachmentRemoveBtn'),
        attachmentIndicator: document.getElementById('attachmentIndicator'),
        attachmentIndicatorText: document.getElementById('attachmentIndicatorText'),
        uploadArea: document.getElementById('uploadArea'),
        emojiTrigger: document.getElementById('emojiTrigger'),
        emojiPicker: document.getElementById('emojiPicker'),
        emojiPickerClose: document.getElementById('emojiPickerClose'),
        formFeedback: document.getElementById('formFeedback'),
    };

    if (!refs.conversationList || !refs.messageContainer || !refs.chatForm) {
        return;
    }

    const state = {
        conversations: [],
        activeRecipientId: null,
        activeRecipient: null,
        messages: [],
        polling: false,
        loadingMessages: false,
        chatSidebarCollapsed: window.innerWidth <= 767,
    };

    function isMobileViewport() {
        return window.innerWidth <= 767;
    }

    function getFileIcon(file) {
        const extension = file.name.split('.').pop().toLowerCase();
        const iconMap = {
            'pdf': '📄',
            'doc': '📝',
            'docx': '📝',
            'jpg': '🖼️',
            'jpeg': '🖼️',
            'png': '🖼️',
            'gif': '🖼️',
            'webp': '🖼️'
        };
        return iconMap[extension] || '📎';
    }

    function getFileIconClass(file) {
        const extension = file.name.split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
            return 'img';
        }
        return extension;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function showAttachmentPreview(file) {
        if (!refs.chatInputWrap || !refs.attachmentInput || !refs.messageInput) {
            return;
        }

        // Add attachment class to input wrapper
        refs.chatInputWrap.classList.add('has-attachment');
        
        // Update attachment indicator text
        if (refs.attachmentIndicatorText) {
            const fileName = file.name.length > 15 ? file.name.substring(0, 12) + '...' : file.name;
            refs.attachmentIndicatorText.textContent = fileName;
        }
        
        // Focus the input
        refs.messageInput.focus();
    }

    function hideAttachmentPreview() {
        // Remove attachment class from input wrapper
        if (refs.chatInputWrap) {
            refs.chatInputWrap.classList.remove('has-attachment');
        }
        
        if (refs.attachmentInput) {
            refs.attachmentInput.value = '';
        }
        
        // Remove filename from message input
        if (refs.messageInput) {
            const currentMessage = refs.messageInput.value;
            // Remove the file reference (📎 filename) from the message
            const cleanedMessage = currentMessage.replace(/\n\n📎.*$/gm, '').trim();
            refs.messageInput.value = cleanedMessage;
        }
    }

    function setChatSidebarCollapsed(collapsed) {
        state.chatSidebarCollapsed = collapsed;

        if (refs.chatSidebar) {
            refs.chatSidebar.classList.toggle('is-collapsed', collapsed);
        }

        if (refs.chatSidebarToggle) {
            refs.chatSidebarToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        }
    }

    function syncSidebarStateForViewport() {
        if (isMobileViewport()) {
            setChatSidebarCollapsed(state.activeRecipientId !== null);
            return;
        }

        setChatSidebarCollapsed(false);
    }

    function normalizeBaseUrl(baseUrl) {
        const trimmed = String(baseUrl || '').trim();
        if (trimmed === '' || trimmed === '/') {
            return '';
        }

        return trimmed.endsWith('/') ? trimmed.slice(0, -1) : trimmed;
    }

    function apiUrl(path) {
        return `${BASE_URL}${path}`;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatTimestamp(value) {
        if (!value) {
            return '';
        }

        const parsed = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(parsed.getTime())) {
            return String(value);
        }

        return parsed.toLocaleString([], {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function formatTime(value) {
        if (!value) {
            return '';
        }

        const parsed = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(parsed.getTime())) {
            return '';
        }

        return parsed.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function formatFileSize(bytes) {
        const size = Number(bytes || 0);

        if (size < 1024) {
            return `${size} B`;
        }

        if (size < 1024 * 1024) {
            return `${(size / 1024).toFixed(1)} KB`;
        }

        return `${(size / (1024 * 1024)).toFixed(1)} MB`;
    }

    function setFeedback(message, type = 'muted') {
        refs.formFeedback.textContent = message || '';
        refs.formFeedback.classList.remove('text-muted', 'text-success', 'text-danger');

        if (type === 'success') {
            refs.formFeedback.classList.add('text-success');
            return;
        }

        if (type === 'danger') {
            refs.formFeedback.classList.add('text-danger');
            return;
        }

        refs.formFeedback.classList.add('text-muted');
    }

    function updateTotalUnread(totalUnread) {
        const unread = Math.max(0, Number(totalUnread || 0));

        if (unread > 0) {
            refs.totalUnreadBadge.textContent = String(unread);
            refs.totalUnreadBadge.classList.remove('d-none');
            document.title = `(${unread}) ${DEFAULT_TITLE}`;
        } else {
            refs.totalUnreadBadge.textContent = '0';
            refs.totalUnreadBadge.classList.add('d-none');
            document.title = DEFAULT_TITLE;
        }
    }

    function updateActiveConversationHeader() {
        if (!state.activeRecipient) {
            refs.activeConversationName.textContent = 'Select a conversation';
            refs.activeConversationMeta.textContent = EMPTY_CONVERSATION_HINT;
            return;
        }

        refs.activeConversationName.textContent = state.activeRecipient.full_name || 'Conversation';
        const metaParts = [state.activeRecipient.department_name || '', state.activeRecipient.email || ''].filter(Boolean);
        refs.activeConversationMeta.textContent = metaParts.length > 0 ? metaParts.join(' - ') : 'Active conversation';
    }

    function renderConversationList() {
        if (!state.conversations.length) {
            refs.conversationList.innerHTML = '<div class="empty-state">No active users found.</div>';
            return;
        }

        refs.conversationList.innerHTML = state.conversations
            .map((conversation) => {
                const userId = Number(conversation.user_id);
                const isActive = userId === state.activeRecipientId;
                const unreadCount = Math.max(0, Number(conversation.unread_count || 0));
                const preview = conversation.last_message_preview || 'No messages yet';

                return `
                    <button type="button" class="conversation-item ${isActive ? 'active' : ''}" data-user-id="${userId}">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                            <div>
                                <div class="conversation-name">${escapeHtml(conversation.full_name || '')}</div>
                                <div class="conversation-department">${escapeHtml(conversation.department_name || '')}</div>
                            </div>
                            <div class="conversation-time">${escapeHtml(formatTime(conversation.last_message_time))}</div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div class="conversation-preview">${escapeHtml(preview)}</div>
                            ${unreadCount > 0 ? `<span class="unread-badge">${unreadCount}</span>` : ''}
                        </div>
                    </button>
                `;
            })
            .join('');
    }

    function renderAttachmentLinks(attachments) {
        if (!Array.isArray(attachments) || attachments.length === 0) {
            return '';
        }

        const html = attachments
            .map((attachment) => {
                const url = attachment.download_url || '#';
                const name = attachment.original_name || 'Attachment';
                const sizeLabel = formatFileSize(attachment.file_size);

                return `
                    <a class="attachment-link" href="${escapeHtml(url)}">
                        <span class="attachment-name" title="${escapeHtml(name)}">${escapeHtml(name)}</span>
                        <span class="attachment-size">${escapeHtml(sizeLabel)}</span>
                    </a>
                `;
            })
            .join('');

        return `<div class="attachment-list">${html}</div>`;
    }

    function renderMessages(shouldScrollToBottom) {
        if (!state.activeRecipientId) {
            refs.messageContainer.innerHTML = '<div class="empty-state">No conversation selected yet.</div>';
            return;
        }

        if (!state.messages.length) {
            refs.messageContainer.innerHTML = '<div class="empty-state">No messages yet. Start the conversation.</div>';
            return;
        }

        const html = state.messages
            .map((message) => {
                const isSent = Number(message.sender_id) === CURRENT_USER_ID;
                const rowClass = isSent ? 'sent' : 'received';
                const body = String(message.message_text || '').trim();
                const bodyHtml = body !== '' ? `<div class="message-text">${escapeHtml(body)}</div>` : '';
                const attachmentsHtml = renderAttachmentLinks(message.attachments || []);
                const seenLabel = isSent ? (message.read_at ? 'Seen' : 'Sent') : '';

                return `
                    <div class="message-row ${rowClass}">
                        <article class="message-bubble">
                            ${bodyHtml}
                            ${attachmentsHtml}
                            <div class="message-meta">${escapeHtml(formatTimestamp(message.created_at))}${seenLabel ? ` • ${escapeHtml(seenLabel)}` : ''}</div>
                        </article>
                    </div>
                `;
            })
            .join('');

        refs.messageContainer.innerHTML = html;

        if (shouldScrollToBottom) {
            scrollMessagesToBottom('auto');
        }
    }

    function scrollMessagesToBottom(behavior) {
        refs.messageContainer.scrollTo({
            top: refs.messageContainer.scrollHeight,
            behavior,
        });
    }

    async function requestJson(path, options = {}) {
        const response = await fetch(apiUrl(path), {
            credentials: 'same-origin',
            ...options,
        });

        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
            if (response.redirected) {
                window.location.href = response.url;
                throw new Error('Session expired. Redirecting...');
            }

            const responseText = await response.text();
            throw new Error(responseText || 'Unexpected server response.');
        }

        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Request failed.');
        }

        return payload;
    }

    function findConversationByUserId(userId) {
        return state.conversations.find((conversation) => Number(conversation.user_id) === Number(userId)) || null;
    }

    async function loadConversations({ autoSelectFirst = true } = {}) {
        const payload = await requestJson('/chat/api/conversations.php');
        state.conversations = payload.conversations || [];

        updateTotalUnread(payload.total_unread || 0);

        if (state.activeRecipientId !== null) {
            state.activeRecipient = findConversationByUserId(state.activeRecipientId);
        }

        renderConversationList();
        updateActiveConversationHeader();

        if (state.activeRecipientId === null && autoSelectFirst && state.conversations.length > 0) {
            await selectConversation(state.conversations[0].user_id, true);
            return;
        }

        if (state.activeRecipientId !== null && !state.activeRecipient && state.conversations.length > 0) {
            await selectConversation(state.conversations[0].user_id, true);
            return;
        }

        if (state.activeRecipientId !== null && !state.activeRecipient) {
            state.activeRecipientId = null;
            refs.recipientId.value = '';
            state.messages = [];
            renderMessages(false);
            updateActiveConversationHeader();
        }
    }

    function getLastMessageId(messages) {
        if (!Array.isArray(messages) || messages.length === 0) {
            return 0;
        }

        return Number(messages[messages.length - 1].id || 0);
    }

    async function loadMessages({ forceScroll = false } = {}) {
        if (!state.activeRecipientId || state.loadingMessages) {
            return;
        }

        state.loadingMessages = true;

        const isNearBottom =
            refs.messageContainer.scrollTop + refs.messageContainer.clientHeight >= refs.messageContainer.scrollHeight - 100;

        const previousLastMessageId = getLastMessageId(state.messages);

        try {
            const payload = await requestJson(`/chat/api/messages.php?receiver_id=${encodeURIComponent(String(state.activeRecipientId))}`);

            state.messages = payload.messages || [];

            if (payload.recipient) {
                state.activeRecipient = payload.recipient;
            }

            const activeConversation = findConversationByUserId(state.activeRecipientId);
            if (activeConversation && Number(activeConversation.unread_count || 0) > 0) {
                activeConversation.unread_count = 0;
                renderConversationList();
            }

            if (typeof payload.total_unread !== 'undefined') {
                updateTotalUnread(payload.total_unread);
            }

            updateActiveConversationHeader();

            const newLastMessageId = getLastMessageId(state.messages);
            const lastMessage = state.messages.length > 0 ? state.messages[state.messages.length - 1] : null;
            const userJustSentLatest = lastMessage && Number(lastMessage.sender_id) === CURRENT_USER_ID;
            const hasNewMessage = newLastMessageId !== previousLastMessageId;

            renderMessages(forceScroll || isNearBottom || (hasNewMessage && userJustSentLatest));
        } finally {
            state.loadingMessages = false;
        }
    }

    async function selectConversation(userId, forceReload = false) {
        const selectedUserId = Number(userId);

        if (!Number.isInteger(selectedUserId) || selectedUserId <= 0) {
            return;
        }

        const changed = state.activeRecipientId !== selectedUserId;

        state.activeRecipientId = selectedUserId;
        refs.recipientId.value = String(selectedUserId);
        state.activeRecipient = findConversationByUserId(selectedUserId);

        renderConversationList();
        updateActiveConversationHeader();

        if (changed || forceReload) {
            await loadMessages({ forceScroll: true });
            await loadConversations({ autoSelectFirst: false });
    }

    if (typeof payload.total_unread !== 'undefined') {
        updateTotalUnread(payload.total_unread);
    }

    updateActiveConversationHeader();

    const newLastMessageId = getLastMessageId(state.messages);
    const lastMessage = state.messages.length > 0 ? state.messages[state.messages.length - 1] : null;
    const userJustSentLatest = lastMessage && Number(lastMessage.sender_id) === CURRENT_USER_ID;
    const hasNewMessage = newLastMessageId !== previousLastMessageId;

    renderMessages(forceScroll || isNearBottom || (hasNewMessage && userJustSentLatest));
}

async function selectConversation(userId, forceReload = false) {
    const selectedUserId = Number(userId);

    if (!Number.isInteger(selectedUserId) || selectedUserId <= 0) {
        return;
    }

    const changed = state.activeRecipientId !== selectedUserId;

    state.activeRecipientId = selectedUserId;
    refs.recipientId.value = String(selectedUserId);
    state.activeRecipient = findConversationByUserId(selectedUserId);

    renderConversationList();
    updateActiveConversationHeader();

    if (changed || forceReload) {
        await loadMessages({ forceScroll: true });
        await loadConversations({ autoSelectFirst: false });
    }
}

async function handleChatSubmit(event) {
    event.preventDefault();
    const recipientId = Number(refs.recipientId.value);
    if (recipientId <= 0) {
        setFeedback('Please select a conversation first.', 'danger');
        return;
    }

    const messageText = refs.messageInput.value.trim();
    if (!messageText && !refs.attachmentInput.files.length) {
        setFeedback('Please enter a message or attach a file.', 'danger');
        return;
    }

    const formData = new FormData();
    formData.append('receiver_id', recipientId);
    formData.append('message_text', messageText);
    if (refs.attachmentInput.files.length) {
        formData.append('attachment', refs.attachmentInput.files[0]);
    }

    try {
        await requestJson('/chat/api/send.php', {
            method: 'POST',
            body: formData,
        });

        refs.messageInput.value = '';
        refs.attachmentInput.value = '';
        
        // Hide attachment preview after sending
        hideAttachmentPreview();

        await loadMessages({ forceScroll: true });
        await loadConversations({ autoSelectFirst: false });
    } catch (error) {
        // Silent error handling - no feedback shown
    }
}

function bindEvents() {
    if (refs.chatSidebarToggle) {
        refs.chatSidebarToggle.addEventListener('click', () => {
            setChatSidebarCollapsed(!state.chatSidebarCollapsed);
        });
    }

    if (refs.attachmentTrigger && refs.attachmentInput) {
        refs.attachmentTrigger.addEventListener('click', () => {
            refs.attachmentInput.click();
        });
    }

    // Handle file selection to show preview
    if (refs.attachmentInput) {
        refs.attachmentInput.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) {
                showAttachmentPreview(file);
            } else {
                clearAttachment();
            }
        });
    }

    // Handle attachment removal - Clear file when user removes attachment
    if (refs.attachmentRemoveBtn) {
        refs.attachmentRemoveBtn.addEventListener('click', () => {
            clearAttachment();
        });
    }

    function clearAttachment() {
        // Remove attachment class from input wrapper
        if (refs.chatInputWrap) {
            refs.chatInputWrap.classList.remove('has-attachment');
        }
        
        if (refs.attachmentInput) {
            refs.attachmentInput.value = '';
        }
    }

    // Handle drag and drop
    if (refs.uploadArea && refs.attachmentInput) {
        const chatInputWrap = refs.messageInput?.closest('.chat-input-wrap');
        
        if (chatInputWrap) {
            // Prevent default drag behaviors
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                chatInputWrap.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });

            // Highlight drop area when item is dragged over it
            ['dragenter', 'dragover'].forEach(eventName => {
                chatInputWrap.addEventListener(eventName, () => {
                    refs.uploadArea.classList.add('active');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                chatInputWrap.addEventListener(eventName, () => {
                    refs.uploadArea.classList.remove('active');
                }, false);
            });

            // Handle dropped files
            chatInputWrap.addEventListener('drop', (event) => {
                const files = event.dataTransfer.files;
                if (files.length > 0) {
                    refs.attachmentInput.files = files;
                    showAttachmentPreview(files[0]);
                }
            }, false);

            // Allow clicking upload area to browse files
            refs.uploadArea.addEventListener('click', () => {
                refs.attachmentInput.click();
            });
        }
    }

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    if (refs.emojiTrigger) {
        refs.emojiTrigger.addEventListener('click', () => {
            if (refs.emojiPicker) {
                refs.emojiPicker.style.display = refs.emojiPicker.style.display === 'none' ? 'block' : 'none';
                
                // Position the picker near the emoji button
                const buttonRect = refs.emojiTrigger.getBoundingClientRect();
                refs.emojiPicker.style.bottom = (window.innerHeight - buttonRect.top + 10) + 'px';
                refs.emojiPicker.style.right = (window.innerWidth - buttonRect.right) + 'px';
            }
        });
    }

    if (refs.emojiPickerClose && refs.emojiPicker) {
        refs.emojiPickerClose.addEventListener('click', () => {
            refs.emojiPicker.style.display = 'none';
        });
    }

    // Handle emoji selection
    if (refs.emojiPicker) {
        refs.emojiPicker.addEventListener('click', (event) => {
            const emojiItem = event.target.closest('.emoji-item');
            if (emojiItem) {
                const emoji = emojiItem.getAttribute('data-emoji');
                if (emoji && refs.messageInput) {
                    const start = refs.messageInput.selectionStart;
                    const end = refs.messageInput.selectionEnd;
                    const text = refs.messageInput.value;
                    
                    refs.messageInput.value = text.substring(0, start) + emoji + text.substring(end);
                    refs.messageInput.selectionStart = refs.messageInput.selectionEnd = start + emoji.length;
                    refs.messageInput.focus();
                    
                    // Close picker after selection
                    refs.emojiPicker.style.display = 'none';
                }
            }
        });
    }

    // Close emoji picker when clicking outside
    document.addEventListener('click', (event) => {
        if (refs.emojiPicker && refs.emojiTrigger) {
            const isClickInsidePicker = refs.emojiPicker.contains(event.target);
            const isClickOnTrigger = refs.emojiTrigger.contains(event.target);
            
            if (!isClickInsidePicker && !isClickOnTrigger) {
                refs.emojiPicker.style.display = 'none';
            }
        }
    });

    refs.conversationList.addEventListener('click', (event) => {
        const button = event.target.closest('[data-user-id]');
        if (!button) {
            return;
        }

        const selectedUserId = Number(button.getAttribute('data-user-id'));
        if (selectedUserId > 0) {
            selectConversation(selectedUserId, false).catch((error) => {
                setFeedback(error.message || 'Unable to load conversation.', 'danger');
            });

            if (isMobileViewport()) {
                setChatSidebarCollapsed(true);
            }
        }
    });

    refs.chatForm.addEventListener('submit', (event) => {
        handleChatSubmit(event).catch((error) => {
            setFeedback(error.message || 'Unable to send message.', 'danger');
        });
    });

    refs.messageInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            refs.chatForm.requestSubmit();
        }
    });

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            pollUpdates().catch(() => {
                // Ignore transient polling errors in the UI.
            });
        }
    });

    window.addEventListener('resize', () => {
        syncSidebarStateForViewport();
    });
}

    async function pollUpdates() {
        if (state.polling) {
            return;
        }

        state.polling = true;

        try {
            await loadConversations({ autoSelectFirst: false });

            if (state.activeRecipientId) {
                await loadMessages({ forceScroll: false });
            }
        } catch (error) {
            // Keep polling silent for temporary issues.
            console.error(error);
        } finally {
            state.polling = false;
        }
    }

    async function init() {
        syncSidebarStateForViewport();
        bindEvents();
        setFeedback('', 'muted');

        try {
            await loadConversations({ autoSelectFirst: true });
        } catch (error) {
            setFeedback(error.message || 'Unable to initialize chat.', 'danger');
        }

        window.setInterval(() => {
            pollUpdates().catch(() => {
                // Keep polling loop alive even after a failed request.
            });
        }, POLL_INTERVAL_MS);
    }

    init().catch(() => {
        setFeedback('Unable to initialize chat module.', 'danger');
    });
})();
