<?php
// 儲存使用者 UI 主題偏好
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => '不支援的請求方法']);
    exit;
}

// CSRF 校驗（前端經 X-CSRF-TOKEN 標頭傳送）
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!Security::validateCSRFToken($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF 驗證失敗']);
    exit;
}

$validThemes = ['teal', 'elegant', 'minimal', 'dark'];
$input = json_decode(file_get_contents('php://input'), true);
$theme = $input['theme'] ?? '';

if (!in_array($theme, $validThemes, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '無效的主題']);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare('UPDATE users SET theme = ? WHERE id = ?');
    $stmt->execute([$theme, $_SESSION['user_id']]);
    echo json_encode(['success' => true, 'theme' => $theme]);
} catch (Throwable $e) {
    error_log('更新主題失敗：' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '更新失敗，請稍後再試']);
}
