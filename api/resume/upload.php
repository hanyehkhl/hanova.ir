<?php
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($fullName === '' || $email === '') {
        echo json_encode(['success' => false, 'message' => 'نام و ایمیل الزامی است'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'ایمیل معتبر نیست'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!isset($_FILES['resume_file']) || $_FILES['resume_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'فایل رزومه دریافت نشد'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $file = $_FILES['resume_file'];
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'حجم فایل بیشتر از 5 مگابایت است'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf', 'doc', 'docx'];
    if (!in_array($extension, $allowed, true)) {
        echo json_encode(['success' => false, 'message' => 'فرمت فایل مجاز نیست'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $uploadDir = dirname(__DIR__, 2) . '/uploads/resume/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('امکان ساخت پوشه رزومه وجود ندارد');
    }

    $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $fullName);
    $safeName = trim($safeName, '-');
    if ($safeName === '') {
        $safeName = 'resume';
    }

    $storedName = $safeName . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    $storedPath = $uploadDir . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $storedPath)) {
        throw new RuntimeException('ذخیره فایل انجام نشد');
    }

    $metaLine = json_encode([
        'full_name' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'message' => $message,
        'file' => 'uploads/resume/' . $storedName,
        'created_at' => date('c'),
    ], JSON_UNESCAPED_UNICODE);

    file_put_contents($uploadDir . 'submissions.log', $metaLine . PHP_EOL, FILE_APPEND | LOCK_EX);

    echo json_encode(['success' => true, 'message' => 'رزومه ثبت شد'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
