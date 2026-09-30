<?php
header('Content-Type: application/json; charset=utf-8');
try {
    require_once __DIR__ . '/../../core/Database.php';
    require_once __DIR__ . '/../../core/Session.php';

    Session::start();
    if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] != true) {
        echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $db = Database::getInstance()->getConnection();

    $check = $db->prepare("SELECT username FROM users WHERE id = ?");
    $check->execute([$input['id']]);
    $user = $check->fetch();
    if ($user && $user['username'] === 'admin') {
        echo json_encode(['success' => false, 'message' => 'امکان حذف مدیر اصلی وجود ندارد'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $db->prepare("DELETE FROM messages WHERE from_user = ? OR to_user = ?")->execute([$input['id'], $input['id']]);
    $db->prepare("DELETE FROM contacts WHERE user_id = ? OR contact_id = ?")->execute([$input['id'], $input['id']]);
    $db->prepare("DELETE FROM user_settings WHERE user_id = ?")->execute([$input['id']]);
    $db->prepare("DELETE FROM users WHERE id = ?")->execute([$input['id']]);

    echo json_encode(['success' => true, 'message' => 'کاربر حذف شد'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
