<?php
require_once __DIR__ . '/header.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    header("Location: /app/login.php");
    exit;
}
?>

<div class="chat-page-container">
    <div class="chat-page-header">
        <h1>Healthy Food Assistant</h1>
        <p>Your personal guide to healthy, affordable, and nutritious meals.</p>
    </div>

    <!-- The Chat Window Embedded in the Page -->
    <div id="page-chatbot-window" class="chatbot-window static-mode is-visible">
        <div class="chatbot-header">
            <div class="chatbot-avatar">🥗</div>
            <div class="chatbot-header-info">
                <h3>Food Assistant</h3>
                <span><span class="status-dot"></span> Online</span>
            </div>
            <div class="chatbot-header-actions">
                <button id="chatbot-clear-page" title="Clear chat"><i class="fas fa-trash-alt"></i></button>
            </div>
        </div>

        <div class="chatbot-selector-container">
            <!-- Row 1: Diet Goals -->
            <div class="chatbot-goal-bar" style="justify-content: center; padding: 8px 12px 4px;">
                <button class="goal-pill item-goal-page active" data-goal="general">🍽️ General</button>
                <button class="goal-pill item-goal-page" data-goal="weight_loss">⚖️ Weight Loss</button>
                <button class="goal-pill item-goal-page" data-goal="muscle_gain">💪 Muscle Gain</button>
            </div>
            <!-- Row 2: Tools -->
            <div class="chatbot-goal-bar" style="justify-content: center; padding: 4px 12px 8px;">
                <button class="goal-pill item-tool-page" data-tool="recipe_creator">🍳 Recipes</button>
                <button class="goal-pill item-tool-page" data-tool="meal_planner">📅 Meal Planner</button>
            </div>
        </div>

        <div id="chatbot-messages-page" class="chatbot-messages">
            <div class="chatbot-welcome">
                <span class="welcome-emoji">👋</span>
                <h4>Hi there!</h4>
                <p>I'm your Healthy Food Assistant.<br>Ask me about meals, nutrition, or set a goal above!</p>
            </div>

            <div id="chatbot-typing-page" class="typing-indicator">
                <div class="msg-avatar">🥗</div>
                <div class="typing-dots">
                    <span></span><span></span><span></span>
                </div>
            </div>
        </div>

        <div class="chatbot-input-area">
            <textarea id="chatbot-input-page" placeholder="Ask about healthy meals..." rows="1"></textarea>
            <button id="chatbot-send-page" class="chatbot-send-btn" aria-label="Send message">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<style>
    .chat-page-container {
        max-width: 800px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .chat-page-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .chat-page-header h1 {
        color: #333;
        font-size: 2.5rem;
        margin-bottom: 10px;
    }

    .chat-page-header p {
        color: #666;
        font-size: 1.1rem;
    }

    /* Modify existing chatbot window styles for static mode */
    .chatbot-window.static-mode {
        position: relative;
        bottom: auto;
        right: auto;
        width: 100%;
        max-height: 600px;
        opacity: 1;
        transform: none;
        pointer-events: auto;
        margin: 0 auto;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: 1px solid #eee;
    }

    /* Hide the floating bubble on this page */
    .chatbot-bubble {
        display: none !important;
    }

    /* Hide the floating window in header as well to avoid conflicts */
    #chatbot-window:not(.static-mode) {
        display: none !important;
    }
</style>

