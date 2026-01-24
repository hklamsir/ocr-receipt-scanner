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

// 設定香港時區
date_default_timezone_set('Asia/Hong_Kong');
$nowHK = date('Y-m-d H:i:s');

try {
    $pdo = getDB();

    // 只取得當前有效的公告（使用香港時間比較）
    $stmt = $pdo->prepare("
        SELECT id, title, content
        FROM announcements
        WHERE is_active = 1
          AND (start_date IS NULL OR start_date <= ?)
          AND (end_date IS NULL OR end_date >= ?)
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$nowHK, $nowHK]);

    $announcements = $stmt->fetchAll();
    ApiResponse::success(['announcements' => $announcements]);

} catch (PDOException $e) {
    error_log('Get announcements error: ' . $e->getMessage());
    ApiResponse::error('取得公告失敗', 500);
}
