<?php
// 重設密碼 API（Admin）
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/csrf_check.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../includes/api_response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('僅支援 POST 請求', 405);
}

$user_id = intval($_POST['user_id'] ?? 0);
$new_password = $_POST['new_password'] ?? '';

if ($user_id <= 0 || empty($new_password)) {
    ApiResponse::error('請提供用戶 ID 和新密碼', 400);
}

try {
    $pdo = getDB();

    // 檢查用戶是否存在
    $checkStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $checkStmt->execute([$user_id]);
    $user = $checkStmt->fetch();

    if (!$user) {
        ApiResponse::error('用戶不存在', 404);
    }

    // 更新密碼
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$password_hash, $user_id]);

    logInfo("Admin reset password for user: " . $user['username']);

    ApiResponse::success([], '密碼已重設');

} catch (PDOException $e) {
    logError("Reset password error: " . $e->getMessage());
    ApiResponse::error('重設失敗', 500);
}
