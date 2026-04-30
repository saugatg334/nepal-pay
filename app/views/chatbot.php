<?php
/**
 * NepalPay Sathi — Smart Chatbot Widget
 * 
 * Include this file at the bottom of any NepalPay page:
 * require_once __DIR__ . '/chatbot.php';
 * 
 * Features:
 * - Floating launcher button (bottom-right)
 * - Premium chat popup with glassmorphism
 * - Voice input, emoji picker, quick replies
 * - CSRF-secured API calls
 * - Responsive (full-screen on mobile)
 */
?>
<!-- NepalPay Sathi Chatbot Widget -->
<link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/chatbot.css">
<script>
window.APP_URL = '<?= APP_URL ?>';
window.SATHI_CSRF = '<?= \NepalPay\Helpers\CSRF::getToken() ?>';
window.SATHI_USER = <?= isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'null' ?>;
</script>

<!-- Floating Launcher -->
<button class="sathi-launcher" id="sathi-launcher" aria-label="Open chat">
    🤖
    <span class="sathi-badge" id="sathi-badge" style="display:none">1</span>
</button>

<!-- Chat Window -->
<div class="sathi-chat" id="sathi-chat" role="dialog" aria-label="NepalPay Sathi Chat">
    
    <!-- Header -->
    <div class="sathi-header">
        <div class="sathi-avatar">🤖</div>
        <div class="sathi-title">
            <h3>NepalPay Sathi</h3>
            <p>🟢 Online — Replies instantly</p>
        </div>
        <div class="sathi-header-btns">
            <button class="sathi-header-btn" id="sathi-download" title="Download chat history" onclick="SathiChat.downloadChat()">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </button>
            <button class="sathi-header-btn" id="sathi-minimize" title="Minimize">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <button class="sathi-header-btn" id="sathi-close" title="Close">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    
    <!-- Messages Area -->
    <div class="sathi-messages" id="sathi-messages">
        <!-- Welcome message rendered by JS -->
    </div>
    
    <!-- Quick Replies -->
    <div class="sathi-quick-replies" id="sathi-quick-replies" style="display:none"></div>
    
    <!-- Suggested Replies -->
    <div class="sathi-suggestions" id="sathi-suggestions">
        <button class="sathi-suggestion-chip">Balance check</button>
        <button class="sathi-suggestion-chip">Send money</button>
        <button class="sathi-suggestion-chip">Pay bill</button>
        <button class="sathi-suggestion-chip">Reset PIN</button>
        <button class="sathi-suggestion-chip">Today's offers</button>
    </div>
    
    <!-- Input Area -->
    <div class="sathi-input-area">
        <button class="sathi-emoji-btn" id="sathi-emoji" title="Emoji">😊</button>
        <input 
            type="text" 
            class="sathi-input" 
            id="sathi-input" 
            placeholder="Type a message..." 
            autocomplete="off"
            maxlength="500"
        >
        <button class="sathi-mic-btn" id="sathi-mic" title="Voice input">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
            </svg>
        </button>
        <button class="sathi-send-btn" id="sathi-send" title="Send">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
            </svg>
        </button>
    </div>

<script src="<?= APP_URL ?>/public/assets/js/chatbot.js" defer></script>
<!-- /NepalPay Sathi Chatbot Widget -->
