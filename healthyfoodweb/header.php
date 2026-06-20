<?php require_once __DIR__ . '/init.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo SITE_NAME; ?>
    </title>
    <link rel="stylesheet" href="/app/assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php if (isLoggedIn()): ?>
        <link rel="stylesheet" href="/app/assets/css/chatbot.css">
    <?php endif; ?>
</head>

<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="/app/index1.php" class="logo">
                <?php echo SITE_NAME; ?>
            </a>
            <div class="nav-links">
                <a href="/app/index1.php">Home</a>
                <a href="/app/shop.php">Shop</a>
                <?php if (isLoggedIn()): ?>
                    <a href="/app/cart.php" class="cart-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count">
                            <?php echo getCartCount($conn); ?>
                        </span>
                    </a>
                    <a href="/app/profile.php">Profile</a>
                    <a href="/app/recommendations.php">Recommendations</a>

                    <a href="/app/chat.php">Chatbot</a>

                    <?php if (isAdmin()): ?>
                        <a href="/app/admin/AdminPanel.php" class="btn-admin">Admin</a>
                    <?php endif; ?>
                    <a href="/app/logout.php">Logout</a>
                <?php else: ?>
                    <a href="/app/login.php" class="btn-login">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <main class="main-content">

        <?php if (isLoggedIn()): ?>
            <!-- ── Chatbot Widget ─────────────────────────────────────────────── -->
            <button id="chatbot-bubble" class="chatbot-bubble" aria-label="Open chat assistant">
                <span class="bubble-icon-open"><i class="fas fa-comment-dots"></i></span>
                <span class="bubble-icon-close"><i class="fas fa-times"></i></span>
                <span id="chatbot-badge" class="chatbot-badge">1</span>
            </button>

            <div id="chatbot-window" class="chatbot-window">
                <!-- Header -->
                <div class="chatbot-header">
                    <div class="chatbot-avatar">🥗</div>
                    <div class="chatbot-header-info">
                        <h3>Food Assistant</h3>
                        <span><span class="status-dot"></span> Online</span>
                    </div>
                    <div class="chatbot-header-actions">
                        <button id="chatbot-clear" title="Clear chat"><i class="fas fa-trash-alt"></i></button>
                    </div>
                </div>

                <!-- Selector Bar -->
                <!-- Simplified Selector -->
                <div class="chatbot-selector-container" style="background: #fff9f6; border-bottom: 1px solid #f0d0be;">
                    <div class="chatbot-goal-bar" style="justify-content: center; padding: 10px 12px 2px; gap: 8px;">
                        <button class="goal-pill item-goal-widget" data-goal="general">🍽️ General</button>
                        <button class="goal-pill item-goal-widget" data-goal="weight_loss">⚖️ Weight Loss</button>
                        <button class="goal-pill item-goal-widget" data-goal="muscle_gain">💪 Muscle Gain</button>
                    </div>
                    <div class="chatbot-goal-bar" style="justify-content: center; padding: 2px 12px 10px; gap: 8px;">
                        <button class="goal-pill item-tool-widget" data-tool="recipe_creator">🍳 Recipes</button>
                        <button class="goal-pill item-tool-widget" data-tool="meal_planner">📅 Meal Planner</button>
                    </div>
                </div>

                <!-- Messages -->
                <div id="chatbot-messages" class="chatbot-messages">
                    <div class="chatbot-welcome">
                        <span class="welcome-emoji">👋</span>
                        <h4>Hi there!</h4>
                        <p>I'm your Healthy Food Assistant.<br>Ask me about meals, nutrition, or set a goal above!</p>
                    </div>

                    <!-- Typing indicator -->
                    <div id="chatbot-typing" class="typing-indicator">
                        <div class="msg-avatar">🥗</div>
                        <div class="typing-dots">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                </div>

                <!-- Input -->
                <div class="chatbot-input-area">
                    <textarea id="chatbot-input" placeholder="Ask about healthy meals..." rows="1"></textarea>
                    <button id="chatbot-send" class="chatbot-send-btn" aria-label="Send message">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
            <script src="/app/assets/js/chatbot.js"></script>
        <?php endif; ?>