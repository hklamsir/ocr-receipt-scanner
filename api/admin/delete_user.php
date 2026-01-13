<?php
// 刪除用戶 API（Admin）
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../includes/api_response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('僅支援 POST 請求', 405);
}

$user_id = intval($_POST['user_id'] ?? 0);

if ($user_id <= 0) {
    ApiResponse::error('無效的用戶 ID', 400);
}

// 防止删除自己
if ($user_id == $_SESSION['user_id']) {
    ApiResponse::error('不能刪除自己的帳號', 400);
}

try {
    $pdo = getDB();

    // 獲取用戶名（用於刪除圖片目錄）
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        ApiResponse::error('用戶不存在', 404);
    }

    // 刪除用戶（CASCADE 會自動刪除單據記錄）
    $deleteStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $deleteStmt->execute([$user_id]);

    //刪除圖片目錄
    $userDir = __DIR__ . '/../../receipts/' . $user['username'];
    if (is_dir($userDir)) {
        // 遞迴刪除目錄及檔案
        $files = glob($userDir . '/*');
        foreach ($files as $file) {
            if (is_file($file))
                @unlink($file);
        }
        @rmdir($userDir);
    }

    logInfo("Admin deleted user: " . $user['username']);

    ApiResponse::success([], '用戶已刪除');

} catch (PDOException $e) {
    logError("Delete user error: " . $e->getMessage());
    ApiResponse::error('刪除失敗', 500);
}
