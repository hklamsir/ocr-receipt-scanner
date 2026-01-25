<?php
// 管理員權限檢查模組
// 檢查用戶是否為管理員，非管理員返回 403

require_once __DIR__ . '/auth_check.php';

// 檢查是否為管理員
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    http_response_code(403);
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'error' => '無管理員權限']));
}
