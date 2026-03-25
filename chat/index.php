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
    <title>Inbox - Internal Communication</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/main.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/layout.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/sidebar.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/chat.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/darkmode.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/saas2026.css')) ?>">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        /* 2026 SaaS Design - Modern Chat Interface */
        .chat-shell {
            height: calc(100vh - 0px);
            margin: 0;
            padding: 2rem;
            gap: 1.5rem;
            display: flex;
            align-items: stretch;
        }
        
        /* Sidebar Panel - Unchanged */
        .sidebar-panel {
            width: 320px;
            flex-shrink: 0;
        }
        
        /* Modern Chat Panel - 2026 SaaS Design */
        .chat-panel {
            flex: 1;
            min-width: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            box-shadow: 
                0 20px 25px -5px rgba(0, 0, 0, 0.1),
                0 10px 10px -5px rgba(0, 0, 0, 0.04),
                0 0 0 1px rgba(255, 255, 255, 0.05);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .chat-panel:hover {
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.15),
                0 20px 25px -5px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(255, 255, 255, 0.1);
        }
        
        /* Modern Chat Header */
        .chat-header {
            padding: 1.5rem 2rem;
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.9) 0%, 
                rgba(248, 250, 252, 0.8) 100%);
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            backdrop-filter: blur(10px);
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .chat-header-main h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.25rem;
            letter-spacing: -0.025em;
        }
        
        .chat-header-meta {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
        }
        
        .chat-header-badge {
            font-size: 0.75rem;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 50px;
            font-weight: 600;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
        }
        
        /* Modern Message Container */
        .message-container {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
            background: linear-gradient(180deg, 
                rgba(255, 255, 255, 0.5) 0%, 
                rgba(248, 250, 252, 0.3) 100%);
        }
        
        .message-container::-webkit-scrollbar {
            width: 6px;
        }
        
        .message-container::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .message-container::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.3);
            border-radius: 3px;
        }
        
        .message-container::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.5);
        }
        
        /* Modern Message Bubbles */
        .message-bubble {
            max-width: 60%;
            padding: 1rem 1.25rem;
            margin: 1rem 0;
            border-radius: 20px;
            font-size: 0.9rem;
            line-height: 1.6;
            box-shadow: 
                0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.2s ease;
            position: relative;
        }
        
        .message-bubble:hover {
            transform: translateY(-1px);
            box-shadow: 
                0 10px 15px -3px rgba(0, 0, 0, 0.1),
                0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        /* Sent Messages - Modern Blue */
        .message-bubble.sent {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .message-bubble.sent::before {
            content: '';
            position: absolute;
            bottom: 0;
            right: -8px;
            width: 0;
            height: 0;
            border-left: 8px solid #2563eb;
            border-bottom: 8px solid transparent;
        }
        
        /* Received Messages - Modern Card */
        .message-bubble.received {
            background: rgba(255, 255, 255, 0.9);
            color: #0f172a;
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-bottom-left-radius: 6px;
            backdrop-filter: blur(10px);
        }
        
        .message-bubble.received::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: -8px;
            width: 0;
            height: 0;
            border-right: 8px solid rgba(255, 255, 255, 0.9);
            border-bottom: 8px solid transparent;
        }
        
        .message-text {
            line-height: 1.6;
            word-wrap: break-word;
            font-weight: 400;
        }
        
        .message-meta {
            font-size: 0.75rem;
            margin-top: 0.5rem;
            opacity: 0.7;
            font-weight: 500;
        }
        
        /* Modern Chat Form */
        .chat-form {
            padding: 1.5rem 2rem;
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.95) 0%, 
                rgba(248, 250, 252, 0.9) 100%);
            border-top: 1px solid rgba(148, 163, 184, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .chat-form-composer {
            display: flex;
            align-items: flex-end;
            gap: 1rem;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(148, 163, 184, 0.2);
            box-shadow: 
                0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }
        
        .chat-form-composer:focus-within {
            border-color: #3b82f6;
            box-shadow: 
                0 0 0 3px rgba(59, 130, 246, 0.1),
                0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }
        
        /* Modern Action Buttons */
        .chat-attach-button,
        .chat-emoji-button {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .chat-attach-button:hover,
        .chat-emoji-button:hover {
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .chat-attach-button:active,
        .chat-emoji-button:active {
            transform: translateY(0);
        }
        
        /* Modern Input Area */
        .chat-input-wrap {
            flex: 1;
            position: relative;
        }
        
        .chat-input-wrap textarea {
            width: 100%;
            border: none;
            background: transparent;
            resize: none;
            font-size: 0.9rem;
            line-height: 1.5;
            padding: 0.75rem 0;
            outline: none;
            min-height: 44px;
            max-height: 120px;
            color: #0f172a;
            font-weight: 400;
        }
        
        .chat-input-wrap textarea::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }
        
        /* 2026 SaaS Attachment System */
        .chat-input-wrap.has-attachment {
            position: relative;
        }
        
        .chat-input-wrap.has-attachment textarea {
            background: 
                linear-gradient(135deg, 
                    rgba(59, 130, 246, 0.08) 0%, 
                    rgba(99, 102, 241, 0.04) 50%,
                    rgba(139, 92, 246, 0.02) 100%),
                linear-gradient(90deg, 
                    rgba(59, 130, 246, 0.1) 0%, 
                    transparent 100%);
            background-size: 100% 100%, 100% 2px;
            background-position: center, bottom;
            background-repeat: no-repeat;
            border: 1px solid rgba(59, 130, 246, 0.15);
            border-radius: 16px;
            padding: 1rem 1rem 1rem 1rem;
            box-shadow: 
                0 4px 6px -1px rgba(59, 130, 246, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .chat-input-wrap.has-attachment textarea:focus {
            border-color: rgba(59, 130, 246, 0.3);
            box-shadow: 
                0 0 0 3px rgba(59, 130, 246, 0.1),
                0 4px 6px -1px rgba(59, 130, 246, 0.15),
                0 2px 4px -1px rgba(0, 0, 0, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }
        
        /* 2026 SaaS File Indicator - Inside Textarea */
        .attachment-indicator {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: linear-gradient(135deg, 
                rgba(59, 130, 246, 0.95) 0%, 
                rgba(99, 102, 241, 0.9) 100%);
            color: white;
            padding: 0.375rem 0.625rem;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 0.375rem;
            backdrop-filter: blur(10px);
            box-shadow: 
                0 4px 8px -2px rgba(59, 130, 246, 0.25),
                0 2px 4px -1px rgba(0, 0, 0, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            z-index: 15;
            animation: slideInRight 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .chat-input-wrap.has-attachment .attachment-indicator {
            display: flex;
        }
        
        .attachment-indicator-icon {
            width: 14px;
            height: 14px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.5625rem;
        }
        
        .attachment-indicator-text {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100px;
        }
        
        /* 2026 SaaS Remove Button - Inside Textarea */
        .attachment-remove-btn {
            position: absolute;
            top: 0.375rem;
            right: 0.375rem;
            width: 24px;
            height: 24px;
            border: none;
            background: linear-gradient(135deg, 
                rgba(239, 68, 68, 0.95) 0%, 
                rgba(220, 38, 38, 0.9) 100%);
            color: white;
            border-radius: 50%;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 0.6875rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 20;
            box-shadow: 
                0 3px 6px -2px rgba(239, 68, 68, 0.25),
                0 2px 4px -1px rgba(0, 0, 0, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }
        
        .chat-input-wrap.has-attachment .attachment-remove-btn {
            display: flex;
            animation: scaleIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .attachment-remove-btn:hover {
            background: linear-gradient(135deg, 
                rgba(220, 38, 38, 1) 0%, 
                rgba(185, 28, 28, 0.95) 100%);
            transform: scale(1.05);
            box-shadow: 
                0 6px 12px -3px rgba(239, 68, 68, 0.35),
                0 3px 6px -2px rgba(0, 0, 0, 0.12),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }
        
        .attachment-remove-btn:active {
            transform: scale(0.95);
            box-shadow: 
                0 2px 4px -1px rgba(239, 68, 68, 0.2),
                inset 0 1px 0 rgba(0, 0, 0, 0.1);
        }
        
        /* 2026 SaaS Animations */
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(15px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }
        
        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }
        
        .chat-input-wrap.has-attachment textarea {
            animation: pulse 2s ease-in-out infinite;
        }
        
        /* Modern Upload Area */
        .upload-area {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(59, 130, 246, 0.05);
            border: 2px dashed #3b82f6;
            border-radius: 20px;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(5px);
        }
        
        .upload-area.active {
            display: flex;
        }
        
        .upload-area-content {
            text-align: center;
            color: #3b82f6;
        }
        
        .upload-area-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .upload-area-text {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        
        .upload-area-hint {
            font-size: 0.875rem;
            opacity: 0.8;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .chat-shell {
                padding: 1rem;
                gap: 1rem;
            }
            
            .sidebar-panel {
                width: 280px;
            }
            
            .message-container {
                padding: 1rem;
            }
            
            .chat-form {
                padding: 1rem;
            }
            
            .chat-header {
                padding: 1rem 1.5rem;
                min-height: 70px;
            }
            
            .message-bubble {
                max-width: 75%;
                font-size: 0.875rem;
            }
            
            .chat-form-composer {
                padding: 0.5rem 0.75rem;
                gap: 0.75rem;
            }
            
            .chat-attach-button,
            .chat-emoji-button {
                width: 40px;
                height: 40px;
            }
        }
        
        /* Modern Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .message-bubble.sent {
            animation: slideInRight 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .message-bubble.received {
            animation: slideInLeft 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Focus States for Accessibility */
        .chat-attach-button:focus,
        .chat-emoji-button:focus,
        .attachment-preview-remove:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }
        
        /* Typography System */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.025em;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #64748b;
            font-weight: 500;
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
                        <h1 class="app-title mb-1">Chat</h1>
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
                        <div>
                            <h2 id="activeConversationName" class="h5 mb-1">Select a conversation</h2>
                            <p id="activeConversationMeta" class="small text-muted mb-0"><?= e($conversationHint) ?></p>
                        </div>
                        <button type="button" class="chat-sidebar-toggle" id="chatSidebarToggle" aria-label="Toggle chat list" aria-controls="chatSidebar" aria-expanded="true">
                            <span></span><span></span><span></span>
                        </button>
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
                            
                            <!-- 2026 SaaS Attachment Indicator -->
                            <div class="attachment-indicator" id="attachmentIndicator">
                                <div class="attachment-indicator-icon">📎</div>
                                <span class="attachment-indicator-text" id="attachmentIndicatorText">File attached</span>
                            </div>
                            
                            <!-- 2026 SaaS Remove Attachment Button -->
                            <button type="button" class="attachment-remove-btn" id="attachmentRemoveBtn" aria-label="Remove attachment">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 6L6 18M6 6l12 12"/>
                                </svg>
                            </button>
                            
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
<script src="<?= e(baseUrl('/assets/js/darkmode.js')) ?>"></script>
</body>
</html>
