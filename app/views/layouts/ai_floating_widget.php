<?php
/**
 * Floating AI Assistant Chat Widget
 * Accessible across all pages for authenticated users (Super User, Bendahara, Pengawas, Member)
 */

if (!\App\Core\Auth::check()) {
    return;
}

$userRole = \App\Core\Auth::role();
$username = \App\Core\Auth::username() ?? 'Pengguna';
?>

<!-- Floating AI Chat Styles -->
<style>
/* Floating AI Trigger Button */
.ai-floating-trigger {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: #ffffff;
    border: none;
    outline: none;
    cursor: pointer;
    box-shadow: 0 8px 24px rgba(79, 70, 229, 0.40);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    z-index: 1050;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.ai-floating-trigger:hover {
    transform: scale(1.08) translateY(-2px);
    box-shadow: 0 12px 30px rgba(79, 70, 229, 0.55);
    color: #ffffff;
}
.ai-floating-trigger:active {
    transform: scale(0.95);
}
.ai-floating-badge {
    position: absolute;
    top: -3px;
    right: -3px;
    background: #fbbf24;
    color: #1e1b4b;
    font-size: 0.62rem;
    font-weight: 800;
    border-radius: 10px;
    padding: 2px 6px;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    letter-spacing: 0.04em;
}

/* Floating AI Chat Window */
.ai-floating-window {
    position: fixed;
    bottom: 96px;
    right: 24px;
    width: 380px;
    height: 530px;
    max-height: calc(100vh - 120px);
    background: #ffffff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 48px rgba(15, 23, 42, 0.22), 0 0 0 1px rgba(226, 232, 240, 0.85);
    display: flex;
    flex-direction: column;
    z-index: 1050;
    transform-origin: bottom right;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    opacity: 0;
    pointer-events: none;
    transform: scale(0.85) translateY(20px);
}
.ai-floating-window.show {
    opacity: 1;
    pointer-events: auto;
    transform: scale(1) translateY(0);
}

/* Header */
.ai-floating-header {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4338ca 100%);
    padding: 14px 18px;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.ai-header-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, #818cf8, #c084fc);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    position: relative;
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.25);
    flex-shrink: 0;
}
.ai-header-status {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #4ade80;
    border: 2px solid #1e1b4b;
    position: absolute;
    bottom: 0;
    right: 0;
}

/* Chat Messages Container */
.ai-floating-body {
    flex: 1;
    overflow-y: auto;
    padding: 14px;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
    gap: 12px;
    scroll-behavior: smooth;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}
.ai-floating-body::-webkit-scrollbar {
    width: 5px;
}
.ai-floating-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

/* Bubbles */
.ai-chat-row {
    display: flex;
    gap: 8px;
    max-width: 100%;
}
.ai-chat-row.user-row {
    justify-content: flex-end;
}
.ai-bubble-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    flex-shrink: 0;
    margin-top: 2px;
}
.ai-bubble-avatar.ai-av {
    background: linear-gradient(135deg, #818cf8, #c084fc);
    color: #ffffff;
}
.ai-bubble-avatar.user-av {
    background: #2563eb;
    color: #ffffff;
}
.ai-bubble-wrapper {
    max-width: 82%;
    display: flex;
    flex-direction: column;
}
.ai-chat-row.user-row .ai-bubble-wrapper {
    align-items: flex-end;
}
.ai-bubble {
    padding: 10px 14px;
    border-radius: 16px;
    font-size: 0.85rem;
    line-height: 1.45;
    word-break: break-word;
}
.ai-bubble.user {
    background: #2563eb;
    color: #ffffff;
    border-bottom-right-radius: 4px;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
}
.ai-bubble.bot {
    background: #ffffff;
    color: #1e293b;
    border: 1px solid #e2e8f0;
    border-bottom-left-radius: 4px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}
.ai-bubble.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
    border-bottom-left-radius: 4px;
}
.ai-bubble-time {
    font-size: 0.68rem;
    color: #94a3b8;
    margin-top: 3px;
    padding: 0 4px;
}

/* Suggestion Chips */
.ai-suggestions-container {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-top: 4px;
}
.ai-suggestion-chip {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 6px 12px;
    font-size: 0.78rem;
    color: #3b82f6;
    cursor: pointer;
    text-align: left;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}
.ai-suggestion-chip:hover {
    background: #eff6ff;
    border-color: #bfdbfe;
    color: #1d4ed8;
    transform: translateX(2px);
}

