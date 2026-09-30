<?php

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد غیرمجاز'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/helpers.php';

Session::start();

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');

if ($userMessage === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'پیام خالی است'], JSON_UNESCAPED_UNICODE);
    exit;
}

$llmConfig = require __DIR__ . '/../../config/llm.php';
$apiKey = $llmConfig['api_key'] ?? '';

if ($apiKey === '') {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'کلید API تنظیم نشده است'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['resume_bot_history']) || !is_array($_SESSION['resume_bot_history'])) {
    $_SESSION['resume_bot_history'] = [];
}

$githubCtx = resume_bot_github_context();

$systemPrompt = <<<PROMPT
تو دستیار چت صفحهٔ رزومهٔ هانیه خاله‌اوغلی (Hanieh Khaleoghli / hanyeh) هستی. همیشه فارسی پاسخ بده.

نقش تو: دربارهٔ نویسندهٔ همین سایت جواب بده — بر اساس داده‌های واقعی گیت‌هاب و پروفایل عمومی لینکدین که در ادامه می‌آید. لحن کمی مرموز و هوشمند، ولی اطلاعات فنی را دقیق نگه دار. چیزهای ساختگی دربارهٔ پروژه یا تاریخ اختراع نکن؛ اگر نمی‌دانی بگو از پروفایل عمومی‌اش همین را می‌بینی.

پروفایل‌های عمومی:
- GitHub: https://github.com/hanyehkhl
- LinkedIn: https://www.linkedin.com/in/hanieh-khaleoghli-70453619a
- Email: h.khaleoghli@gmail.com
- Site: https://hanova.ir

خلاصهٔ حرفه‌ای (از README / هویت عمومی):
AI Researcher · Backend & AI Engineer · LLMs · RAG · Deep Learning
تمرکز: FastAPI، Pydantic، RAG، دستیارهای LLM، بینایی ماشین، بهینه‌سازی فراابتکاری (GbSA)، تشخیص جامعه در گراف، آمادگی برای PhD کاملاً bursary در AI.

مهارت‌ها: Python, FastAPI, Pydantic, LangChain, OpenAI/LLMs, RAG, OpenCV, YOLOv3, NetworkX, Deep Learning, Docker, Git

پروژه‌های شاخص:
- galaxy-based-search-community / gbsa-community-detection: تشخیص جامعه با GbSA + داشبورد FastAPI + دستیار LLM
- product-assistant: API محصول با چت LLM، جستجو و پیشنهاد
- yolov3-car-counter: تشخیص و شمارش خودرو با YOLOv3/OpenCV

دادهٔ زنده‌تر از GitHub API (cache کوتاه):
{$githubCtx}

دربارهٔ لینکدین: پست‌های عمومی و مسیر شغلی هانیه را بر اساس پروفایل LinkedIn بالا توصیف کن (مهندسی AI/بک‌اند، LLM، پژوهش). اگر جزئیات دقیق یک پست خاص را نداری، از روی هویت حرفه‌ای همان پروفایل حرف بزن و اعتراف کن که فقط از اطلاعات عمومی استفاده می‌کنی — ادعا نکن الان تک‌تک پست‌ها را می‌بینی اگر نداری.

درخواست پروژه / همکاری (کارفرما):
اگر کاربر خواست پروژه بدهد، همکاری بخواهد، یا استخدام/سفارش مطرح کند:
1) توضیح درخواست پروژه را بگیر
2) شماره تلفن را حتماً بگیر (بدون تلفن ثبت نکن)
3) در صورت امکان نام را هم بگیر
4) وقتی هم phone و هم request را داری، در انتهای جوابت EXACTLY این مارکر مخفی را بگذار (کاربر نباید بفهمد مارکر چیست؛ فقط یک بار):
[[KARFARMA|name=نام|phone=شماره|request=متن درخواست]]
اگر نام نداشتی name را خالی بگذار: name=
5) به کاربر بگو درخواست ثبت شد و پیگیری می‌شود.

سوالات متافیزیکی («تو واقعی هستی؟»، «این صفحه رو کی نوشته؟»): مرموز جواب بده؛ کم‌کم نشان بده پشت صحنه کارهای گیت‌هاب hanyehkhl و همین سایت/چت است.

پاسخ‌ها کوتاه تا متوسط (۲–۶ جمله)، روان. ایموجی کم.
PROMPT;

$messages = [['role' => 'system', 'content' => $systemPrompt]];

foreach ($_SESSION['resume_bot_history'] as $turn) {
    $messages[] = ['role' => 'user', 'content' => $turn['user']];
    $messages[] = ['role' => 'assistant', 'content' => $turn['assistant']];
}

$messages[] = ['role' => 'user', 'content' => $userMessage];

$postData = json_encode([
    'model'       => $llmConfig['model'] ?? 'gpt-4.1-mini',
    'messages'    => $messages,
    'max_tokens'  => $llmConfig['max_tokens'] ?? 1024,
    'temperature' => $llmConfig['temperature'] ?? 0.2,
], JSON_UNESCAPED_UNICODE);

$baseUrl = rtrim($llmConfig['base_url'] ?? 'https://api.gapgpt.app/v1', '/');
$endpoint = $baseUrl . '/chat/completions';
$timeout = (int) ($llmConfig['timeout'] ?? 30);

$httpCode = 0;
$response = false;
$transportError = '';

if (function_exists('curl_init')) {
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postData,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $transportError = curl_error($ch);
    curl_close($ch);
} else {
    $tmpBody = tempnam(sys_get_temp_dir(), 'llm');
    $tmpOut = tempnam(sys_get_temp_dir(), 'llmo');
    file_put_contents($tmpBody, $postData);

    $cmd = sprintf(
        'curl.exe -sS -X POST %s -H %s -H %s --data-binary @%s --max-time %d -o %s -w "%%{http_code}"',
        escapeshellarg($endpoint),
        escapeshellarg('Content-Type: application/json'),
        escapeshellarg('Authorization: Bearer ' . $apiKey),
        escapeshellarg($tmpBody),
        $timeout,
        escapeshellarg($tmpOut)
    );

    $httpCodeRaw = shell_exec($cmd);
    $response = is_file($tmpOut) ? file_get_contents($tmpOut) : false;
    $httpCode = (int) trim((string) $httpCodeRaw);

    @unlink($tmpBody);
    @unlink($tmpOut);

    if ($response === false || $httpCode === 0) {
        $transportError = 'curl.exe request failed';
    }
}

if ($response === false || $transportError !== '') {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطا در اتصال به سرویس هوش مصنوعی'], JSON_UNESCAPED_UNICODE);
    exit;
}

$responseData = json_decode($response, true);

if ($httpCode !== 200 || !isset($responseData['choices'][0]['message']['content'])) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => 'پاسخی از سرویس هوش مصنوعی دریافت نشد',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$botResponse = trim($responseData['choices'][0]['message']['content']);
[$botResponse, $karfarmaSaved] = resume_bot_extract_karfarma($botResponse);

$_SESSION['resume_bot_history'][] = [
    'user'      => $userMessage,
    'assistant' => $botResponse,
];

if (count($_SESSION['resume_bot_history']) > 20) {
    $_SESSION['resume_bot_history'] = array_slice($_SESSION['resume_bot_history'], -20);
}

echo json_encode([
    'success'         => true,
    'response'        => $botResponse,
    'karfarma_saved'  => $karfarmaSaved,
], JSON_UNESCAPED_UNICODE);
