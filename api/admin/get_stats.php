<?php
// 取得系統統計 API（Admin）

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/api_response.php';

// 參數
$period = $_GET['period'] ?? 'today'; // today, week, month, all
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

try {
    $pdo = getDB();

    // 根據期間設定日期範圍
    $today = date('Y-m-d');
    switch ($period) {
        case 'today':
            $startDate = $today;
            $endDate = $today;
            break;
        case 'week':
            $startDate = date('Y-m-d', strtotime('-7 days'));
            $endDate = $today;
            break;
        case 'month':
            $startDate = date('Y-m-d', strtotime('-30 days'));
            $endDate = $today;
            break;
        case 'custom':
            // 使用傳入的日期
            if (!$startDate)
                $startDate = date('Y-m-d', strtotime('-30 days'));
            if (!$endDate)
                $endDate = $today;
            break;
        default:
            $startDate = null;
            $endDate = null;
    }

    // === 基本統計 ===
    // 1. 總用戶數
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'];

    // 2. 總單據數
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM receipts");
    $totalReceipts = $stmt->fetch()['total'];

    // 3. 今日新增單據
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM receipts WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $todayReceipts = $stmt->fetch()['total'];

    // 4. 本月新增單據
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM receipts WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
    $stmt->execute();
    $monthReceipts = $stmt->fetch()['total'];

    // === 每日統計趨勢（最近 30 天）===
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM receipts
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute();
    $dailyTrend = $stmt->fetchAll();

    // === 每使用者統計 ===
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.status, u.quota_limit,
               COUNT(r.id) as receipt_count,
               COALESCE(SUM(
                   CASE WHEN r.created_at >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN 1 ELSE 0 END
               ), 0) as month_count
        FROM users u
        LEFT JOIN receipts r ON u.id = r.user_id
        GROUP BY u.id
        ORDER BY receipt_count DESC
    ");
    $userStats = $stmt->fetchAll();

    // === 儲存空間統計 ===
    $storageStats = calculateStorageUsage();

    // === OCR 統計（從 system_stats 表或計算）===
    $ocrStats = getOcrStats($pdo, $startDate, $endDate);

    ApiResponse::success([
        'overview' => [
            'total_users' => (int) $totalUsers,
            'total_receipts' => (int) $totalReceipts,
            'today_receipts' => (int) $todayReceipts,
            'month_receipts' => (int) $monthReceipts
        ],
        'daily_trend' => $dailyTrend,
        'user_stats' => $userStats,
        'storage' => $storageStats,
        'ocr' => $ocrStats,
        'period' => $period,
        'date_range' => [
            'start' => $startDate,
            'end' => $endDate
        ]
    ]);

} catch (PDOException $e) {
    error_log('Stats API error: ' . $e->getMessage());
    ApiResponse::error('取得統計失敗', 500);
}

/**
 * 計算儲存空間使用量
 */
function calculateStorageUsage()
{
    $receiptsDir = __DIR__ . '/../../receipts';
    $totalSize = 0;
    $userSizes = [];

    if (is_dir($receiptsDir)) {
        $users = scandir($receiptsDir);
        foreach ($users as $user) {
            if ($user === '.' || $user === '..')
                continue;
            $userDir = $receiptsDir . '/' . $user;
            if (is_dir($userDir)) {
                $size = getDirectorySize($userDir);
                $userSizes[$user] = $size;
                $totalSize += $size;
            }
        }
    }

    // 排序（最大的在前）
    arsort($userSizes);

    return [
        'total_bytes' => $totalSize,
        'total_mb' => round($totalSize / 1024 / 1024, 2),
        'by_user' => array_map(function ($user, $size) {
            return [
                'username' => $user,
                'bytes' => $size,
                'mb' => round($size / 1024 / 1024, 2)
            ];
        }, array_keys($userSizes), array_values($userSizes))
    ];
}

/**
 * 取得目錄大小
 */
function getDirectorySize($dir)
{
    $size = 0;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($files as $file) {
        $size += $file->getSize();
    }
    return $size;
}

/**
 * 取得 OCR 統計
 */
function getOcrStats($pdo, $startDate, $endDate)
{
    // 嘗試從 system_stats 表取得
    try {
        $sql = "SELECT 
                    SUM(total_ocr_requests) as total_requests,
                    SUM(successful_ocr) as successful,
                    SUM(failed_ocr) as failed
                FROM system_stats";

        $params = [];
        if ($startDate && $endDate) {
            $sql .= " WHERE stat_date BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        $total = (int) ($result['total_requests'] ?? 0);
        $success = (int) ($result['successful'] ?? 0);
        $failed = (int) ($result['failed'] ?? 0);

        return [
            'total_requests' => $total,
            'successful' => $success,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($success / $total) * 100, 1) : 0
        ];
    } catch (Exception $e) {
        // 表可能不存在
        return [
            'total_requests' => 0,
            'successful' => 0,
            'failed' => 0,
            'success_rate' => 0,
            'note' => '統計資料尚未建立'
        ];
    }
}