/* Typing indicator */
.ai-typing-indicator {
    display: none;
    align-items: center;
    gap: 5px;
    padding: 10px 14px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    border-bottom-left-radius: 4px;
    width: fit-content;
}
.ai-typing-dot {
    width: 6px;
    height: 6px;
    background: #94a3b8;
    border-radius: 50%;
    animation: aiBounce 1.4s infinite ease-in-out both;
}
.ai-typing-dot:nth-child(1) { animation-delay: -0.32s; }
.ai-typing-dot:nth-child(2) { animation-delay: -0.16s; }
@keyframes aiBounce {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}

/* Input Area */
.ai-floating-footer {
    padding: 10px 14px;
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
}
.ai-input-group {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 24px;
    padding: 4px 6px 4px 14px;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.ai-input-group:focus-within {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    background: #ffffff;
}
.ai-chat-input {
    border: none;
    background: transparent;
    outline: none;
    width: 100%;
    font-size: 0.85rem;
    color: #1e293b;
    resize: none;
    max-height: 80px;
    padding: 4px 0;
}
.ai-send-btn {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #4f46e5;
    color: #ffffff;
    border: none;
    outline: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    cursor: pointer;
    flex-shrink: 0;
    transition: all 0.2s ease;
}
.ai-send-btn:hover:not(:disabled) {
    background: #4338ca;
    transform: scale(1.05);
}
.ai-send-btn:disabled {
    background: #cbd5e1;
    cursor: not-allowed;
}

/* Mobile Responsiveness */
@media (max-width: 576px) {
    .ai-floating-window {
        width: calc(100vw - 32px);
        right: 16px;
        bottom: 86px;
        height: calc(100vh - 110px);
        max-height: 520px;
    }
    .ai-floating-trigger {
        bottom: 16px;
        right: 16px;
        width: 54px;
        height: 54px;
        font-size: 1.35rem;
    }
}
</style>

<!-- Floating AI Chat Button Trigger -->
<button type="button" class="ai-floating-trigger shadow-lg" id="aiFloatingTrigger" aria-label="Buka Asisten AI" title="Asisten AI (Klik untuk membuka)">
    <i class="bi bi-stars" id="aiTriggerIcon"></i>
    <span class="ai-floating-badge">AI</span>
</button>

<!-- Floating AI Chat Panel Window -->
<div class="ai-floating-window" id="aiFloatingWindow" role="dialog" aria-labelledby="aiWindowTitle" aria-hidden="true">
    <!-- Header -->
    <div class="ai-floating-header">
        <div class="d-flex align-items-center gap-2 overflow-hidden">
            <div class="ai-header-avatar">
                <i class="bi bi-robot"></i>
                <div class="ai-header-status"></div>
            </div>
            <div class="overflow-hidden">
                <div class="fw-bold text-white small lh-1 text-truncate" id="aiWindowTitle">Asisten AI</div>
                <small class="text-white-50" style="font-size: 0.72rem;">Sistem Keuangan Kas</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-1">
            <button type="button" class="btn btn-sm btn-outline-light border-0 p-1 rounded-circle text-white-50 hover-white" id="aiClearHistoryBtn" title="Bersihkan riwayat percakapan" aria-label="Bersihkan chat" style="width: 28px; height: 28px;">
                <i class="bi bi-arrow-clockwise fs-6"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-light border-0 p-1 rounded-circle text-white-50 hover-white" id="aiCloseWindowBtn" title="Tutup Asisten AI" aria-label="Tutup Asisten AI" style="width: 28px; height: 28px;">
                <i class="bi bi-x-lg fs-6"></i>
            </button>
        </div>
    </div>

    <!-- Messages Container -->
    <div class="ai-floating-body" id="aiFloatingBody">
        <!-- Initial Greeting -->
        <div class="ai-chat-row">
            <div class="ai-bubble-avatar ai-av">
                <i class="bi bi-robot"></i>
            </div>
            <div class="ai-bubble-wrapper">
                <div class="ai-bubble bot">
                    <p class="mb-1"><strong>Halo, <?= e($username) ?>!</strong></p>
                    <p class="mb-2">Saya <strong>Asisten AI pada Sistem Keuangan Kas</strong>. Saya siap membantu Anda memahami saldo kas, mutasi pemasukan, pengeluaran, status pembayaran iuran, atau laporan keuangan.</p>
                    <div class="ai-suggestions-container">
                        <div class="ai-suggestion-chip" data-query="Berapa saldo kas saat ini?">
                            <i class="bi bi-wallet2 text-primary"></i> Berapa saldo kas saat ini?
                        </div>
                        <div class="ai-suggestion-chip" data-query="Bagaimana kondisi keuangan bulan ini?">
                            <i class="bi bi-graph-up text-success"></i> Bagaimana kondisi keuangan bulan ini?
                        </div>
                        <div class="ai-suggestion-chip" data-query="Siapa kamu dan apa tugas kamu?">
                            <i class="bi bi-info-circle text-info"></i> Siapa kamu dan apa tugas kamu?
                        </div>
                    </div>
                </div>
                <span class="ai-bubble-time"><?= date('H:i') ?></span>
            </div>
        </div>

        <!-- Dynamic message bubbles will be inserted here -->
        <div id="aiDynamicMessages"></div>

        <!-- Typing Indicator -->
        <div class="ai-chat-row" id="aiFloatingTypingRow" style="display: none;">
            <div class="ai-bubble-avatar ai-av">
                <i class="bi bi-robot"></i>
            </div>
            <div class="ai-bubble-wrapper">
                <div class="ai-typing-indicator" id="aiFloatingTyping">
                    <div class="ai-typing-dot"></div>
                    <div class="ai-typing-dot"></div>
                    <div class="ai-typing-dot"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Input Form -->
    <div class="ai-floating-footer">
        <form id="aiFloatingForm" action="<?= base_url('ai/ask') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateCsrfToken() ?>">
            <div class="ai-input-group">
                <input type="text" class="ai-chat-input" id="aiFloatingInput" name="question" placeholder="Tanya tentang kas..." autocomplete="off" maxlength="1000">
                <button type="submit" class="ai-send-btn" id="aiFloatingSendBtn" aria-label="Kirim Pesan">
                    <i class="bi bi-send-fill" id="aiFloatingSendIcon"></i>
                    <span class="spinner-border spinner-border-sm" id="aiFloatingSendSpinner" style="display: none; width: 14px; height: 14px;" role="status"></span>
                </button>
            </div>
        </form>
        <div class="text-center mt-1">
            <small class="text-muted" style="font-size: 0.68rem;">Asisten AI &bull; Pengelolaan Kas & Panduan Sistem</small>
        </div>
    </div>
</div>

<!-- Floating AI JavaScript Interaction -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const triggerBtn = document.getElementById('aiFloatingTrigger');
    const triggerIcon = document.getElementById('aiTriggerIcon');
    const windowEl = document.getElementById('aiFloatingWindow');
    const closeBtn = document.getElementById('aiCloseWindowBtn');
    const clearBtn = document.getElementById('aiClearHistoryBtn');
    const form = document.getElementById('aiFloatingForm');
    const input = document.getElementById('aiFloatingInput');
    const sendBtn = document.getElementById('aiFloatingSendBtn');
    const sendIcon = document.getElementById('aiFloatingSendIcon');
    const sendSpinner = document.getElementById('aiFloatingSendSpinner');
    const bodyContainer = document.getElementById('aiFloatingBody');
    const dynamicMessages = document.getElementById('aiDynamicMessages');
    const typingRow = document.getElementById('aiFloatingTypingRow');
    const typingIndicator = document.getElementById('aiFloatingTyping');

    if (!triggerBtn || !windowEl || !form) return;

    let isOpen = false;

    function openChat() {
        isOpen = true;
        windowEl.classList.add('show');
        windowEl.setAttribute('aria-hidden', 'false');
        triggerIcon.className = 'bi bi-x-lg';
        triggerBtn.setAttribute('aria-expanded', 'true');
        setTimeout(() => {
            input.focus();
            scrollToBottom();
        }, 150);
    }

    function closeChat() {
        isOpen = false;
        windowEl.classList.remove('show');
        windowEl.setAttribute('aria-hidden', 'true');
        triggerIcon.className = 'bi bi-stars';
        triggerBtn.setAttribute('aria-expanded', 'false');
    }

    triggerBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        if (isOpen) {
            closeChat();
        } else {
            openChat();
        }
    });

    closeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        closeChat();
    });

    // Close when clicking outside of floating window or trigger
    document.addEventListener('click', (e) => {
        if (isOpen && !windowEl.contains(e.target) && !triggerBtn.contains(e.target)) {
            closeChat();
        }
    });

    // ESC key closes window
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isOpen) {
            closeChat();
        }
    });

    function scrollToBottom() {
        bodyContainer.scrollTop = bodyContainer.scrollHeight;
    }

    function getNowTime() {
        const d = new Date();
        return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function parseMarkdown(text) {
        if (!text) return '';
        let safe = escapeHtml(text);

        // Headings
        safe = safe.replace(/^### (.*$)/gim, '<h6 class="fw-bold mt-2 mb-1 text-primary">$1</h6>');
        safe = safe.replace(/^## (.*$)/gim, '<h6 class="fw-bold mt-2 mb-1 text-dark">$1</h6>');
        safe = safe.replace(/^# (.*$)/gim, '<h6 class="fw-bold mt-2 mb-1 text-dark">$1</h6>');

        // Bold & Italic
        safe = safe.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        safe = safe.replace(/\*(.*?)\*/g, '<em>$1</em>');

        // Lists
        safe = safe.replace(/^[-*]\s+(.*)$/gim, '<li class="small mb-1">$1</li>');
        safe = safe.replace(/(<li[\s\S]*?<\/li>)/g, '<ul class="ps-3 mb-2 small">$1</ul>');

        // Line breaks
        safe = safe.replace(/\n{2,}/g, '<br><br>');
        safe = safe.replace(/\n/g, '<br>');

        return safe;
    }

    function appendUserMessage(text) {
        const row = document.createElement('div');
        row.className = 'ai-chat-row user-row';
        row.innerHTML = `
            <div class="ai-bubble-wrapper">
                <div class="ai-bubble user">${escapeHtml(text)}</div>
                <span class="ai-bubble-time">${getNowTime()}</span>
            </div>
            <div class="ai-bubble-avatar user-av">
                <i class="bi bi-person-fill"></i>
            </div>
        `;
        dynamicMessages.appendChild(row);
        scrollToBottom();
    }

    function appendAiMessage(response, isError = false) {
        const row = document.createElement('div');
        row.className = 'ai-chat-row';
        const formatted = isError ? `<i class="bi bi-exclamation-triangle-fill me-1"></i> ${escapeHtml(response)}` : parseMarkdown(response);
        row.innerHTML = `
            <div class="ai-bubble-avatar ai-av">
                <i class="bi bi-robot"></i>
            </div>
            <div class="ai-bubble-wrapper">
                <div class="ai-bubble ${isError ? 'error' : 'bot'}">${formatted}</div>
                <span class="ai-bubble-time">${getNowTime()}</span>
            </div>
        `;
        dynamicMessages.appendChild(row);
        scrollToBottom();
    }

    // Clear messages
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            dynamicMessages.innerHTML = '';
            scrollToBottom();
        });
    }

    // Suggestion chips click handler
    document.querySelectorAll('.ai-suggestion-chip').forEach((chip) => {
        chip.addEventListener('click', function() {
            const query = this.getAttribute('data-query');
            if (query) {
                input.value = query;
                form.dispatchEvent(new Event('submit'));
            }
        });
    });

    // Form submit handler
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const query = input.value.trim();
        if (!query) return;

        appendUserMessage(query);
        input.value = '';
        input.disabled = true;
        sendBtn.disabled = true;
        sendIcon.style.display = 'none';
        sendSpinner.style.display = 'inline-block';

        // Show typing indicator
        typingRow.style.display = 'flex';
        typingIndicator.style.display = 'flex';
        scrollToBottom();

        // Get CSRF Token
        const metaCsrf = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = metaCsrf ? metaCsrf.getAttribute('content') : (form.querySelector('input[name="csrf_token"]')?.value || '');

        const formData = new FormData();
        formData.append('question', query);
        formData.append('csrf_token', csrfToken);
        formData.append('ajax', '1');

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Server merespons status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            typingRow.style.display = 'none';
            typingIndicator.style.display = 'none';
            if (data.success && data.response) {
                appendAiMessage(data.response, false);
            } else {
                appendAiMessage(data.message || 'Maaf, terjadi kendala dalam memproses pertanyaan Anda.', true);
            }
        })
        .catch(err => {
            typingRow.style.display = 'none';
            typingIndicator.style.display = 'none';
            // Show detailed error for debugging while keeping user-friendly message
            console.error('AI request error:', err);
            const msg = err && err.message ? err.message : 'Gagal terhubung ke server AI. Silakan periksa koneksi atau coba sesaat lagi.';
            appendAiMessage(msg, true);
        })
        .finally(() => {
            input.disabled = false;
            sendBtn.disabled = false;
            sendIcon.style.display = 'inline-block';
            sendSpinner.style.display = 'none';
            input.focus();
            scrollToBottom();
        });
    });
});
</script>
