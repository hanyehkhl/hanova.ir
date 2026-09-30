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

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $bg = $input['chat_bg'] ?? '#0f0f1a';

        $stmt = $db->prepare("INSERT INTO user_settings (user_id, chat_bg) VALUES (?, ?) ON DUPLICATE KEY UPDATE chat_bg = ?");
        $stmt->execute([$myId, $bg, $bg]);

        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    } else {
        $stmt = $db->prepare("SELECT chat_bg FROM user_settings WHERE user_id = ?");
        $stmt->execute([$myId]);
        $row = $stmt->fetch();
        echo json_encode(['success' => true, 'chat_bg' => $row ? $row['chat_bg'] : '#0f0f1a'], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
