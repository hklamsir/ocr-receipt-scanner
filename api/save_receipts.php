<?php
// 儲存單據 API
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

if (!$data || !isset($data['receipts']) || !is_array($data['receipts'])) {
    ApiResponse::error('無效的請求資料', 400);
}

$receipts = $data['receipts'];
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

// 建立用戶圖片目錄
$userDir = __DIR__ . '/../receipts/' . $username;
if (!is_dir($userDir)) {
    if (!@mkdir($userDir, 0755, true)) {
        logError("Failed to create directory: $userDir");
        ApiResponse::error('無法建立儲存目錄', 500);
    }
}

// 驗證圖片 MIME 類型（檢查 Magic Bytes）
function isValidImageMime($bytes)
{
    if (strlen($bytes) < 12)
        return false;

    // JPEG: FF D8 FF
    if (substr($bytes, 0, 3) === "\xFF\xD8\xFF")
        return true;

    // PNG: 89 50 4E 47 0D 0A 1A 0A
    if (substr($bytes, 0, 8) === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A")
        return true;

    // WebP: RIFF....WEBP
    if (substr($bytes, 0, 4) === "RIFF" && substr($bytes, 8, 4) === "WEBP")
        return true;

    // GIF: GIF87a or GIF89a
    if (substr($bytes, 0, 6) === "GIF87a" || substr($bytes, 0, 6) === "GIF89a")
        return true;

    return false;
}

try {
    $pdo = getDB();
    $pdo->beginTransaction();

    $saved = 0;
    $timestamp = time();

    // 欄位長度限制
    $fieldLimits = [
        'company' => 50,
        'payment' => 12,
        'summary' => 15,
        'items' => 200
    ];

    foreach ($receipts as $index => $receipt) {
        // 欄位長度驗證
        foreach ($fieldLimits as $field => $limit) {
            if (isset($receipt[$field]) && mb_strlen($receipt[$field], 'UTF-8') > $limit) {
                logError("Field {$field} exceeds max length {$limit}: " . mb_strlen($receipt[$field], 'UTF-8'));
                $pdo->rollBack();
                ApiResponse::error("欄位 {$field} 超過最大長度 {$limit} 字", 400);
            }
        }

        // 儲存圖片到檔案系統
        $imageData = $receipt['image'] ?? '';
        $imageFilename = null;

        if ($imageData) {
            // 移除 data URL 前綴
            $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
            $imageBytes = base64_decode($imageData);

            // 檢查大小
            if (strlen($imageBytes) > 250000) { // 250KB（留點餘裕）
                logError("Image too large: " . strlen($imageBytes) . " bytes");
                continue; // 跳過太大的圖片
            }

            // 驗證 MIME 類型（Magic Bytes）
            if (!isValidImageMime($imageBytes)) {
                logError("Invalid image MIME type");
                continue; // 跳過非法圖片格式
            }

            // 生成檔名
            $imageFilename = $timestamp . '_' . ($index + 1) . '.jpg';
            $imagePath = $userDir . '/' . $imageFilename;

            if (!@file_put_contents($imagePath, $imageBytes)) {
                logError("Failed to save image: $imagePath");
                continue;
            }
        }

        // 插入資料庫
        $stmt = $pdo->prepare("
            INSERT INTO receipts (
                user_id, receipt_date, receipt_time, company_name,
                items_summary, payment_method, total_amount,
                summary, ocr_engine, image_filename
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $receipt['date'] ?: null,
            $receipt['time'] ?: null,
            $receipt['company'] ?: null,
            $receipt['items'] ?: null,
            $receipt['payment'] ?: null,
            $receipt['amount'] ?: null,
            $receipt['summary'] ?: null,
            $receipt['engine'] ?? null,
            $imageFilename
        ]);

        $receiptId = $pdo->lastInsertId();

        // 關聯 tags (如果有選擇)
        $tagIds = $receipt['tag_ids'] ?? [];
        if (!empty($tagIds) && is_array($tagIds)) {
            $tagStmt = $pdo->prepare("INSERT INTO receipt_tags (receipt_id, tag_id) VALUES (?, ?)");
            foreach ($tagIds as $tagId) {
                try {
                    $tagStmt->execute([$receiptId, $tagId]);
                } catch (PDOException $e) {
                    // 忽略重複或無效的 tag
                }
            }
        }

        $saved++;
    }

    $pdo->commit();

    logInfo("User $username saved $saved receipts");

    ApiResponse::success([
        'saved' => $saved,
        'total' => count($receipts)
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    logError("Save receipts error: " . $e->getMessage());
    ApiResponse::error('儲存失敗', 500);
}
