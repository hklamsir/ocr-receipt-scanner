<?php
// 更新用戶狀態 API（Admin）
// 停用/啟用用戶帳號

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/csrf_check.php';
require_once __DIR__ . '/../../includes/api_response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Method not allowed', 405);
}

$userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
$status = $_POST['status'] ?? '';

// 驗證
if (!$userId) {
    ApiResponse::error('缺少用戶 ID');
}

if (!in_array($status, ['active', 'suspended'])) {
    ApiResponse::error('無效的狀態值');
}

// 不能停用自己
if ($userId === (int) $_SESSION['user_id']) {
    ApiResponse::error('無法變更自己的帳號狀態');
}

try {
    $pdo = getDB();

    // 檢查用戶是否存在
    $stmt = $pdo->prepare("SELECT id, username, is_admin FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        ApiResponse::error('用戶不存在');
    }

    // 更新狀態
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$status, $userId]);

    // 記錄活動日誌
    $action = $status === 'active' ? 'user_activated' : 'user_suspended';
    $details = json_encode([
        'target_user_id' => $userId,
        'target_username' => $user['username'],
        'new_status' => $status
    ], JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("
        INSERT INTO user_activity_logs (user_id, action, details, ip_address)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    // 如果停用，清除該用戶的所有 session
    if ($status === 'suspended') {
        $stmt = $pdo->prepare("DELETE FROM active_sessions WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    $statusText = $status === 'active' ? '啟用' : '停用';
    ApiResponse::success([
        'user_id' => $userId,
        'status' => $status
    ], "用戶已{$statusText}");

} catch (PDOException $e) {
    error_log('Update user status error: ' . $e->getMessage());
    ApiResponse::error('更新狀態失敗', 500);
}
