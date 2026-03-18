<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/navigation.php';

requireLogin();
$currentUser = getCurrentUser();
$currentRole = getCurrentUserRole();
$conversationHint = $currentRole === ROLE_SUPER_ADMIN
    ? 'Select a department admin from the left panel.'
    : 'Select the super admin from the left panel.';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Complaints Chat - Internal Complaint Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/main.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/layout.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/sidebar.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/chat.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/darkmode.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/saas2026.css')) ?>">
</head>
<body>
<div class="app-shell">
    <?php renderNavigationSidebar('chats'); ?>

    <main class="app-main">
        <section class="chat-shell">
            <aside class="sidebar-panel" id="chatSidebar">
                <div class="sidebar-header">
                    <div>
                        <div class="sidebar-kicker">Inbox</div>
                        <h1 class="app-title mb-1">Complaints Chat</h1>
                    </div>
                    <span id="totalUnreadBadge" class="badge rounded-pill text-bg-danger d-none">0</span>
                </div>

                <div class="current-user-box">
                    <div class="current-user-copy">
                        <div class="fw-semibold"><?= e($currentUser['full_name'] ?? '') ?></div>
                        <div class="small text-muted"><?= e($currentUser['department_name'] ?? '') ?></div>
                        <div class="small text-muted"><?= e($currentUser['email'] ?? '') ?></div>
                    </div>
                </div>

                <div id="conversationList" class="conversation-list" aria-label="Conversation list"></div>
            </aside>

            <section class="chat-panel d-flex flex-column">
                <header class="chat-header" id="activeConversationHeader">
                    <div class="chat-header-main">
                        <button type="button" class="chat-sidebar-toggle" id="chatSidebarToggle" aria-label="Toggle chat list" aria-controls="chatSidebar" aria-expanded="true">
                            <span></span><span></span><span></span>
                        </button>
                        <div>
                            <h2 id="activeConversationName" class="h5 mb-1">Select a conversation</h2>
                            <p id="activeConversationMeta" class="small text-muted mb-0"><?= e($conversationHint) ?></p>
                        </div>
                    </div>
                    <div class="chat-header-actions">
                        <span class="chat-header-badge">Secure internal messaging</span>
                    </div>
                </header>

                <main id="messageContainer" class="message-container" aria-live="polite">
                    <div class="empty-state">No conversation selected yet.</div>
                </main>

                <form id="chatForm" class="chat-form" enctype="multipart/form-data" autocomplete="off">
                    <input type="hidden" id="recipientId" name="receiver_id" value="">

                    <div class="chat-form-composer">
                        <div class="chat-attach-control">
                            <button type="button" class="chat-attach-button" id="attachmentTrigger" aria-label="Attach file">+</button>
                            <input class="d-none" type="file" id="attachmentInput" name="attachment" accept=".pdf,.docx,.jpg,.jpeg,.png,.gif,.webp">
                        </div>

                        <div class="chat-emoji-control">
                            <button type="button" class="chat-emoji-button" id="emojiTrigger" aria-label="Add emoji">😊</button>
                        </div>

                        <div class="chat-input-wrap">
                            <textarea id="messageInput" name="message_text" class="form-control" rows="2" placeholder="Type a message"></textarea>
                            
                            <!-- File Selected Display Inside Input -->
                            <div id="attachmentPreview" class="attachment-preview" style="display: none;">
                                <div class="attachment-preview-content">
                                    <span class="attachment-preview-icon">📎</span>
                                    <span class="attachment-preview-name" id="attachmentName"></span>
                                    <button type="button" class="attachment-preview-remove" id="attachmentRemove" aria-label="Remove attachment">×</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p id="formFeedback" class="small mb-0 mt-2"></p>
                </form>
            </section>
        </section>
    </main>
</div>

