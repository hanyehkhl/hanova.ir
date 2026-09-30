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

$sender = $auth->getUser();
$receiverId = intval($_POST['receiver_id'] ?? 0);

if ($receiverId === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'گیرنده مشخص نیست']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'فایلی ارسال نشد']);
    exit;
}

$file = $_FILES['file'];
$maxSize = 100 * 1024 * 1024;

if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'حجم فایل بیشتر از 100 مگابایت است']);
    exit;
}

$allowedTypes = [
    'image/jpeg' => 'image',
    'image/png'  => 'image',
    'image/gif'  => 'image',
    'image/webp' => 'image',
    'application/pdf' => 'pdf'
];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!isset($allowedTypes[$mimeType])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'فقط فایل های تصویری و PDF مجاز هستند']);
    exit;
}

$fileType = $allowedTypes[$mimeType];
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$newFileName = uniqid('chat_', true) . '.' . strtolower($extension);

$uploadDir = __DIR__ . '/../../uploads/chat/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$destination = $uploadDir . $newFileName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطا در ذخیره فایل']);
    exit;
}

$filePath = 'uploads/chat/' . $newFileName;

$messageText = trim($_POST['message'] ?? '');

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    INSERT INTO messages (from_user, to_user, message, file_path, file_type, created_at)
    VALUES (:from_user, :to_user, :message, :file_path, :file_type, NOW())
");

$stmt->execute([
    'from_user'   => $sender['id'],
    'to_user'     => $receiverId,
    'message'     => $messageText !== '' ? $messageText : null,
    'file_path'   => $filePath,
    'file_type'   => $fileType
]);

if ($stmt->rowCount() === 0) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطا در ثبت پیام در دیتابیس']);
    exit;
}

http_response_code(201);
echo json_encode([
    'success'    => true,
    'message'    => 'فایل ارسال شد',
    'message_id' => $db->lastInsertId(),
    'file_path'  => $filePath,
    'file_type'  => $fileType
]);
