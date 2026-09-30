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

    $myId = $_SESSION['user_id'];
    $contactId = (int)$input['contact_id'];

    if ($myId === $contactId) {
        echo json_encode(['success' => false, 'message' => 'نمیتوانید خودتان را اضافه کنید'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $check = $db->prepare("SELECT id FROM contacts WHERE user_id = ? AND contact_id = ?");
    $check->execute([$myId, $contactId]);
    if ($check->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'این مخاطب قبلا اضافه شده'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $db->prepare("INSERT INTO contacts (user_id, contact_id) VALUES (?, ?)")->execute([$myId, $contactId]);
    $db->prepare("INSERT IGNORE INTO contacts (user_id, contact_id) VALUES (?, ?)")->execute([$contactId, $myId]);

    echo json_encode(['success' => true, 'message' => 'مخاطب اضافه شد'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
