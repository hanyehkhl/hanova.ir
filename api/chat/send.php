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

    // --------------------------------------------------
    // تشخیص منبع داده: FormData یا JSON
    // --------------------------------------------------
    $hasFile = (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK);

    if (!empty($_POST['to_user'])) {
        $toUser  = (int)$_POST['to_user'];
        $message = trim($_POST['message'] ?? '');
    } else {
        $input   = json_decode(file_get_contents('php://input'), true);
        $toUser  = (int)($input['to_user'] ?? 0);
        $message = trim($input['message'] ?? '');
    }

    if ($toUser === 0) {
        echo json_encode(['success' => false, 'message' => 'مخاطب مشخص نیست'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // --------------------------------------------------
    // شاخه ارسال با فایل
    // --------------------------------------------------
    if ($hasFile) {
        $file = $_FILES['file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp','pdf','doc','docx','xls','xlsx','zip','rar','mp3','mp4','txt','svg'];

        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'فرمت فایل مجاز نیست'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($file['size'] > 100 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'حداکثر حجم فایل 100مگابایت'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $uploadDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $newName = uniqid('f_', true) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
            echo json_encode(['success' => false, 'message' => 'خطا در آپلود فایل'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $filePath = 'uploads/' . $newName;
        $fileType = in_array($ext, ['jpg','jpeg','png','gif','webp','svg']) ? 'image' : 'file';

        $stmt = $db->prepare(
            "INSERT INTO messages (from_user, to_user, message, file_path, file_name, file_type)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$myId, $toUser, $message, $filePath, $file['name'], $fileType]);

    // --------------------------------------------------
    // شاخه ارسال بدون فایل (فقط متن)
    // --------------------------------------------------
    } else {
        if ($message === '') {
            echo json_encode(['success' => false, 'message' => 'پیام خالی است'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $db->prepare("INSERT INTO messages (from_user, to_user, message) VALUES (?, ?, ?)");
        $stmt->execute([$myId, $toUser, $message]);
    }

    echo json_encode(['success' => true, 'message' => 'ارسال شد'], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
