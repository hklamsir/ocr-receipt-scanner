<?php
// 查詢用戶列表 API（Admin）
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/api_response.php';

try {
    $pdo = getDB();

    // 查詢所有用戶及其單據數量
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.is_admin, u.created_at, u.last_login,
               COUNT(r.id) as receipt_count
        FROM users u
        LEFT JOIN receipts r ON u.id = r.user_id
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll();

    ApiResponse::success(['users' => $users]);

} catch (PDOException $e) {
    ApiResponse::error('查詢失敗', 500);
}
