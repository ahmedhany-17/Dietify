<?php
require_once __DIR__ . '/config.php';

// ── Only logged-in users ─────────────────────────────────────────────────────
header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in to use the chat assistant.']);
    exit;
}

// Ensure script doesn't time out for long AI generations
set_time_limit(300);

// ── Read JSON body ───────────────────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'message';

// ── Handle actions ───────────────────────────────────────────────────────────


// ── Send message ─────────────────────────────────────────────────────────────
$userMessageRaw = $input['message'] ?? '';
if (is_array($userMessageRaw)) {
    // Log the anomaly: why is it an array? (Likely WAF or specific tool)
    error_log("Chatbot API Error: Received array for message. Data: " . print_r($userMessageRaw, true));
    // Fallback: convert to string or pick first element
    $userMessage = (string) ($userMessageRaw[0] ?? '');
} else {
    $userMessage = (string) $userMessageRaw;
}

if (trim($userMessage) === '' && $action === 'message') {
    http_response_code(400);
    echo json_encode(['error' => 'Message cannot be empty.']);
    exit;
}

// ── Database Table and Session Initialization ───────────────────────────────
$userId = $_SESSION['user_id'] ?? 0;
if (!isset($_SESSION['chat_goal'])) {
    $_SESSION['chat_goal'] = 'general';
}
if (!isset($_SESSION['chat_tool'])) {
    $_SESSION['chat_tool'] = null;
}
$goal = $_SESSION['chat_goal'];
$tool = $_SESSION['chat_tool'];

