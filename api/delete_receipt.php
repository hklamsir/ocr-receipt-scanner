<?php
// 刪除單據 API
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf_check.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/api_response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('僅支援 POST 請求', 405);
}

// 讀取 JSON 資料
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['id'])) {
    ApiResponse::error('無效的請求資料', 400);
}

$receiptId = (int) $data['id'];
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

try {
    $pdo = getDB();

    // 驗證該單據屬於當前用戶並取得圖片檔名
    $stmt = $pdo->prepare("SELECT id, image_filename FROM receipts WHERE id = ? AND user_id = ?");
    $stmt->execute([$receiptId, $userId]);
    $receipt = $stmt->fetch();

    if (!$receipt) {
        ApiResponse::error('無權刪除此單據', 403);
    }

    // 刪除資料庫記錄
    $stmt = $pdo->prepare("DELETE FROM receipts WHERE id = ?");
    $stmt->execute([$receiptId]);

    // 刪除圖片檔案（如存在）
    if ($receipt['image_filename']) {
        $imagePath = __DIR__ . '/../receipts/' . $username . '/' . $receipt['image_filename'];
        if (file_exists($imagePath)) {
            @unlink($imagePath);
        }
    }

    logInfo("User $username deleted receipt $receiptId");

    ApiResponse::success();

} catch (PDOException $e) {
    logError("Delete receipt error: " . $e->getMessage());
    ApiResponse::error('刪除失敗', 500);
}
