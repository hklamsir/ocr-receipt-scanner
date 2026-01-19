<?php
// 更新用戶配額 API（Admin）

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/api_response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Method not allowed', 405);
}

$userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
$quotaLimit = isset($_POST['quota_limit']) ? (int) $_POST['quota_limit'] : 0;

// 驗證
if (!$userId) {
    ApiResponse::error('缺少用戶 ID');
}

if ($quotaLimit < 0) {
    ApiResponse::error('配額不能為負數');
}

try {
    $pdo = getDB();

    // 檢查用戶是否存在
    $stmt = $pdo->prepare("SELECT id, username, quota_limit FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        ApiResponse::error('用戶不存在');
    }

    $oldQuota = $user['quota_limit'] ?? 0;

    // 更新配額
    $stmt = $pdo->prepare("UPDATE users SET quota_limit = ? WHERE id = ?");
    $stmt->execute([$quotaLimit, $userId]);

    // 記錄活動日誌
    $details = json_encode([
        'target_user_id' => $userId,
        'target_username' => $user['username'],
        'old_quota' => $oldQuota,
        'new_quota' => $quotaLimit
    ], JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("
        INSERT INTO user_activity_logs (user_id, action, details, ip_address)
        VALUES (?, 'quota_updated', ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $details,
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    $quotaText = $quotaLimit === 0 ? '無限制' : $quotaLimit . ' 次/月';
    ApiResponse::success([
        'user_id' => $userId,
        'quota_limit' => $quotaLimit
    ], "配額已更新為 {$quotaText}");

} catch (PDOException $e) {
    error_log('Update quota error: ' . $e->getMessage());
    ApiResponse::error('更新配額失敗', 500);
}
