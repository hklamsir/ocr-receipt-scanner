<?php
// 取得儲存空間資訊 API（Admin）

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/api_response.php';

try {
    $pdo = getDB();
    $receiptsDir = __DIR__ . '/../../receipts';

    $storageData = [];
    $totalSize = 0;
    $totalFiles = 0;

    // 取得所有使用者
    $stmt = $pdo->query("SELECT id, username FROM users ORDER BY username");
    $users = $stmt->fetchAll();

    foreach ($users as $user) {
        $userDir = $receiptsDir . '/' . $user['username'];
        $userSize = 0;
        $userFiles = 0;

        if (is_dir($userDir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($userDir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($files as $file) {
                $userSize += $file->getSize();
                $userFiles++;
            }
        }

        // 取得資料庫中的單據數
        $stmt2 = $pdo->prepare("SELECT COUNT(*) as count FROM receipts WHERE user_id = ?");
        $stmt2->execute([$user['id']]);
        $dbCount = $stmt2->fetch()['count'];

        $storageData[] = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'file_count' => $userFiles,
            'db_count' => (int) $dbCount,
            'size_bytes' => $userSize,
            'size_mb' => round($userSize / 1024 / 1024, 2),
            'orphan_count' => $userFiles - (int) $dbCount // 可能的孤立檔案數
        ];

        $totalSize += $userSize;
        $totalFiles += $userFiles;
    }

    // 排序（使用空間最大的在前）
    usort($storageData, function ($a, $b) {
        return $b['size_bytes'] - $a['size_bytes'];
    });

    ApiResponse::success([
        'total_size_bytes' => $totalSize,
        'total_size_mb' => round($totalSize / 1024 / 1024, 2),
        'total_files' => $totalFiles,
        'by_user' => $storageData
    ]);

} catch (Exception $e) {
    error_log('Storage info error: ' . $e->getMessage());
    ApiResponse::error('取得儲存資訊失敗', 500);
}
