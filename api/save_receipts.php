<?php
// 儲存單據 API
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf_check.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/api_response.php';
require_once __DIR__ . '/../includes/quota_helper.php';

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

// =============== 配額檢查 ===============
try {
    $pdo = getDB();

    $quotaStatus = getQuotaStatus($pdo, $userId);
    $checkResult = canAddReceipts($quotaStatus, count($receipts));

    if (!$checkResult['allowed']) {
        logInfo("User $username quota exceeded: " . $checkResult['error']);
        ApiResponse::error($checkResult['error'], 429);
    }
} catch (PDOException $e) {
    logError("Quota check error: " . $e->getMessage());
    // 配額檢查失敗不阻止儲存
}
// =============== 配額檢查結束 ===============

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

/**
 * 帶重試機制的圖片儲存
 * @param string $imagePath 圖片儲存路徑
 * @param string $imageBytes 圖片二進制資料
 * @param int $maxRetries 最大重試次數
 * @return bool 是否儲存成功
 */
function saveImageWithRetry($imagePath, $imageBytes, $maxRetries = 3)
{
    $delay = 100000; // 100ms (微秒)

    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        if (@file_put_contents($imagePath, $imageBytes)) {
            if ($attempt > 1) {
                logInfo("Image saved successfully on attempt $attempt: $imagePath");
            }
            return true;
        }

        if ($attempt < $maxRetries) {
            logInfo("Image save attempt $attempt failed, retrying in " . ($delay / 1000) . "ms...");
            usleep($delay);
            $delay *= 2; // 指數退避：100ms → 200ms → 400ms
        }
    }

    return false;
}

/**
 * 嘗試將圖片重新壓縮至指定 KB 上限內（需要 GD 擴充）
 * 當前端送來的圖片超過 MAX_IMAGE_SIZE_KB 時，伺服器端自救，避免靜默丟棄圖片。
 *
 * @param string $bytes 原始圖片二進位
 * @param int $maxKb 上限（KB）
 * @return string|null 成功回傳重新壓縮後的 JPEG 二進位；GD 不可用或仍無法壓到上限則回傳 null
 */
function recompressImageToLimit($bytes, $maxKb)
{
    if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
        return null; // GD 不可用，交回上層決定（略過圖片並警告）
    }

    $src = @imagecreatefromstring($bytes);
    if ($src === false) {
        return null;
    }

    // 先鋪白底，避免 PNG 透明區域轉 JPEG 時變黑
    $w = imagesx($src);
    $h = imagesy($src);
    $white = imagecreatetruecolor($w, $h);
    imagefill($white, 0, 0, imagecolorallocate($white, 255, 255, 255));
    imagecopy($white, $src, 0, 0, 0, 0, $w, $h);
    imagedestroy($src);
    $src = $white;

    $maxBytes = (int) ($maxKb * 1024);
    $quality = 85;
    $result = null;

    // 第一輪：原尺寸逐步降質
    do {
        ob_start();
        $ok = @imagejpeg($src, null, $quality);
        $out = ob_get_clean();
        if ($ok && is_string($out) && strlen($out) <= $maxBytes) {
            $result = $out;
            break;
        }
        $quality -= 5;
    } while ($quality >= 30);

    // 第二輪：仍過大則縮小至最大寬 1200px 再降質
    if ($result === null) {
        $maxW = 1200;
        if ($w > $maxW) {
            $nw = $maxW;
            $nh = (int) round($h * $maxW / $w);
            $dst = imagecreatetruecolor($nw, $nh);
            if ($dst !== false) {
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                $quality = 85;
                do {
                    ob_start();
                    $ok = @imagejpeg($dst, null, $quality);
                    $out = ob_get_clean();
                    if ($ok && is_string($out) && strlen($out) <= $maxBytes) {
                        $result = $out;
                        break;
                    }
                    $quality -= 5;
                } while ($quality >= 30);
                imagedestroy($dst);
            }
        }
    }

    imagedestroy($src);
    return $result;
}

try {
    $pdo = getDB();
    $pdo->beginTransaction();

    $saved = 0;
    $timestamp = time();
    $imageWarning = null; // 累積圖片相關警告，回傳前端明確提示

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
        $imageBytes = null;

        if (empty($imageData)) {
            logError("Receipt $index has no image data (empty string) - saving receipt without image");
        } else {
            // 移除 data URL 前綴
            $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
            $imageBytes = base64_decode($imageData);
            $imageSize = strlen($imageBytes);

            // 檢查大小（從系統設定取得，轉換 KB 為 bytes；設定異常時最低容許 50KB）
            $configuredMaxKb = (int) (defined('MAX_IMAGE_SIZE_KB') ? MAX_IMAGE_SIZE_KB : 200);
            if ($configuredMaxKb <= 0) {
                logError("MAX_IMAGE_SIZE_KB is invalid ($configuredMaxKb), falling back to 200KB");
                $configuredMaxKb = 200;
            }
            $maxImageBytes = $configuredMaxKb * 1024 + 500; // (留點餘裕)

            // 超過大小上限：嘗試伺服器端重新壓縮（GD 可用時）
            if ($imageSize > $maxImageBytes) {
                $recompressed = recompressImageToLimit($imageBytes, $configuredMaxKb);
                if ($recompressed !== null) {
                    $imageBytes = $recompressed;
                    $imageSize = strlen($imageBytes);
                    logInfo("Receipt $index image recompressed to fit $configuredMaxKb KB (now $imageSize bytes)");
                } else {
                    logError("Image too large for receipt $index: $imageSize bytes (limit $maxImageBytes) and cannot recompress - saving receipt without image");
                    $imageWarning = '部份單據因圖片過大而未能儲存圖片';
                    $imageBytes = null; // 標記不存圖，但繼續儲存單據資料
                }
            }

            // 驗證 MIME 類型（Magic Bytes）
            if ($imageBytes !== null && !isValidImageMime($imageBytes)) {
                logError("Invalid image MIME type for receipt $index (size $imageSize bytes) - saving receipt without image");
                $imageWarning = '部份單據因圖片格式無效而未能儲存圖片';
                $imageBytes = null; // 標記不存圖，但繼續儲存單據資料
            }

            // 只有當圖片有效時才儲存
            if ($imageBytes !== null) {
                // 生成檔名
                $imageFilename = $timestamp . '_' . ($index + 1) . '.jpg';
                $imagePath = $userDir . '/' . $imageFilename;

                if (!saveImageWithRetry($imagePath, $imageBytes)) {
                    logError("Failed to save image for receipt $index after 3 attempts: $imagePath (size $imageSize bytes) - saving receipt without image");
                    $imageFilename = null; // 儲存失敗，設為 null
                    $imageWarning = '部份單據圖片儲存失敗';
                }
            }
        }

        // 插入資料庫（即使沒有圖片也要儲存）
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
        'total' => count($receipts),
        'warning' => $imageWarning
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    logError("Save receipts error: " . $e->getMessage());
    ApiResponse::error('儲存失敗', 500);
}
