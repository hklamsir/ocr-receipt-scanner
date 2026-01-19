<?php
// 活動 Session 管理 API（Admin）

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/api_response.php';

try {
    $pdo = getDB();

    // 取得所有活動 session
    $stmt = $pdo->query("
        SELECT s.*, u.username
        FROM active_sessions s
        JOIN users u ON s.user_id = u.id
        ORDER BY s.last_activity DESC
    ");
    $sessions = $stmt->fetchAll();

    // 標記當前 session
    $currentSessionId = session_id();
    foreach ($sessions as &$session) {
        $session['is_current'] = ($session['session_id'] === $currentSessionId);
        // 隱藏部分 session ID
        $session['session_id_masked'] = substr($session['session_id'], 0, 8) . '...' . substr($session['session_id'], -4);
    }

    // 按用戶分組統計
    $userSessions = [];
    foreach ($sessions as $s) {
        $userId = $s['user_id'];
        if (!isset($userSessions[$userId])) {
            $userSessions[$userId] = [
                'username' => $s['username'],
                'count' => 0,
                'sessions' => []
            ];
        }
        $userSessions[$userId]['count']++;
        $userSessions[$userId]['sessions'][] = $s;
    }

    ApiResponse::success([
        'sessions' => $sessions,
        'total' => count($sessions),
        'by_user' => array_values($userSessions),
        'current_session_id' => substr($currentSessionId, 0, 8) . '...'
    ]);

} catch (PDOException $e) {
    error_log('Active sessions error: ' . $e->getMessage());
    ApiResponse::error('取得 Session 清單失敗', 500);
}
