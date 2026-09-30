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
    $q = trim($_GET['q'] ?? '');

    if (mb_strlen($q) < 1) {
        echo json_encode(['success' => true, 'users' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT id, username, display_name
        FROM users
        WHERE (username LIKE ? OR display_name LIKE ?)
        AND id != ?
        AND is_active = 1
        LIMIT 10
    ");
    $like = '%' . $q . '%';
    $stmt->execute([$like, $like, $myId]);

    echo json_encode(['success' => true, 'users' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
