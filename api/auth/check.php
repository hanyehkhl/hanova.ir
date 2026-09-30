<?php
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

    $auth = new Auth();
    echo json_encode($auth->check(), JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'logged_in' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
