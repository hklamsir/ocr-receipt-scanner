<?php
// 登入嘗試記錄 API（Admin）

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/api_response.php';

// 參數
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = min((int) ($_GET['limit'] ?? 50), 200);
$showFailed = isset($_GET['failed_only']) && $_GET['failed_only'] === '1';
$ip = $_GET['ip'] ?? null;
$username = $_GET['username'] ?? null;
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

try {
    $pdo = getDB();

    // 建立查詢
    $sql = "SELECT * FROM login_attempts WHERE 1=1";
    $params = [];

    if ($showFailed) {
        $sql .= " AND success = 0";
    }

    if ($ip) {
        $sql .= " AND ip_address = ?";
        $params[] = $ip;
    }

    if ($username) {
        $sql .= " AND username LIKE ?";
        $params[] = "%{$username}%";
    }

    if ($startDate) {
        $sql .= " AND DATE(created_at) >= ?";
        $params[] = $startDate;
    }

    if ($endDate) {
        $sql .= " AND DATE(created_at) <= ?";
        $params[] = $endDate;
    }

    // 計算總數
    $countSql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];

    // 分頁查詢
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = ($page - 1) * $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $attempts = $stmt->fetchAll();

    // 統計資訊
    $statsStmt = $pdo->query("
        SELECT 
            COUNT(*) as total_attempts,
            SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed_attempts,
            COUNT(DISTINCT ip_address) as unique_ips,
            COUNT(DISTINCT CASE WHEN success = 0 THEN ip_address END) as failed_ips
        FROM login_attempts
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stats = $statsStmt->fetch();

    // 最常失敗的 IP（過去 24 小時）
    $topFailedStmt = $pdo->query("
        SELECT ip_address, COUNT(*) as fail_count
        FROM login_attempts
        WHERE success = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY ip_address
        HAVING fail_count >= 3
        ORDER BY fail_count DESC
        LIMIT 10
    ");
    $topFailedIps = $topFailedStmt->fetchAll();

    ApiResponse::success([
        'attempts' => $attempts,
        'total' => (int) $total,
        'page' => $page,
        'pages' => ceil($total / $limit),
        'stats_24h' => [
            'total_attempts' => (int) $stats['total_attempts'],
            'failed_attempts' => (int) $stats['failed_attempts'],
            'unique_ips' => (int) $stats['unique_ips'],
            'suspicious_ips' => (int) $stats['failed_ips']
        ],
        'top_failed_ips' => $topFailedIps
    ]);

} catch (PDOException $e) {
    error_log('Login attempts error: ' . $e->getMessage());
    ApiResponse::error('取得登入記錄失敗', 500);
}
