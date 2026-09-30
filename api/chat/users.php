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

$currentUser = $auth->getUser();
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT u.id, u.username, u.display_name,
        (SELECT COUNT(*) FROM messages m
         WHERE m.sender_id = u.id AND m.receiver_id = :uid AND m.is_read = 0
        ) AS unread_count
    FROM users u
    WHERE u.is_active = 1 AND u.id != :current_id
    ORDER BY u.display_name ASC
");

$stmt->execute([
    'uid'        => $currentUser['id'],
    'current_id' => $currentUser['id']
]);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

http_response_code(200);
echo json_encode([
    'success' => true,
    'users'   => $users
]);
