<?php
// 登入驗證 API
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/api_response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('僅支援 POST 請求', 405);
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

if (empty($username) || empty($password)) {
    ApiResponse::error('請輸入用戶名和密碼', 400);
}

try {
    $pdo = getDB();

    // === 安全檢查：IP 封鎖 ===
    $stmt = $pdo->prepare("
        SELECT id, reason, blocked_until 
        FROM ip_blocklist 
        WHERE ip_address = ? 
          AND (blocked_until IS NULL OR blocked_until > NOW())
    ");
    $stmt->execute([$ipAddress]);
    $blocked = $stmt->fetch();

    if ($blocked) {
        $reason = $blocked['reason'] ?: '可疑活動';
        $until = $blocked['blocked_until'] ? " 至 " . $blocked['blocked_until'] : '';
        logError("Login blocked: IP blocked - $ipAddress");
        ApiResponse::error("此 IP 已被封鎖{$until}（原因：{$reason}）", 403);
    }

    // === 安全檢查：登入頻率限制 ===
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as fail_count
        FROM login_attempts 
        WHERE ip_address = ? 
          AND success = 0 
          AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ");
    $stmt->execute([$ipAddress]);
    $failCount = $stmt->fetch()['fail_count'];

    // 取得設定的最大嘗試次數
    $maxAttempts = 5;
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'login_max_attempts'");
    $stmt->execute();
    $setting = $stmt->fetch();
    if ($setting) {
        $maxAttempts = (int) $setting['setting_value'];
    }

    if ($failCount >= $maxAttempts) {
        logError("Login rate limited: $ipAddress (attempts: $failCount)");
        ApiResponse::error('登入嘗試過多，請稍後再試', 429);
    }

    // 查詢用戶
    $stmt = $pdo->prepare("SELECT id, username, password_hash, is_admin, status FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // 記錄登入嘗試的輔助函數
    $recordAttempt = function ($success) use ($pdo, $username, $ipAddress, $userAgent) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO login_attempts (username, ip_address, user_agent, success) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$username, $ipAddress, substr($userAgent, 0, 255), $success ? 1 : 0]);
        } catch (Exception $e) {
            // 靜默失敗，不影響主流程
        }
    };

    if (!$user) {
        $recordAttempt(false);
        logError("Login failed: user not found - $username");
        ApiResponse::error('用戶名或密碼錯誤', 401);
    }

    // 驗證密碼
    if (!password_verify($password, $user['password_hash'])) {
        $recordAttempt(false);
        logError("Login failed: incorrect password - $username");
        ApiResponse::error('用戶名或密碼錯誤', 401);
    }

    // === 檢查帳號狀態 ===
    if (isset($user['status']) && $user['status'] === 'suspended') {
        $recordAttempt(false);
        logError("Login failed: account suspended - $username");
        ApiResponse::error('此帳號已被停用，請聯繫管理員', 403);
    }

    // 登入成功
    $recordAttempt(true);

    // 建立 Session
    session_start();
    session_regenerate_id(true); // 防止 Session 固定攻擊

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['is_admin'] = $user['is_admin'];
    $_SESSION['login_time'] = time();

    // 更新最後登入時間
    $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $updateStmt->execute([$user['id']]);

    // === 記錄活動 Session ===
    try {
        // 清除該用戶的舊 session 記錄（可選：限制同時登入數）
        // $pdo->prepare("DELETE FROM active_sessions WHERE user_id = ?")->execute([$user['id']]);

        $stmt = $pdo->prepare("
            INSERT INTO active_sessions (user_id, session_id, ip_address, user_agent)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent),
                last_activity = NOW()
        ");
        $stmt->execute([
            $user['id'],
            session_id(),
            $ipAddress,
            substr($userAgent, 0, 255)
        ]);
    } catch (Exception $e) {
        // 靜默失敗
    }

    // === 記錄用戶活動日誌 ===
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_activity_logs (user_id, action, details, ip_address)
            VALUES (?, 'login', ?, ?)
        ");
        $stmt->execute([
            $user['id'],
            json_encode(['user_agent' => substr($userAgent, 0, 100)], JSON_UNESCAPED_UNICODE),
            $ipAddress
        ]);
    } catch (Exception $e) {
        // 靜默失敗
    }

    logInfo("Login successful: $username");

    ApiResponse::success([
        'username' => $user['username'],
        'is_admin' => $user['is_admin']
    ]);

} catch (PDOException $e) {
    logError("Login error: " . $e->getMessage());
    ApiResponse::error('系統錯誤', 500);
}

