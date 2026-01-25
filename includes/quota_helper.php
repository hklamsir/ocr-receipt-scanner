<?php
// 配額檢查 Helper
// 可被多個 API 使用

/**
 * 取得用戶配額狀態
 * @param PDO $pdo 資料庫連接
 * @param int $userId 用戶 ID
 * @return array ['quota_limit' => int, 'current_count' => int, 'remaining' => int, 'has_limit' => bool]
 */
function getQuotaStatus($pdo, $userId)
{
    // 取得用戶配額設定
    $quotaStmt = $pdo->prepare("SELECT quota_limit FROM users WHERE id = ?");
    $quotaStmt->execute([$userId]);
    $user = $quotaStmt->fetch();
    $quotaLimit = $user['quota_limit'] ?? 0;

    // 計算本月已儲存的單據數量
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM receipts 
        WHERE user_id = ? 
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
        AND MONTH(created_at) = MONTH(CURRENT_DATE())
    ");
    $countStmt->execute([$userId]);
    $result = $countStmt->fetch();
    $currentCount = $result['count'] ?? 0;

    // 計算剩餘配額
    $remaining = $quotaLimit > 0 ? max(0, $quotaLimit - $currentCount) : -1; // -1 表示無限制

    return [
        'quota_limit' => $quotaLimit,
        'current_count' => $currentCount,
        'remaining' => $remaining,
        'has_limit' => $quotaLimit > 0
    ];
}

/**
 * 檢查是否可以新增指定數量的單據
 * @param array $quotaStatus getQuotaStatus 的返回值
 * @param int $count 要新增的數量
 * @return array ['allowed' => bool, 'error' => string|null]
 */
function canAddReceipts($quotaStatus, $count)
{
    if (!$quotaStatus['has_limit']) {
        return ['allowed' => true, 'error' => null];
    }

    if ($quotaStatus['remaining'] <= 0) {
        return [
            'allowed' => false,
            'error' => "已達本月配額上限（{$quotaStatus['quota_limit']} 張）。本月已儲存 {$quotaStatus['current_count']} 張，無法繼續處理。"
        ];
    }

    if ($count > $quotaStatus['remaining']) {
        return [
            'allowed' => false,
            'error' => "配額不足！本月還可儲存 {$quotaStatus['remaining']} 張，但您嘗試儲存 {$count} 張。"
        ];
    }

    return ['allowed' => true, 'error' => null];
}
