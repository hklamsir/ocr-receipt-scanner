<?php
// 清理孤立圖片 API（Admin）

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/api_response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Method not allowed', 405);
}

// 接收要刪除的圖片路徑列表
$input = json_decode(file_get_contents('php://input'), true);
$imagePaths = $input['paths'] ?? [];
$cleanDangling = $input['clean_dangling'] ?? false; // 是否清理懸空記錄

if (empty($imagePaths) && !$cleanDangling) {
    ApiResponse::error('未指定要清理的項目');
}

$receiptsDir = __DIR__ . '/../../receipts';
$deleted = [];
$failed = [];
$danglingCleaned = 0;

try {
    $pdo = getDB();

    // 刪除孤立圖片
    foreach ($imagePaths as $path) {
        // 安全檢查：確保路徑在 receipts 目錄內
        $fullPath = realpath($receiptsDir . '/' . $path);
        $realReceiptsDir = realpath($receiptsDir);

        if ($fullPath === false || strpos($fullPath, $realReceiptsDir) !== 0) {
            $failed[] = [
                'path' => $path,
                'reason' => '無效的路徑'
            ];
            continue;
        }

        if (!file_exists($fullPath)) {
            $failed[] = [
                'path' => $path,
                'reason' => '檔案不存在'
            ];
            continue;
        }

        // 再次確認不在資料庫中
        $parts = explode('/', $path);
        if (count($parts) === 2) {
            $username = $parts[0];
            $filename = $parts[1];

            $stmt = $pdo->prepare("
                SELECT r.id FROM receipts r 
                JOIN users u ON r.user_id = u.id 
                WHERE u.username = ? AND r.image_filename = ?
            ");
            $stmt->execute([$username, $filename]);

            if ($stmt->fetch()) {
                $failed[] = [
                    'path' => $path,
                    'reason' => '圖片仍在資料庫中'
                ];
                continue;
            }
        }

        // 嘗試刪除
        if (@unlink($fullPath)) {
            $deleted[] = $path;
        } else {
            $failed[] = [
                'path' => $path,
                'reason' => '刪除失敗'
            ];
        }
    }

    // 清理懸空記錄（資料庫中指向不存在檔案的記錄）
    if ($cleanDangling) {
        $stmt = $pdo->query("
            SELECT r.id, r.image_filename, u.username
            FROM receipts r 
            JOIN users u ON r.user_id = u.id
            WHERE r.image_filename IS NOT NULL AND r.image_filename != ''
        ");

        $idsToClean = [];
        while ($row = $stmt->fetch()) {
            $filePath = $receiptsDir . '/' . $row['username'] . '/' . $row['image_filename'];
            if (!file_exists($filePath)) {
                $idsToClean[] = $row['id'];
            }
        }

        if (!empty($idsToClean)) {
            // 清空圖片欄位（而非刪除整筆記錄）
            $placeholders = str_repeat('?,', count($idsToClean) - 1) . '?';
            $stmt = $pdo->prepare("UPDATE receipts SET image_filename = NULL WHERE id IN ($placeholders)");
            $stmt->execute($idsToClean);
            $danglingCleaned = count($idsToClean);
        }
    }

    // 記錄活動日誌
    $details = json_encode([
        'deleted_images' => count($deleted),
        'failed_images' => count($failed),
        'dangling_cleaned' => $danglingCleaned
    ], JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("
        INSERT INTO user_activity_logs (user_id, action, details, ip_address)
        VALUES (?, 'cleanup_orphans', ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $details,
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    ApiResponse::success([
        'deleted' => $deleted,
        'deleted_count' => count($deleted),
        'failed' => $failed,
        'failed_count' => count($failed),
        'dangling_cleaned' => $danglingCleaned
    ], '清理完成');

} catch (Exception $e) {
    error_log('Cleanup orphan images error: ' . $e->getMessage());
    ApiResponse::error('清理失敗: ' . $e->getMessage(), 500);
}
