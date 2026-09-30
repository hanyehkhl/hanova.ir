<?php
header('Content-Type: application/json; charset=utf-8');
try {
    require_once __DIR__ . '/../../core/Database.php';
    require_once __DIR__ . '/../../core/Session.php';

    Session::start();
    if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['id']) || empty($input['password'])) {
        echo json_encode(['success' => false, 'message' => 'شناسه کاربر و رمز عبور الزامی است'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([password_hash($input['password'], PASSWORD_BCRYPT), (int)$input['id']]);

    echo json_encode(['success' => true, 'message' => 'رمز عبور تغییر کرد'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
