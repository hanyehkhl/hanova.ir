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
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$db = Database::getInstance()->getConnection();

$countStmt = $db->prepare("SELECT COUNT(*) FROM chatbot_history WHERE user_id = :uid");
$countStmt->execute(['uid' => $user['id']]);
$totalMessages = intval($countStmt->fetchColumn());
$totalPages = ceil($totalMessages / $limit);

$stmt = $db->prepare("
    SELECT id, user_message, bot_response, created_at
    FROM chatbot_history
    WHERE user_id = :uid
    ORDER BY id ASC
    LIMIT :lim OFFSET :off
");
$stmt->bindValue('uid', $user['id'], PDO::PARAM_INT);
$stmt->bindValue('lim', $limit, PDO::PARAM_INT);
$stmt->bindValue('off', $offset, PDO::PARAM_INT);
$stmt->execute();

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

http_response_code(200);
echo json_encode([
    'success'       => true,
    'messages'      => $messages,
    'current_page'  => $page,
    'total_pages'   => $totalPages,
    'total_messages' => $totalMessages
]);
