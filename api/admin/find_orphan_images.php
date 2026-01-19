<?php
// 尋找孤立圖片 API（Admin）
// 找出存在於檔案系統但不在資料庫中的圖片

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/api_response.php';

try {
    $pdo = getDB();
    $receiptsDir = __DIR__ . '/../../receipts';

    $orphanImages = [];
    $totalOrphanSize = 0;

    // 取得所有使用者
    $stmt = $pdo->query("SELECT id, username FROM users");
    $users = $stmt->fetchAll();
    $userMap = [];
    foreach ($users as $user) {
        $userMap[$user['username']] = $user['id'];
    }

    // 取得資料庫中所有圖片檔名（以使用者分組）
    $stmt = $pdo->query("
        SELECT u.username, r.image_filename 
        FROM receipts r 
        JOIN users u ON r.user_id = u.id
        WHERE r.image_filename IS NOT NULL AND r.image_filename != ''
    ");
    $dbImages = [];
    while ($row = $stmt->fetch()) {
        $key = $row['username'] . '/' . $row['image_filename'];
        $dbImages[$key] = true;
    }

    // 掃描檔案系統
    if (is_dir($receiptsDir)) {
        $userDirs = scandir($receiptsDir);
        foreach ($userDirs as $username) {
            if ($username === '.' || $username === '..')
                continue;

            $userDir = $receiptsDir . '/' . $username;
            if (!is_dir($userDir))
                continue;

            $files = scandir($userDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..')
                    continue;

                $key = $username . '/' . $file;
                $filePath = $userDir . '/' . $file;

                // 檢查是否在資料庫中
                if (!isset($dbImages[$key])) {
                    $fileSize = filesize($filePath);
                    $fileTime = filemtime($filePath);

                    $orphanImages[] = [
                        'username' => $username,
                        'filename' => $file,
                        'path' => $key,
                        'size_bytes' => $fileSize,
                        'size_kb' => round($fileSize / 1024, 2),
                        'created_at' => date('Y-m-d H:i:s', $fileTime)
                    ];

                    $totalOrphanSize += $fileSize;
                }
            }
        }
    }

    // 也檢查資料庫中指向不存在檔案的記錄
    $danglingRecords = [];
    $stmt = $pdo->query("
        SELECT r.id, r.image_filename, u.username, r.created_at
        FROM receipts r 
        JOIN users u ON r.user_id = u.id
        WHERE r.image_filename IS NOT NULL AND r.image_filename != ''
    ");
    while ($row = $stmt->fetch()) {
        $filePath = $receiptsDir . '/' . $row['username'] . '/' . $row['image_filename'];
        if (!file_exists($filePath)) {
            $danglingRecords[] = [
                'receipt_id' => $row['id'],
                'username' => $row['username'],
                'filename' => $row['image_filename'],
                'created_at' => $row['created_at']
            ];
        }
    }

    ApiResponse::success([
        'orphan_images' => $orphanImages,
        'orphan_count' => count($orphanImages),
        'orphan_size_bytes' => $totalOrphanSize,
        'orphan_size_mb' => round($totalOrphanSize / 1024 / 1024, 2),
        'dangling_records' => $danglingRecords,
        'dangling_count' => count($danglingRecords)
    ]);

} catch (Exception $e) {
    error_log('Find orphan images error: ' . $e->getMessage());
    ApiResponse::error('掃描孤立圖片失敗', 500);
}
