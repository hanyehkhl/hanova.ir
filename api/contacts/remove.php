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

    $input = json_decode(file_get_contents('php://input'), true);
    $db = Database::getInstance()->getConnection();

    $db->prepare("DELETE FROM contacts WHERE user_id = ? AND contact_id = ?")->execute([$_SESSION['user_id'], (int)$input['contact_id']]);

    echo json_encode(['success' => true, 'message' => 'مخاطب حذف شد'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
