<?php
// 裁剪單據圖片 API（覆寫該單據的圖片）
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf_check.php';
require_once __DIR__ . '/../includes/api_response.php';
require_once __DIR__ . '/../includes/logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('僅支援 POST 請求', 405);
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['receipt_id']) || !isset($data['image']) || empty($data['image'])) {
    ApiResponse::error('無效的請求資料', 400);
}

$receiptId = (int) $data['receipt_id'];
$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];

// 移除 data URL 前綴
$imageData = preg_replace('/^data:image\/\w+;base64,/', '', $data['image']);
$imageBytes = base64_decode($imageData, true);

if ($imageBytes === false || strlen($imageBytes) === 0) {
    ApiResponse::error('無效的圖片資料', 400);
}

// 驗證 MIME（Magic Bytes）
function isValidImageMime($bytes)
{
    if (strlen($bytes) < 12) return false;
    if (substr($bytes, 0, 3) === "\xFF\xD8\xFF") return true;            // JPEG
    if (substr($bytes, 0, 8) === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") return true; // PNG
    if (substr($bytes, 0, 4) === "RIFF" && substr($bytes, 8, 4) === "WEBP") return true; // WebP
    if (substr($bytes, 0, 6) === "GIF87a" || substr($bytes, 0, 6) === "GIF89a") return true; // GIF
    return false;
}

if (!isValidImageMime($imageBytes)) {
    ApiResponse::error('圖片格式不被支援', 400);
}

try {
    $pdo = getDB();

    // 確認單據存在且屬於目前使用者
    $stmt = $pdo->prepare("SELECT id, image_filename FROM receipts WHERE id = ? AND user_id = ?");
    $stmt->execute([$receiptId, $userId]);
    $receipt = $stmt->fetch();

    if (!$receipt) {
        ApiResponse::error('單據不存在或無權限', 404);
    }

    $userDir = __DIR__ . '/../receipts/' . $username;
    if (!is_dir($userDir)) {
        if (!@mkdir($userDir, 0755, true)) {
            logError("Failed to create directory: $userDir");
            ApiResponse::error('無法建立儲存目錄', 500);
        }
    }

    // 產生新檔名（避免與快取衝突，並保留舊檔以便刪除）
    $newFilename = time() . '_' . $receiptId . '_' . bin2hex(random_bytes(4)) . '.jpg';
    $newPath = $userDir . '/' . $newFilename;

    if (!@file_put_contents($newPath, $imageBytes)) {
        logError("Failed to save cropped image: $newPath");
        ApiResponse::error('圖片儲存失敗', 500);
    }

    // 更新資料庫 image_filename
    $oldFilename = $receipt['image_filename'];
    $updateStmt = $pdo->prepare("UPDATE receipts SET image_filename = ? WHERE id = ?");
    $updateStmt->execute([$newFilename, $receiptId]);

    // 刪除舊圖片（若不同且存在）
    if (!empty($oldFilename) && $oldFilename !== $newFilename) {
        $oldPath = $userDir . '/' . $oldFilename;
        if (file_exists($oldPath)) {
            @unlink($oldPath);
        }
    }

    logInfo("User $username cropped receipt $receiptId (new file: $newFilename)");
    ApiResponse::success(['image_filename' => $newFilename]);

} catch (PDOException $e) {
    logError("Crop receipt image error: " . $e->getMessage());
    ApiResponse::error('裁剪儲存失敗', 500);
}
