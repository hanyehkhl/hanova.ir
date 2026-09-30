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
    $db = Database::getInstance()->getConnection();

    $check = $db->prepare("SELECT username FROM users WHERE id = ?");
    $check->execute([$input['id']]);
    $user = $check->fetch();
    if ($user && $user['username'] === 'admin') {
        echo json_encode(['success' => false, 'message' => 'امکان تغییر وضعیت مدیر اصلی وجود ندارد'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $stmt->execute([(int)$input['is_active'], (int)$input['id']]);
    echo json_encode(['success' => true, 'message' => 'وضعیت تغییر کرد'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
