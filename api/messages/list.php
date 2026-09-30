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
    $userId = (int)($_GET['user_id'] ?? 0);

    if ($userId === 0) {
        echo json_encode(['success' => false, 'message' => 'مخاطب مشخص نیست'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("
        SELECT m.id, m.from_user, m.to_user, m.message, m.file_path, m.file_name, m.file_type, m.created_at,
        u.display_name as sender_name
        FROM messages m
        JOIN users u ON u.id = m.from_user
        WHERE (m.from_user = ? AND m.to_user = ?) OR (m.from_user = ? AND m.to_user = ?)
        ORDER BY m.id ASC
    ");
    $stmt->execute([$myId, $userId, $userId, $myId]);

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($messages as &$msg) {
        $msg['from_me'] = ((int)$msg['from_user'] === $myId);
    }
    unset($msg);

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
