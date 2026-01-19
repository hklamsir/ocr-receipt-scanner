<?php
// 取得用戶活動日誌 API（Admin）

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/api_response.php';

// 參數
$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : null;
$action = $_GET['action'] ?? null;
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = min((int) ($_GET['limit'] ?? 50), 200);
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

try {
    $pdo = getDB();

    // 建立查詢
    $sql = "SELECT l.*, u.username 
            FROM user_activity_logs l
            LEFT JOIN users u ON l.user_id = u.id
            WHERE 1=1";
    $params = [];

    if ($userId) {
        $sql .= " AND l.user_id = ?";
        $params[] = $userId;
    }

    if ($action) {
        $sql .= " AND l.action = ?";
        $params[] = $action;
    }

    if ($startDate) {
        $sql .= " AND DATE(l.created_at) >= ?";
        $params[] = $startDate;
    }

    if ($endDate) {
        $sql .= " AND DATE(l.created_at) <= ?";
        $params[] = $endDate;
    }

    // 計算總數
    $countSql = str_replace("SELECT l.*, u.username", "SELECT COUNT(*) as total", $sql);
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];

    // 分頁查詢
    $sql .= " ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = ($page - 1) * $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    // 取得可用的動作類型
    $stmt = $pdo->query("SELECT DISTINCT action FROM user_activity_logs ORDER BY action");
    $actions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    ApiResponse::success([
        'logs' => $logs,
        'total' => (int) $total,
        'page' => $page,
        'pages' => ceil($total / $limit),
        'limit' => $limit,
        'available_actions' => $actions
    ]);

} catch (PDOException $e) {
    error_log('User activity error: ' . $e->getMessage());
    ApiResponse::error('取得活動日誌失敗', 500);
}
