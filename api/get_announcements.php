<?php
// 公告 API（給一般用戶）
// 只返回當前有效的公告，不需要管理員權限

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/api_response.php';

session_start();

// 需要登入
if (!isset($_SESSION['user_id'])) {
    ApiResponse::error('請先登入', 401);
}

try {
    $pdo = getDB();

    // 只取得當前有效的公告
    $stmt = $pdo->query("
        SELECT id, title, content
        FROM announcements
        WHERE is_active = 1
          AND (start_date IS NULL OR start_date <= NOW())
          AND (end_date IS NULL OR end_date >= NOW())
        ORDER BY created_at DESC
        LIMIT 5
    ");

    $announcements = $stmt->fetchAll();
    ApiResponse::success(['announcements' => $announcements]);

} catch (PDOException $e) {
    error_log('Get announcements error: ' . $e->getMessage());
    ApiResponse::error('取得公告失敗', 500);
}
