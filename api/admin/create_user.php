<?php
// 新增用戶 API（Admin）
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../includes/api_response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('僅支援 POST 請求', 405);
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$is_admin = isset($_POST['is_admin']) ? intval($_POST['is_admin']) : 0;
$quota_limit = isset($_POST['quota_limit']) ? intval($_POST['quota_limit']) : 0;

if (empty($username) || empty($password)) {
    ApiResponse::error('請填寫用戶名和密碼', 400);
}

if ($quota_limit < 0) {
    $quota_limit = 0;
}

try {
    $pdo = getDB();

    // 檢查用戶名是否已存在
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->execute([$username]);

    if ($checkStmt->fetch()) {
        ApiResponse::error('用戶名已存在', 400);
    }

    // 建立用戶
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, is_admin, quota_limit) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $password_hash, $is_admin, $quota_limit]);

    logInfo("Admin created user: $username");

    ApiResponse::success([], '用戶建立成功');

} catch (PDOException $e) {
    logError("Create user error: " . $e->getMessage());
    ApiResponse::error('建立失敗', 500);
}
