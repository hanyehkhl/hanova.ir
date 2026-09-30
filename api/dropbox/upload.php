<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: https://hanova.ir");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Only POST allowed"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_FILES['upload_file'])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "فایل دریافت نشد"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES['upload_file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "خطا در آپلود فایل",
        "code" => $file['error']
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$maxSize = 1024 * 1024 * 1024; // 1GB

if ($file['size'] > $maxSize) {
    http_response_code(413);
    echo json_encode([
        "success" => false,
        "message" => "حجم فایل بیشتر از ۱ گیگابایت است"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/dropbox/";

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "امکان ساخت پوشه آپلود وجود ندارد"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$originalName = basename($file['name']);
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

$blocked = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'htaccess'];

if (in_array($ext, $blocked, true)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "این نوع فایل مجاز نیست"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$safeName = bin2hex(random_bytes(16)) . ($ext ? "." . $ext : "");

$targetPath = $uploadDir . $safeName;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "ذخیره فایل انجام نشد"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$fileUrl = 'https://hanova.ir/uploads/dropbox/' . $safeName;

echo json_encode([
    'success' => true,
    'message' => 'فایل با موفقیت بارگذاری شد',
    'url' => $fileUrl
], JSON_UNESCAPED_UNICODE);