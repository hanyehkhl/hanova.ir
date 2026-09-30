<?php
require_once __DIR__.'/../../core/Session.php';
require_once __DIR__.'/../../core/Auth.php';
require_once __DIR__.'/../../core/Database.php';

$requestedFile = $_GET['file'] ?? '';
$decodedFilePath = urldecode($requestedFile);
// تبدیل کاراکترها برای سازگاری با سیستم فایل سرور LiteSpeed
$serverFilePath = mb_convert_encoding($decodedFilePath, 'UTF-8', 'auto');

$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    exit('Unauthorized');
}

$filePath = urldecode($_GET['file'] ?? '');
error_log("File request: " . $filePath);

if ($filePath === '' || $filePath === 'undefined') {
    http_response_code(400);
    exit('Invalid file');
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT file_path, file_type FROM messages WHERE file_path = :path LIMIT 1");
$stmt->execute(['path' => $filePath]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    error_log("File not found in DB: " . $filePath);
    http_response_code(404);
    exit('File not found');
}


$fullPath = __DIR__ . '/../../uploads/' . basename($file['file_path']);

if (!is_file($fullPath)) {
    http_response_code(404);
    exit('File not found');
}

//$mime = $file['file_type'] === 'image' ? mime_content_type($fullPath) : 'application/pdf';
$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
switch ($ext) {
    case 'mp3':
        $mime = 'audio/mpeg';
        break;
    case 'mp4':
        $mime = 'video/mp4';
        break;
    case 'pdf':
        $mime = 'application/pdf';
        break;
    case 'jpg':
    case 'jpeg':
        $mime = 'image/jpeg';
        break;
    case 'png':
        $mime = 'image/png';
        break;
    case 'mp3':
        $mime = 'audio/mp3';
        break;
    default:
        $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
}

header('Content-Type: '.$mime);
header('Content-Length: '.filesize($fullPath));

readfile($fullPath);
