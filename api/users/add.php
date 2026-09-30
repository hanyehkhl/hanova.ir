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

    if (empty($input['username']) || empty($input['password']) || empty($input['display_name'])) {
        echo json_encode(['success' => false, 'message' => 'تمام فیلدها الزامی است'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $db = Database::getInstance()->getConnection();
    $check = $db->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$input['username']]);
    if ($check->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'این نام کاربری قبلا ثبت شده'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO users (username, password, display_name, is_active) VALUES (?, ?, ?, 1)");
    $stmt->execute([$input['username'], password_hash($input['password'], PASSWORD_BCRYPT), $input['display_name']]);

    echo json_encode(['success' => true, 'message' => 'کاربر با موفقیت اضافه شد'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
