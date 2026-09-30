<?php
header('Content-Type: application/json; charset=utf-8');
try {
    require_once __DIR__ . '/../../core/Database.php';
    require_once __DIR__ . '/../../core/Session.php';

    Session::start();
    if (empty($_SESSION['logged_in'])) {
        echo json_encode(['success' => false, 'message' => 'وارد نشده‌اید'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $myId = $_SESSION['user_id'];
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("
        SELECT u.id, u.username, u.display_name,
        (SELECT message FROM messages
         WHERE (from_user = ? AND to_user = u.id) OR (from_user = u.id AND to_user = ?)
         ORDER BY id DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages
         WHERE (from_user = ? AND to_user = u.id) OR (from_user = u.id AND to_user = ?)
         ORDER BY id DESC LIMIT 1) as last_time
        FROM contacts c
        JOIN users u ON u.id = c.contact_id
        WHERE c.user_id = ? AND u.is_active = 1
        ORDER BY last_time DESC, u.display_name ASC
    ");
    $stmt->execute([$myId, $myId, $myId, $myId, $myId]);

    echo json_encode(['success' => true, 'contacts' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
