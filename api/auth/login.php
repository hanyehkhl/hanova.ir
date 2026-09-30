<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
}
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../core/Database.php';
    require_once __DIR__ . '/../../core/Session.php';
    require_once __DIR__ . '/../../core/Auth.php';

    Session::start();

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || empty($input['username']) || empty($input['password'])) {
        echo json_encode(['success' => false, 'message' => 'نام کاربری و رمز عبور الزامی است'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $auth = new Auth();
    $result = $auth->login($input['username'], $input['password']);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'خطای سرور: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
