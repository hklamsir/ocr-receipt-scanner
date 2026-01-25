<?php
// Session 驗證模組
// 檢查用戶是否已登入，未登入則重定向至登入頁

session_start();

// Helper function to check if request is API
function isApiRequest()
{
    return strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}

// Helper function for API error response
function apiErrorAndExit($message, $code = 401)
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    http_response_code($code);
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'error' => $message]));
}

// 檢查 Session 是否存在
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    if (isApiRequest()) {
        apiErrorAndExit('請先登入');
    }

    header('Location: login.php');
    exit;
}

// 更新最後活動時間
$_SESSION['last_activity'] = time();

// 驗證帳號狀態和 Session 有效性（每 1 分鐘檢查一次）
if (!isset($_SESSION['status_check']) || (time() - $_SESSION['status_check']) > 60) {
    try {
        require_once __DIR__ . '/db.php';
        $pdo = getDB();

        // 檢查帳號狀態
        $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || (isset($user['status']) && $user['status'] === 'suspended')) {
            // 帳號已被停用或刪除
            if (isApiRequest()) {
                apiErrorAndExit('帳號已被停用', 403);
            }
            session_destroy();
            header('Location: login.php?error=suspended');
            exit;
        }

        // 檢查 Session 是否仍然有效（是否被強制登出）
        try {
            $stmt = $pdo->prepare("SELECT id FROM active_sessions WHERE session_id = ?");
            $stmt->execute([session_id()]);
            $sessionRecord = $stmt->fetch();

            if (!$sessionRecord) {
                // Session 已被強制登出
                if (isApiRequest()) {
                    apiErrorAndExit('Session 已過期');
                }
                session_destroy();
                header('Location: login.php?error=session_expired');
                exit;
            }

            // 更新 session 活動時間
            $stmt = $pdo->prepare("
                UPDATE active_sessions 
                SET last_activity = NOW() 
                WHERE session_id = ?
            ");
            $stmt->execute([session_id()]);
        } catch (Exception $e) {
            // 靜默失敗（表可能不存在）
        }

        $_SESSION['status_check'] = time();

    } catch (Exception $e) {
        // 資料庫錯誤時不阻止使用
    }
}
