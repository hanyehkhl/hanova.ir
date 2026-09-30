<?php

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد غیرمجاز']);
    exit;
}

require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Database.php';

$auth = Auth::getInstance();

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'ابتدا وارد شوید']);
    exit;
}

$user = $auth->getUser();
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');

if ($userMessage === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'پیام خالی است']);
    exit;
}

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT user_message, bot_response FROM chatbot_history
    WHERE user_id = :uid
    ORDER BY id DESC
    LIMIT 10
");
$stmt->execute(['uid' => $user['id']]);
$history = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

$messagesForApi = [];
$messagesForApi[] = [
    'role'    => 'system',
    'content' => 'تو یک دستیار هوشمند فارسی‌زبان هستی. پاسخ‌های کوتاه، دقیق و مفید بده.'
];

foreach ($history as $row) {
    $messagesForApi[] = ['role' => 'user', 'content' => $row['user_message']];
    $messagesForApi[] = ['role' => 'assistant', 'content' => $row['bot_response']];
}

$messagesForApi[] = ['role' => 'user', 'content' => $userMessage];

$apiKey = getenv('OPENAI_API_KEY');

if (!$apiKey) {
    $configPath = __DIR__ . '/../../config/api_keys.php';
    if (file_exists($configPath)) {
        $keys = require $configPath;
        $apiKey = $keys['openai_api_key'] ?? '';
    }
}

if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'کلید API تنظیم نشده است']);
    exit;
}

$postData = json_encode([
    'model'       => 'gpt-3.5-turbo',
    'messages'    => $messagesForApi,
    'max_tokens'  => 500,
    'temperature' => 0.7
]);

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postData,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_CONNECTTIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطا در اتصال به سرویس هوش مصنوعی']);
    exit;
}

$responseData = json_decode($response, true);

if ($httpCode !== 200 || !isset($responseData['choices'][0]['message']['content'])) {
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => 'پاسخی از سرویس هوش مصنوعی دریافت نشد']);
    exit;
}

$botResponse = trim($responseData['choices'][0]['message']['content']);

$stmt = $db->prepare("
    INSERT INTO chatbot_history (user_id, user_message, bot_response, created_at)
    VALUES (:uid, :user_msg, :bot_msg, NOW())
");
$stmt->execute([
    'uid'      => $user['id'],
    'user_msg' => $userMessage,
    'bot_msg'  => $botResponse
]);

http_response_code(200);
echo json_encode([
    'success'  => true,
    'response' => $botResponse
]);