<script>
    // Initialize the page version of the chatbot using the same API
    (function () {
        const API_URL = '/app/chatbot_api.php';

        const msgArea = document.getElementById('chatbot-messages-page');
        const textarea = document.getElementById('chatbot-input-page');
        const sendBtn = document.getElementById('chatbot-send-page');
        const clearBtn = document.getElementById('chatbot-clear-page');
        const goalPills = document.querySelectorAll('.item-goal-page');
        const toolPills = document.querySelectorAll('.item-tool-page');
        const typingEl = document.getElementById('chatbot-typing-page');

        let isLoading = false;
        let selectedGoal = 'general';
        let selectedTool = null;

        // Auto-load history
        loadHistory();

        // Event Listeners
        sendBtn.addEventListener('click', sendMessage);
        textarea.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        textarea.addEventListener('input', () => {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        });

        // ── Independent Selection Logic ──────────────────────────────────────
        goalPills.forEach(pill => {
            pill.addEventListener('click', () => {
                selectedGoal = pill.dataset.goal;
                goalPills.forEach(p => p.classList.remove('active'));
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

        async function loadHistory() {
            try {
                const res = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_history' }),
                });
                const data = await res.json();

                // Update Status UI
                const statusInfo = document.querySelector('.chatbot-header-info span');
                if (data.service_online) {
                    statusInfo.innerHTML = '<span class="status-dot" style="background: #4ade80"></span> Online';
                } else {
                    statusInfo.innerHTML = '<span class="status-dot" style="background: #e74c3c; animation: none;"></span> Offline (Check .env)';
                }

                // Sync UI active states
                if (data.goal) {
                    selectedGoal = data.goal;
                    goalPills.forEach(p => p.classList.toggle('active', p.dataset.goal === data.goal));
                }
                if (data.tool) {
                    selectedTool = data.tool;
                    toolPills.forEach(p => p.classList.toggle('active', p.dataset.tool === data.tool));
                }

                if (data.history) {
                    data.history.forEach(msg => appendMessage(msg.role === 'user' ? 'user' : 'bot', msg.content, false));
                    scrollToBottom();
                }
            } catch (e) { console.error(e); }
        }

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
                if (data.error) showError(data.error, text);
                else appendMessage('bot', data.reply);
            } catch (e) { showError('Network error.', text); }
            finally { setLoading(false); }
        }

        function appendMessage(type, content, animate = true) {
            const wrapper = document.createElement('div');
            wrapper.className = `chat-msg ${type}`;
            const avatar = document.createElement('div');
            avatar.className = 'msg-avatar';
            avatar.textContent = type === 'bot' ? '🥗' : '👤';
            const bubble = document.createElement('div');
            bubble.className = 'msg-bubble';
            bubble.innerHTML = formatContent(content);
            const time = document.createElement('span');
            time.className = 'msg-time';
            time.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            bubble.appendChild(time);
            wrapper.appendChild(avatar);
            wrapper.appendChild(bubble);

            // Add interactive options if this is a bot message asking for duration
            if (type === 'bot' && (content.includes('3-day') || content.includes('7-day'))) {
                addOptions(bubble, ['3-day', '7-day']);
            }

            msgArea.insertBefore(wrapper, typingEl);
            scrollToBottom();
        }

        function addOptions(bubble, options) {
            const optionsContainer = document.createElement('div');
            optionsContainer.className = 'chat-options';

            options.forEach(opt => {
                const btn = document.createElement('button');
                btn.className = 'option-btn';
                btn.innerHTML = '📅 ' + opt;

                btn.addEventListener('click', () => {
                    sendMessage(opt, true);
                    btn.classList.add('selected');
                    const siblings = optionsContainer.querySelectorAll('.option-btn');
                    siblings.forEach(s => s.disabled = true);
                });

                optionsContainer.appendChild(btn);
            });

            bubble.appendChild(optionsContainer);
        }

        function formatContent(text) {
            return '<p>' + text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>') + '</p>';
        }

        function scrollToBottom() { msgArea.scrollTop = msgArea.scrollHeight; }

        function setLoading(state) {
            isLoading = state;
            sendBtn.disabled = state;
            textarea.disabled = state;
            typingEl.classList.toggle('show', state);
            if (state) scrollToBottom();
            else textarea.focus();
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

            if (!retryText) {
                setTimeout(() => el.remove(), 6000);
            }
        }
    })();
</script>

</main>
</body>

</html>