<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Session.php';

Session::start();
if (empty($_SESSION['logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'وارد نشده‌اید'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$messageId = (int)($input['message_id'] ?? 0);

$userId = $_SESSION['user_id'];

if ($messageId === 0) {
    error_log("Delete failed - Input: " . print_r($input, true) . " | messageId: " . $messageId);
    echo json_encode(['success' => false, 'message' => 'شناسه پیام نامعتبر'], JSON_UNESCAPED_UNICODE);
    exit;
}


$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("DELETE FROM messages WHERE id = ? AND (from_user = ? OR to_user = ?)");
$stmt->execute([$messageId, $userId, $userId]);

echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
