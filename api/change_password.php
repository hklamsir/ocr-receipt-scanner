<?php
// 變更密碼 API
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf_check.php';
require_once __DIR__ . '/../includes/api_response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('不支援的請求方法', 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$currentPassword = $data['current_password'] ?? '';
$newPassword = $data['new_password'] ?? '';

if (empty($currentPassword) || empty($newPassword)) {
    ApiResponse::error('請填寫所有欄位', 400);
}

if (strlen($newPassword) < 6) {
    ApiResponse::error('新密碼至少需要 6 個字元', 400);
}

try {
    $pdo = getDB();
    $userId = $_SESSION['user_id'];

    // 取得目前密碼 hash
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
        ApiResponse::error('目前密碼不正確', 400);
    }

    // 更新密碼
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$newHash, $userId]);

    ApiResponse::success();

} catch (PDOException $e) {
    ApiResponse::error('變更密碼失敗', 500);
}
