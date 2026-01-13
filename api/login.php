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

if (empty($username) || empty($password)) {
    ApiResponse::error('請輸入用戶名和密碼', 400);
}

try {
    $pdo = getDB();

    // 查詢用戶
    $stmt = $pdo->prepare("SELECT id, username, password_hash, is_admin FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        logError("Login failed: user not found - $username");
        ApiResponse::error('用戶名或密碼錯誤', 401);
    }

    // 驗證密碼
    if (!password_verify($password, $user['password_hash'])) {
        logError("Login failed: incorrect password - $username");
        ApiResponse::error('用戶名或密碼錯誤', 401);
    }

    // 登入成功，建立 Session
    session_start();
    session_regenerate_id(true); // 防止 Session 固定攻擊

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['is_admin'] = $user['is_admin'];
    $_SESSION['login_time'] = time();

    // 更新最後登入時間
    $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $updateStmt->execute([$user['id']]);

    logInfo("Login successful: $username");

    ApiResponse::success([
        'username' => $user['username'],
        'is_admin' => $user['is_admin']
    ]);

} catch (PDOException $e) {
    logError("Login error: " . $e->getMessage());
    ApiResponse::error('系統錯誤', 500);
}
