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
    <style>
        /* Compact Chat Styling */
        .chat-shell {
            height: calc(100vh - 0px);
            margin: 0;
            padding: 0.5rem;
            gap: 0.5px;
        }
        
        .sidebar-panel {
            width: 300px;
            flex-shrink: 0;
            margin-top: 0.5rem;
        }
        
        .sidebar-header {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--glass-border);
            margin-top: 0.25rem;
        }
        
        .current-user-box {
            padding: 0.5rem 1rem;
            border-bottom: 1px solid var(--glass-border);
            font-size: 0.85rem;
        }
        
        .conversation-list {
            padding: 0.25rem 0;
        }
        
        .conversation-item {
            padding: 0.5rem 1rem;
            margin: 0.125rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
        }
        
        .chat-panel {
            flex: 1;
            min-width: 0;
            margin-top: 0.5rem;
        }
        
        .chat-header {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--glass-border);
            min-height: 60px;
        }
        
        .chat-header-main h2 {
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }
        
        .chat-header-meta {
            font-size: 0.8rem;
        }
        
        .message-container {
            flex: 1;
            padding: 0.5rem;
            overflow-y: auto;
        }
        
        .message-bubble {
            max-width: 75%;
            padding: 0.5rem 0.75rem;
            margin: 0.25rem 0;
            border-radius: 0.75rem;
            font-size: 0.9rem;
        }
        
        .chat-form {
            padding: 0.75rem 1rem;
            border-top: 1px solid var(--glass-border);
        }
        
        .chat-form-composer {
            gap: 0.5rem;
        }
        
        .chat-attach-button,
        .chat-emoji-button {
            width: 36px;
            height: 36px;
            font-size: 1rem;
        }
        
        .chat-input-wrap textarea {
            padding: 0.5rem;
            font-size: 0.9rem;
            min-height: 38px;
            max-height: 100px;
        }
        
        .attachment-preview {
            display: none;
            margin-top: 0.5rem;
            border-radius: 0.75rem;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }
        
        .attachment-preview.active {
            display: block;
        }
        
        .attachment-preview-content {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            gap: 0.75rem;
        }
        
        .attachment-preview-icon {
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            color: white;
            flex-shrink: 0;
        }
        
        .attachment-preview-icon.image {
            background: none;
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 0.5rem;
        }
        
        .attachment-preview-details {
            flex: 1;
            min-width: 0;
        }
        
        .attachment-preview-name {
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--secondary-900);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }
        
        .attachment-preview-size {
            font-size: 0.8rem;
            color: var(--secondary-600);
            margin-top: 0.125rem;
        }
        
        .attachment-preview-remove {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: none;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        
        .attachment-preview-remove:hover {
            background: rgba(239, 68, 68, 0.2);
            transform: scale(1.1);
        }
        
        /* File type specific icons */
        .attachment-preview-icon.pdf { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .attachment-preview-icon.doc { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .attachment-preview-icon.docx { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .attachment-preview-icon.img { background: linear-gradient(135deg, #22c55e, #16a34a); }
        
        /* Upload area for drag and drop */
        .upload-area {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(59, 130, 246, 0.1);
            border: 2px dashed var(--primary-500);
            border-radius: 0.75rem;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .upload-area.active {
            display: flex;
        }
        
        .upload-area-content {
            text-align: center;
            color: var(--primary-600);
        }
        
        .upload-area-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .upload-area-text {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .upload-area-hint {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        /* Reduce gaps between elements */
        .sidebar-kicker {
            font-size: 0.75rem;
            margin-bottom: 0.25rem;
        }
        
        .app-title {
            font-size: 1.25rem;
        }
        
        .chat-header-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        /* Compact conversation items */
        .conversation-unread {
            padding: 0.125rem 0.375rem;
            font-size: 0.7rem;
        }
        
        .conversation-time {
            font-size: 0.75rem;
        }
        
        .conversation-preview {
            font-size: 0.8rem;
            margin-top: 0.125rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .chat-shell {
                padding: 0.25rem;
                gap: 0.5px;
            }
            
            .sidebar-panel {
                width: 280px;
                margin-top: 0.25rem;
            }
            
            .chat-panel {
                margin-top: 0.25rem;
            }
            
            .chat-header {
                padding: 0.5rem 0.75rem;
            }
            
            .message-container {
                padding: 0.25rem;
            }
            
            .chat-form {
                padding: 0.5rem 0.75rem;
            }
        }
    </style>
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
                            
                            <!-- Modern File Preview -->
                            <div id="attachmentPreview" class="attachment-preview">
                                <div class="attachment-preview-content">
                                    <div class="attachment-preview-icon" id="attachmentIcon">📎</div>
                                    <div class="attachment-preview-details">
                                        <span class="attachment-preview-name" id="attachmentName"></span>
                                        <span class="attachment-preview-size" id="attachmentSize"></span>
                                    </div>
                                    <button type="button" class="attachment-preview-remove" id="attachmentRemove" aria-label="Remove attachment">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Drag and Drop Area -->
                            <div class="upload-area" id="uploadArea">
                                <div class="upload-area-content">
                                    <div class="upload-area-icon">📁</div>
                                    <div class="upload-area-text">Drop file here</div>
                                    <div class="upload-area-hint">or click to browse</div>
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
</body>
</html>
