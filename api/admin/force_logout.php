<?php
// 強制登出 API（Admin）

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/api_response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$sessionId = $input['session_id'] ?? '';
$userId = isset($input['user_id']) ? (int) $input['user_id'] : 0;
$logoutAll = isset($input['logout_all']) && $input['logout_all'] === true;

try {
    $pdo = getDB();
    $currentSessionId = session_id();
    $loggedOut = 0;

    if ($logoutAll && $userId) {
        // 登出指定用戶的所有 session（除了當前 session）
        $stmt = $pdo->prepare("
            DELETE FROM active_sessions 
            WHERE user_id = ? AND session_id != ?
        ");
        $stmt->execute([$userId, $currentSessionId]);
        $loggedOut = $stmt->rowCount();

        // 記錄
        $action = 'force_logout_all';
        $details = json_encode(['target_user_id' => $userId, 'count' => $loggedOut], JSON_UNESCAPED_UNICODE);

    } elseif ($sessionId) {
        // 登出特定 session
        // 不能登出自己
        if ($sessionId === $currentSessionId) {
            ApiResponse::error('無法強制登出當前使用中的 Session');
        }

        // 取得 session 資訊
        $stmt = $pdo->prepare("SELECT user_id FROM active_sessions WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();

        if (!$session) {
            ApiResponse::error('Session 不存在');
        }

        $stmt = $pdo->prepare("DELETE FROM active_sessions WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $loggedOut = $stmt->rowCount();

        $action = 'force_logout';
        $details = json_encode([
            'target_session' => substr($sessionId, 0, 8) . '...',
            'target_user_id' => $session['user_id']
        ], JSON_UNESCAPED_UNICODE);

    } else {
        ApiResponse::error('請指定 session_id 或 user_id');
    }

    // 記錄活動日誌
    $logStmt = $pdo->prepare("
        INSERT INTO user_activity_logs (user_id, action, details, ip_address)
        VALUES (?, ?, ?, ?)
    ");
    $logStmt->execute([
        $_SESSION['user_id'],
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    ApiResponse::success([
        'logged_out' => $loggedOut
    ], "已強制登出 {$loggedOut} 個 Session");

} catch (PDOException $e) {
    error_log('Force logout error: ' . $e->getMessage());
    ApiResponse::error('強制登出失敗', 500);
}
