<?php
// 配額檢查 API
// 返回用戶的配額狀態，讓前端在 OCR 前檢查
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/api_response.php';
require_once __DIR__ . '/../includes/quota_helper.php';

try {
    $pdo = getDB();
    $userId = $_SESSION['user_id'];

    $quotaStatus = getQuotaStatus($pdo, $userId);

    ApiResponse::success($quotaStatus);

} catch (PDOException $e) {
    ApiResponse::error('配額查詢失敗', 500);
}
