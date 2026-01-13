<?php
/**
 * csrf_check.php - CSRF 驗證輔助
 * 在需要 CSRF 保護的 API 中引用
 */
require_once __DIR__ . '/security.php';

// 只對寫入操作驗證 CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!Security::validateCSRFToken($token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'CSRF 驗證失敗']);
        exit;
    }
}
