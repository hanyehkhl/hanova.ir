<?php
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        echo json_encode(['success' => false, 'message' => 'داده نامعتبر است'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $name = trim($input['employer_name'] ?? '');
    $company = trim($input['company_name'] ?? '');
    $contact = trim($input['employer_email'] ?? '');
    $message = trim($input['employer_message'] ?? '');

    if ($name === '' || $contact === '' || $message === '') {
        echo json_encode(['success' => false, 'message' => 'نام، راه ارتباطی و پیام الزامی است'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $storeDir = dirname(__DIR__, 2) . '/uploads/employer-messages/';
    if (!is_dir($storeDir) && !mkdir($storeDir, 0775, true) && !is_dir($storeDir)) {
        throw new RuntimeException('امکان ساخت پوشه پیام‌ها وجود ندارد');
    }

    $entry = [
        'employer_name' => $name,
        'company_name' => $company,
        'contact' => $contact,
        'message' => $message,
        'created_at' => date('c'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ];

    file_put_contents(
        $storeDir . 'messages.log',
        json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );

    echo json_encode(['success' => true, 'message' => 'پیام ثبت شد'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
