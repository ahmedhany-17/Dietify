/**
 * Healthy Food Assistant — Chatbot Client
 * ────────────────────────────────────────
 */
(function () {
    'use strict';

    const API_URL = '/app/chatbot_api.php';

    // ── DOM refs ──────────────────────────────────────────────────────────
    const bubble = document.getElementById('chatbot-bubble');
    const badge = document.getElementById('chatbot-badge');
    const chatWindow = document.getElementById('chatbot-window');
    const msgArea = document.getElementById('chatbot-messages');
    const textarea = document.getElementById('chatbot-input');
    const sendBtn = document.getElementById('chatbot-send');
    const clearBtn = document.getElementById('chatbot-clear');
    const dietPills = document.querySelectorAll('.item-goal-widget');
    const toolPills = document.querySelectorAll('.item-tool-widget');
    const typingEl = document.getElementById('chatbot-typing');

    let isOpen = false;
    let isLoading = false;
    let hasLoadedHistory = false;
    let selectedGoal = 'general';
    let selectedTool = null;

    // ── Toggle chat window ───────────────────────────────────────────────
    bubble.addEventListener('click', () => {
        isOpen = !isOpen;
        chatWindow.classList.toggle('is-visible', isOpen);
        bubble.classList.toggle('is-open', isOpen);
        badge.classList.remove('show');

        if (isOpen && !hasLoadedHistory) {
            loadHistory();
            hasLoadedHistory = true;
        }
        if (isOpen) textarea.focus();
    });

    // ── Send message ─────────────────────────────────────────────────────
    sendBtn.addEventListener('click', sendMessage);
    textarea.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Auto-resize textarea
    textarea.addEventListener('input', () => {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 90) + 'px';
    });

    // ── Independent Selection Logic ──────────────────────────────────────
    dietPills.forEach(pill => {
        pill.addEventListener('click', () => {
            selectedGoal = pill.dataset.goal;
            dietPills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            updateState({ goal: selectedGoal });
        });
    });

    toolPills.forEach(pill => {
        pill.addEventListener('click', () => {
            const tool = pill.dataset.tool;
            if (selectedTool === tool) {
                selectedTool = null; // Toggle off
                pill.classList.remove('active');
                updateState({ tool: 'none' });
            } else {
                selectedTool = tool;
                toolPills.forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                updateState({ tool: selectedTool });
            }
        });
    });

    async function updateState(data) {
        try {
            await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'set_goal', ...data }),
            });
        } catch (e) { console.error('State sync failed', e); }
    }

    // ── Clear chat ───────────────────────────────────────────────────────
    clearBtn.addEventListener('click', () => {
        if (confirm("Are you sure you want to clear the chat history?")) {
            fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'clear' }),
            });
            const msgs = msgArea.querySelectorAll('.chat-msg, .chatbot-error');
            msgs.forEach(m => m.remove());
        }
    });

    // ── Load history on first open ───────────────────────────────────────
    async function loadHistory() {
        try {
            const res = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_history' }),
            });
            const data = await res.json();

            // Sync UI active states
            if (data.goal) {
                selectedGoal = data.goal;
                dietPills.forEach(p => p.classList.toggle('active', p.dataset.goal === data.goal));
            }
            if (data.tool) {
                selectedTool = data.tool;
                toolPills.forEach(p => p.classList.toggle('active', p.dataset.tool === data.tool));
            }

            // Update online status
            const statusDot = chatWindow.querySelector('.status-dot');
            const statusText = chatWindow.querySelector('.chatbot-header-info span');
            if (data.service_online) {
                if (statusDot) statusDot.style.background = '#4ade80';
                if (statusText) statusText.innerHTML = '<span class="status-dot" style="background: #4ade80"></span> Online';
            } else {
                if (statusDot) statusDot.style.background = '#e74c3c';
                if (statusText) statusText.innerHTML = '<span class="status-dot" style="background: #e74c3c; animation: none;"></span> Offline (Check .env)';
            }

            // Render history
            if (data.history && data.history.length > 0) {
                data.history.forEach(msg => {
                    appendMessage(msg.role === 'user' ? 'user' : 'bot', msg.content, false);
                });
                scrollToBottom();
            }
        } catch (e) {
            console.error('Failed to load chat history:', e);
        }
    }

    // ── Send message ─────────────────────────────────────────────────────
    async function sendMessage(retryText = null, showBubble = false) {
        // Ensure we don't treat the Click Event as the message text
        const isEvent = retryText && (retryText instanceof Event || retryText.nativeEvent);
        const text = (retryText !== null && !isEvent) ? retryText : textarea.value;

        if (!text.trim() || isLoading) return;

        // Show user message in UI if it's a new message or an interactive choice
        if (retryText === null || showBubble) {
            appendMessage('user', text);
            if (retryText === null) {
                textarea.value = '';
                textarea.style.height = 'auto';
            }
        }

        setLoading(true);

        try {
            const res = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'message', message: text }),
            });

            const data = await res.json();

            if (data.error) {
                showError(data.error, text);
            } else {
                appendMessage('bot', data.reply);
                // If chat is closed, show badge
                if (!isOpen) {
                    badge.classList.add('show');
                }
            }
        } catch (e) {
            showError('Network error. Please check your connection.', text);
        } finally {
            setLoading(false);
        }
    }

    // ── Set goal ─────────────────────────────────────────────────────────
    async function setGoal(goal) {
        try {
            await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'set_goal', goal: goal }),
            });
        } catch (e) {
            console.error('Failed to set goal:', e);
        }
    }

    // ── Append message bubble ────────────────────────────────────────────
    function appendMessage(type, content, animate = true) {
        const wrapper = document.createElement('div');
        wrapper.className = `chat-msg ${type}`;
        if (!animate) wrapper.style.animation = 'none';

        const avatar = document.createElement('div');
        avatar.className = 'msg-avatar';
        avatar.textContent = type === 'bot' ? '🥗' : '👤';

        const bubble = document.createElement('div');
        bubble.className = 'msg-bubble';
        bubble.innerHTML = formatContent(content);

        const time = document.createElement('span');
        time.className = 'msg-time';
        time.textContent = getTimeStr();
        bubble.appendChild(time);

        wrapper.appendChild(avatar);
        wrapper.appendChild(bubble);

        // Add interactive options if this is a bot message asking for duration
        if (type === 'bot' && (content.includes('3-day') || content.includes('7-day'))) {
            addOptions(bubble, ['3-day', '7-day']);
        }

        // Insert before typing indicator
        msgArea.insertBefore(wrapper, typingEl);
        scrollToBottom();
    }

    // ── Add interactive options to bubble ───────────────────────────────
    function addOptions(bubble, options) {
        const optionsContainer = document.createElement('div');
        optionsContainer.className = 'chat-options';

        options.forEach(opt => {
            const btn = document.createElement('button');
            btn.className = 'option-btn';
            btn.innerHTML = (opt === '3-day' ? '📅 ' : '📅 ') + opt;

            btn.addEventListener('click', () => {
                // Send as a message
                sendMessage(opt, true);

                // Highlight and disable
                btn.classList.add('selected');
                const siblings = optionsContainer.querySelectorAll('.option-btn');
                siblings.forEach(s => s.disabled = true);
            });

            optionsContainer.appendChild(btn);
        });

        bubble.appendChild(optionsContainer);
    }

    // ── Format bot text ──────────────────────────────────────────────────
    function formatContent(text) {
        // Convert **bold** to <strong>
        text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        // Convert *italic* to <em>
        text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');
        // Convert newlines to <br>
        text = text.replace(/\n/g, '<br>');
        // Wrap in paragraphs
        return '<p>' + text + '</p>';
    }

    // ── Helpers ──────────────────────────────────────────────────────────
    function scrollToBottom() {
        requestAnimationFrame(() => {
            msgArea.scrollTop = msgArea.scrollHeight;
        });
    }

    function setLoading(state) {
        isLoading = state;
        sendBtn.disabled = state;
        textarea.disabled = state;
        typingEl.classList.toggle('show', state);
        if (state) scrollToBottom();
        if (!state) textarea.focus();
    }

    function showError(msg, retryText = null) {
        const el = document.createElement('div');
        el.className = 'chatbot-error';
        el.innerHTML = `<span>${msg}</span>`;

        if (retryText) {
            const retryBtn = document.createElement('button');
            retryBtn.className = 'chatbot-retry-btn';
            retryBtn.innerHTML = '<i class="fas fa-redo"></i> Retry';
            retryBtn.onclick = () => {
                el.remove();
                sendMessage(retryText);
            };
            el.appendChild(retryBtn);
        }

        msgArea.insertBefore(el, typingEl);
        scrollToBottom();

        // Only auto-remove if NOT a retryable error
        if (!retryText) {
            setTimeout(() => el.remove(), 6000);
        }
    }

    function getTimeStr() {
        const now = new Date();
        return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

})();
