<?php
// 取得當前用戶個人資料 API
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/api_response.php';
require_once __DIR__ . '/../includes/quota_helper.php';

try {
    $pdo = getDB();
    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

    // 1. 基本用戶資訊
    $stmt = $pdo->prepare("SELECT created_at, last_login FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        ApiResponse::error('用戶不存在', 404);
        exit;
    }

    // 2. 配額資訊
    $quotaStatus = getQuotaStatus($pdo, $userId);

    // 3. 統計數據
    // 單據總數
    $stmtReceipts = $pdo->prepare("SELECT COUNT(*) FROM receipts WHERE user_id = ?");
    $stmtReceipts->execute([$userId]);
    $totalReceipts = $stmtReceipts->fetchColumn();

    // 標籤總數
    $stmtTags = $pdo->prepare("SELECT COUNT(*) FROM tags WHERE user_id = ?");
    $stmtTags->execute([$userId]);
    $totalTags = $stmtTags->fetchColumn();

    // 4. 儲存空間計算 (只計算該用戶的 receipts 目錄)
    $receiptsDir = __DIR__ . '/../receipts/' . $username;
    $storageBytes = 0;
    if (is_dir($receiptsDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($receiptsDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($files as $file) {
            $storageBytes += $file->getSize();
        }
    }

    // 格式化儲存大小
    $storageFormatted = '';
    if ($storageBytes < 1024 * 1024) {
        $storageFormatted = round($storageBytes / 1024, 2) . ' KB';
    } else {
        $storageFormatted = round($storageBytes / 1024 / 1024, 2) . ' MB';
    }

    // 5. 格式化時間
    $joinedDate = new DateTime($user['created_at']);
    $lastLoginDate = $user['last_login'] ? new DateTime($user['last_login']) : null;
    $now = new DateTime();

    $lastLoginStr = '從未登入';
    if ($lastLoginDate) {
        $interval = $lastLoginDate->diff($now);
        if ($interval->y > 0)
            $lastLoginStr = $interval->y . ' 年前';
        elseif ($interval->m > 0)
            $lastLoginStr = $interval->m . ' 個月前';
        elseif ($interval->d > 0)
            $lastLoginStr = $interval->d . ' 天前';
        elseif ($interval->h > 0)
            $lastLoginStr = $interval->h . ' 小時前';
        elseif ($interval->i > 0)
            $lastLoginStr = $interval->i . ' 分鐘前';
        else
            $lastLoginStr = '剛剛';
    }

    $data = [
        'username' => $username,
        'is_admin' => $isAdmin,
        'joined_at' => $joinedDate->format('Y-m-d'),
        'last_login_relative' => $lastLoginStr,
        'quota' => [
            'limit' => $quotaStatus['quota_limit'], // 0 = unlimited
            'used' => $quotaStatus['current_count'],
            'remaining' => $quotaStatus['remaining'],
            'percent' => ($quotaStatus['quota_limit'] > 0)
                ? min(100, round(($quotaStatus['current_count'] / $quotaStatus['quota_limit']) * 100))
                : 0
        ],
        'stats' => [
            'total_receipts' => $totalReceipts,
            'total_tags' => $totalTags,
            'storage_used' => $storageFormatted
        ]
    ];

    ApiResponse::success($data);

} catch (Exception $e) {
    ApiResponse::error('無法取得用戶資料: ' . $e->getMessage(), 500);
}
