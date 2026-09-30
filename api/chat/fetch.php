<?php

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
$userId = intval($user['id']);
$partnerId = intval($_GET['partner_id'] ?? 0);
$lastId = intval($_GET['last_id'] ?? 0);

if ($partnerId === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'مخاطب مشخص نیست']);
    exit;
}

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT m.id, m.sender_id, m.receiver_id, m.message, m.file_path, m.file_type, m.is_read, m.created_at,
           u.display_name AS sender_name
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    WHERE m.id > :last_id
      AND ((m.sender_id = :uid1 AND m.receiver_id = :pid1)
        OR (m.sender_id = :pid2 AND m.receiver_id = :uid2))
    ORDER BY m.id ASC
    LIMIT 100
");

$stmt->execute([
    'last_id' => $lastId,
    'uid1'    => $userId,
    'pid1'    => $partnerId,
    'pid2'    => $partnerId,
    'uid2'    => $userId
]);

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$updateStmt = $db->prepare("
    UPDATE messages SET is_read = 1
    WHERE sender_id = :pid AND receiver_id = :uid AND is_read = 0
");
$updateStmt->execute([
    'pid' => $partnerId,
    'uid' => $userId
]);

http_response_code(200);
echo json_encode([
    'success'  => true,
    'messages' => $messages
]);
// پارامتر جدید: before_id برای لود پیام‌های قدیمی‌تر
$before_id = isset($_GET['before_id']) ? (int)$_GET['before_id'] : 0;
$last_id   = isset($_GET['last_id'])   ? (int)$_GET['last_id']   : 0;

if ($before_id > 0) {
    // لود پیام‌های قدیمی‌تر (backward pagination)
    $sql = "SELECT m.id, m.sender_id, m.receiver_id, m.message, m.file_path, m.file_type, m.is_read, m.created_at,
                   u.display_name AS sender_name
            FROM messages m
            JOIN users u ON u.id = m.sender_id
            WHERE m.id < :before_id
              AND ((m.sender_id = :uid1 AND m.receiver_id = :pid1)
                OR (m.sender_id = :pid2 AND m.receiver_id = :uid2))
            ORDER BY m.id DESC
            LIMIT 50";
    // نتایج را reverse کنید تا ترتیب زمانی حفظ شود
} else {
    // لود پیام‌های جدیدتر (همان منطق فعلی)
    $sql = "SELECT ... WHERE m.id > :last_id ... ORDER BY m.id ASC LIMIT 100";
}