<!-- Emoji Picker Popup -->
<div id="emojiPicker" class="emoji-picker" style="display: none;">
    <div class="emoji-picker-header">
        <span>Select Emoji</span>
        <button type="button" class="emoji-picker-close" id="emojiPickerClose">×</button>
    </div>
    <div class="emoji-picker-body">
        <div class="emoji-grid">
            <button type="button" class="emoji-item" data-emoji="😀">😀</button>
            <button type="button" class="emoji-item" data-emoji="😃">😃</button>
            <button type="button" class="emoji-item" data-emoji="😄">😄</button>
            <button type="button" class="emoji-item" data-emoji="😁">😁</button>
            <button type="button" class="emoji-item" data-emoji="😅">😅</button>
            <button type="button" class="emoji-item" data-emoji="😂">😂</button>
            <button type="button" class="emoji-item" data-emoji="🤣">🤣</button>
            <button type="button" class="emoji-item" data-emoji="😊">😊</button>
            <button type="button" class="emoji-item" data-emoji="😇">😇</button>
            <button type="button" class="emoji-item" data-emoji="🙂">🙂</button>
            <button type="button" class="emoji-item" data-emoji="😉">😉</button>
            <button type="button" class="emoji-item" data-emoji="😌">😌</button>
            <button type="button" class="emoji-item" data-emoji="😍">😍</button>
            <button type="button" class="emoji-item" data-emoji="🥰">🥰</button>
            <button type="button" class="emoji-item" data-emoji="😘">😘</button>
            <button type="button" class="emoji-item" data-emoji="😗">😗</button>
            <button type="button" class="emoji-item" data-emoji="😙">😙</button>
            <button type="button" class="emoji-item" data-emoji="😚">😚</button>
            <button type="button" class="emoji-item" data-emoji="😋">😋</button>
            <button type="button" class="emoji-item" data-emoji="😛">😛</button>
            <button type="button" class="emoji-item" data-emoji="😜">😜</button>
            <button type="button" class="emoji-item" data-emoji="🤪">🤪</button>
            <button type="button" class="emoji-item" data-emoji="😝">😝</button>
            <button type="button" class="emoji-item" data-emoji="🤗">🤗</button>
            <button type="button" class="emoji-item" data-emoji="🤭">🤭</button>
            <button type="button" class="emoji-item" data-emoji="🤫">🤫</button>
            <button type="button" class="emoji-item" data-emoji="🤔">🤔</button>
            <button type="button" class="emoji-item" data-emoji="❤️">❤️</button>
            <button type="button" class="emoji-item" data-emoji="💕">💕</button>
            <button type="button" class="emoji-item" data-emoji="💖">💖</button>
            <button type="button" class="emoji-item" data-emoji="💗">💗</button>
            <button type="button" class="emoji-item" data-emoji="💘">💘</button>
            <button type="button" class="emoji-item" data-emoji="💙">💙</button>
            <button type="button" class="emoji-item" data-emoji="💚">💚</button>
            <button type="button" class="emoji-item" data-emoji="💛">💛</button>
            <button type="button" class="emoji-item" data-emoji="🧡">🧡</button>
            <button type="button" class="emoji-item" data-emoji="💜">💜</button>
            <button type="button" class="emoji-item" data-emoji="👍">👍</button>
            <button type="button" class="emoji-item" data-emoji="👎">👎</button>
            <button type="button" class="emoji-item" data-emoji="👌">👌</button>
            <button type="button" class="emoji-item" data-emoji="✌️">✌️</button>
            <button type="button" class="emoji-item" data-emoji="🤞">🤞</button>
            <button type="button" class="emoji-item" data-emoji="🤟">🤟</button>
            <button type="button" class="emoji-item" data-emoji="🤘">🤘</button>
            <button type="button" class="emoji-item" data-emoji="🤙">🤙</button>
            <button type="button" class="emoji-item" data-emoji="👏">👏</button>
            <button type="button" class="emoji-item" data-emoji="🙌">🙌</button>
            <button type="button" class="emoji-item" data-emoji="👐">👐</button>
            <button type="button" class="emoji-item" data-emoji="🤲">🤲</button>
            <button type="button" class="emoji-item" data-emoji="🙏">🙏</button>
            <button type="button" class="emoji-item" data-emoji="🎉">🎉</button>
            <button type="button" class="emoji-item" data-emoji="🎊">🎊</button>
            <button type="button" class="emoji-item" data-emoji="🎈">🎈</button>
            <button type="button" class="emoji-item" data-emoji="🎁">🎁</button>
            <button type="button" class="emoji-item" data-emoji="🎀">🎀</button>
            <button type="button" class="emoji-item" data-emoji="🔥">🔥</button>
            <button type="button" class="emoji-item" data-emoji="✨">✨</button>
            <button type="button" class="emoji-item" data-emoji="💫">💫</button>
            <button type="button" class="emoji-item" data-emoji="⭐">⭐</button>
            <button type="button" class="emoji-item" data-emoji="🌟">🌟</button>
            <button type="button" class="emoji-item" data-emoji="⚡">⚡</button>
            <button type="button" class="emoji-item" data-emoji="💯">💯</button>
            <button type="button" class="emoji-item" data-emoji="✅">✅</button>
            <button type="button" class="emoji-item" data-emoji="❌">❌</button>
            <button type="button" class="emoji-item" data-emoji="⭕">⭕</button>
            <button type="button" class="emoji-item" data-emoji="❗">❗</button>
            <button type="button" class="emoji-item" data-emoji="❓">❓</button>
            <button type="button" class="emoji-item" data-emoji="‼️">‼️</button>
            <button type="button" class="emoji-item" data-emoji="⁉️">⁉️</button>
            <button type="button" class="emoji-item" data-emoji="👋">👋</button>
            <button type="button" class="emoji-item" data-emoji="🤝">🤝</button>
            <button type="button" class="emoji-item" data-emoji="🙈">🙈</button>
            <button type="button" class="emoji-item" data-emoji="🙉">🙉</button>
            <button type="button" class="emoji-item" data-emoji="🙊">🙊</button>
        </div>
    </div>
</div>

<script>
    window.KESS = {
        baseUrl: <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>,
        currentUserId: <?= (int) ($currentUser['id'] ?? 0) ?>,
        emptyConversationHint: <?= json_encode($conversationHint, JSON_UNESCAPED_SLASHES) ?>,
        pollIntervalMs: 3000
    };
</script>
<script src="<?= e(baseUrl('/assets/js/sidebar.js')) ?>"></script>
<script src="<?= e(baseUrl('/assets/js/chat.js')) ?>"></script>
<script src="<?= e(baseUrl('/assets/js/darkmode.js')) ?>"></script>
</body>
</html>
