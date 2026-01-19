<?php
// Session 驗證模組
// 檢查用戶是否已登入，未登入則重定向至登入頁

session_start();

// 檢查 Session 是否存在
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// 更新最後活動時間
$_SESSION['last_activity'] = time();

// 驗證帳號狀態（每 5 分鐘檢查一次）
if (!isset($_SESSION['status_check']) || (time() - $_SESSION['status_check']) > 300) {
    try {
        require_once __DIR__ . '/db.php';
        $pdo = getDB();

        $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || (isset($user['status']) && $user['status'] === 'suspended')) {
            // 帳號已被停用或刪除
            session_destroy();
            header('Location: login.php?error=suspended');
            exit;
        }

        $_SESSION['status_check'] = time();

        // 更新 session 活動時間
        try {
            $stmt = $pdo->prepare("
                UPDATE active_sessions 
                SET last_activity = NOW() 
                WHERE session_id = ?
            ");
            $stmt->execute([session_id()]);
        } catch (Exception $e) {
            // 靜默失敗
        }
    } catch (Exception $e) {
        // 資料庫錯誤時不阻止使用
    }
}