// ── Handle Clear Action ──────────────────────────────────────────────────────
if ($action === 'clear') {
    try {
        $stmt = $conn->prepare("DELETE FROM messages WHERE user_id = ?");
        if (!$stmt)
            throw new Exception($conn->error);
        $stmt->bind_param("s", $userId);
        if (!$stmt->execute())
            throw new Exception($stmt->error);
        echo json_encode(['status' => 'cleared']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ── Handle Set Goal/Tool Action ──────────────────────────────────────────────
if ($action === 'set_goal') {
    $allowedGoals = ['weight_loss', 'muscle_gain', 'general'];
    $allowedTools = ['recipe_creator', 'meal_planner'];

    if (isset($input['goal'])) {
        $_SESSION['chat_goal'] = in_array($input['goal'], $allowedGoals) ? $input['goal'] : 'general';
    }
    if (array_key_exists('tool', $input)) {
        $inTool = $input['tool'];
        $_SESSION['chat_tool'] = ($inTool === 'none' || !in_array($inTool, $allowedTools)) ? null : $inTool;
    }

    echo json_encode([
        'status' => 'state_updated',
        'goal' => $_SESSION['chat_goal'],
        'tool' => $_SESSION['chat_tool']
    ]);
    exit;
}

// ── Handle Get History Action ────────────────────────────────────────────────
if ($action === 'get_history') {
    try {
        $stmt = $conn->prepare("SELECT message AS content FROM messages WHERE user_id = ? ORDER BY created_at ASC LIMIT 50");
        if (!$stmt)
            throw new Exception($conn->error);
        $stmt->bind_param("s", $userId);
        if (!$stmt->execute())
            throw new Exception($stmt->error);
        $result = $stmt->get_result();
        $history = [];
        $i = 0;
        while ($row = $result->fetch_assoc()) {
            // Alternate roles by position: even index = user, odd = assistant
            $history[] = ['role' => ($i % 2 === 0 ? 'user' : 'assistant'), 'content' => $row['content']];
            $i++;
        }
        echo json_encode([
            'history' => $history,
            'goal' => $goal,
            'tool' => $tool,
            'service_online' => !empty($_ENV['GROQ_API_KEY'])
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'History lookup failed: ' . $e->getMessage()]);
    }
    exit;
}

// ── API key from .env ────────────────────────────────────────────────────────
$apiKey = $_ENV['GROQ_API_KEY'] ?? '';
if ($apiKey === '') {
    http_response_code(500);
    echo json_encode(['error' => 'Chat service is not configured.']);
    exit;
}

// ── Save User Message to Database ───────────────────────────────────────────
if ($action === 'message') {
    try {
        $stmt = $conn->prepare("INSERT INTO messages (user_id, message) VALUES (?, ?)");
        if (!$stmt)
            throw new Exception($conn->error);
        $stmt->bind_param("ss", $userId, $userMessage);
        if (!$stmt->execute())
            throw new Exception($stmt->error);
    } catch (Exception $e) {
        error_log("Chat Save (user) Error: " . $e->getMessage());
    }
}

// ── Build Context from Database (Last 5 messages) ──────────────────────────
$stmt = $conn->prepare("SELECT message AS content FROM messages WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("s", $userId);
$stmt->execute();
$result = $stmt->get_result();
$rawHistory = [];
while ($row = $result->fetch_assoc()) {
    $rawHistory[] = $row;
}
$rawHistory = array_reverse($rawHistory); // oldest first
// Assign alternating roles by position
$history = [];
foreach ($rawHistory as $idx => $row) {
    $history[] = ['role' => ($idx % 2 === 0 ? 'user' : 'assistant'), 'content' => $row['content']];
}

// ── System prompt ────────────────────────────────────────────────────────────
$goalContext = "";
switch ($goal) {
    case 'weight_loss':
        $goalContext = "The user wants to lose weight. Suggest low-calorie, high-fiber, protein-rich options. Avoid suggesting fried or high-sugar foods.";
        break;
    case 'muscle_gain':
        $goalContext = "The user wants to build muscle. Suggest high-protein options with adequate carbs for energy. Include chicken, eggs, beans, rice, and dairy.";
        break;
    default:
        $goalContext = "The user wants to eat healthier in general. Suggest balanced, nutritious options with variety.";
        break;
}

$toolContext = "";
switch ($tool) {
    case 'recipe_creator':
        $toolContext = "The user specifically wants a DETAILED RECIPE. Provide a full ingredient list with quantities and step-by-step cooking instructions. Format instructions with numbered steps. Use '### Ingredients' and '### Instructions' headers for a modern card layout.";
        break;
    case 'meal_planner':
        $toolContext = "The user specifically wants a STRUCTURED MEAL PLAN. Include breakfast, lunch, dinner, and a '### Nutrition Tip' section. IMPORTANT: If the user hasn't specified a duration, ask whether they want a 3-day or 7-day meal plan. If they HAVE specified (e.g., '7-day plan'), generate the full plan immediately.";
        break;
    default:
        $toolContext = "Engage in general nutrition conversation. Keep answers concise (2-4 lines) unless asked for something complex (like a meal plan or recipe).";
        break;
}

$systemPrompt = <<<PROMPT
You are a friendly Healthy Food Assistant. Your job is to help users choose healthy, affordable, and practical meals.
PROMPT;

$displayTool = $tool ?? "None";
$systemPrompt .= <<<PROMPT

USER STATUS:
- DIET GOAL: {$goal} ({$goalContext})
- ACTIVE TOOL: {$displayTool} ({$toolContext})

RULES:
1. Focus on common, affordable foods: rice, eggs, chicken, vegetables, beans, lentils, oats, fruits, bread, dairy.
2. If an ACTIVE TOOL is selected, prioritize that specific output format (Recipe or Plan).
3. Follow the brevity rule (concise answers) for general chat, but provide full length content for meal plans and recipes.
4. Use emojis sparingly to be friendly (🥗🍳🥚🍗🥦).
5. NEVER give medical advice. If asked, respond with the mandatory disclaimer: "Please consult a doctor for medical advice. I can only help with general food suggestions! 🩺"

Be helpful, warm, and combine the USER STATUS to give personalized advice.
PROMPT;

// ── Build messages array for API ─────────────────────────────────────────────
$messages = [['role' => 'system', 'content' => $systemPrompt]];
foreach ($history as $msg) {
    if ($msg['content'] !== $userMessage || $msg['role'] !== 'user') { // Avoid duplicating current message if already in history
        // Actually, since I just saved the user message, it might be in the history results already.
        // But typically it's safer to just build history first then add current.
        // In this logic, I saved then fetched. So it IS in history.
    }
    $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
}

// ── Call Groq API via cURL ───────────────────────────────────────────────────
$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'model' => 'llama-3.1-8b-instant',
        'messages' => $messages,
        'max_tokens' => 3000,
        'temperature' => 0.7,
    ]),
    CURLOPT_TIMEOUT => 222,
    //CURLOPT_TIMEOUT => 60: This is the limit for the entire process. It says: "From the moment I start until I get the full answer back, don't take more than 60 seconds total."
    CURLOPT_CONNECTTIMEOUT => 333,
    //CURLOPT_CONNECTTIMEOUT => 30: This tells the server to wait up to 30 seconds just to "knock on the door" of the AI service. If the AI doesn't answer the door in 30 seconds, it stops trying

    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // Use IPv4 for stability in XAMPP
    CURLOPT_SSL_VERIFYPEER => false,        // Temporary bypass becuse problem with https
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, // Force HTTP/1.1 for better stability in local environments
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Connectivity issue: ' . $curlError]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code(502);
    $apiErr = json_decode($response, true);
    $errMsg = $apiErr['error']['message'] ?? 'AI service returned error ' . $httpCode;
    echo json_encode(['error' => $errMsg]);
    exit;
}

$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(502);
    echo json_encode(['error' => 'Invalid response from AI service.']);
    exit;
}

$reply = $data['choices'][0]['message']['content'] ?? '';
if ($reply === '') {
    http_response_code(502);
    echo json_encode(['error' => 'The AI assistant returned an empty response.']);
    exit;
}

// ── Save Assistant Response to Database ─────────────────────────────────────
if ($reply !== '') {
    $stmt = $conn->prepare("INSERT INTO messages (user_id, message) VALUES (?, ?)");
    $stmt->bind_param("ss", $userId, $reply);
    $stmt->execute();
}

// ── Return reply ─────────────────────────────────────────────────────────────
echo json_encode(['reply' => $reply]);
