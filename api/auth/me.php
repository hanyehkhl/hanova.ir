<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../core/Database.php';
    require_once __DIR__ . '/../../core/Session.php';
    require_once __DIR__ . '/../../core/Auth.php';

    $auth = new Auth();
    $result = $auth->check();

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'internal error'
    ], JSON_UNESCAPED_UNICODE);
}
