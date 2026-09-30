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

    $db = Database::getInstance()->getConnection();

    // درخواست GET: دریافت لیست کاربران
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $db->query("SELECT id, username, display_name, is_admin, is_active, created_at FROM users ORDER BY id ASC");
        echo json_encode(['success' => true, 'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // درخواست POST: ایجاد یا حذف کاربر
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        // ---- ایجاد کاربر ----
        if ($action === 'create') {
            if (empty($input['username']) || empty($input['password'])) {
                echo json_encode(['success' => false, 'message' => 'نام کاربری و رمز عبور الزامی است'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $check = $db->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$input['username']]);
            if ($check->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'این نام کاربری قبلا ثبت شده'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt = $db->prepare("INSERT INTO users (username, password, display_name, is_active) VALUES (?, ?, ?, 1)");
            $stmt->execute([
                $input['username'],
                password_hash($input['password'], PASSWORD_BCRYPT),
                $input['display_name'] ?? ''
            ]);

            echo json_encode(['success' => true, 'message' => 'کاربر با موفقیت ایجاد شد'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // ---- حذف کاربر ----
        if ($action === 'delete') {
            if (empty($input['id'])) {
                echo json_encode(['success' => false, 'message' => 'شناسه کاربر الزامی است'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // جلوگیری از حذف ادمین
            $check = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
            $check->execute([$input['id']]);
            $user = $check->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'کاربر یافت نشد'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            if ($user['is_admin'] == 1) {
                echo json_encode(['success' => false, 'message' => 'امکان حذف کاربر ادمین وجود ندارد'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$input['id']]);

            echo json_encode(['success' => true, 'message' => 'کاربر با موفقیت حذف شد'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'عملیات نامعتبر'], JSON_UNESCAPED_UNICODE);
    }

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
