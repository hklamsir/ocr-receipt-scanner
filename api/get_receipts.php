<?php
// 查詢單據 API（支援搜尋、篩選和分頁）
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/api_response.php';

try {
    $pdo = getDB();
    $userId = $_SESSION['user_id'];

    // 如果請求年份列表
    if (isset($_GET['years']) && $_GET['years'] == '1') {
        $stmt = $pdo->prepare("
            SELECT DISTINCT YEAR(receipt_date) as year
            FROM receipts
            WHERE user_id = ? AND receipt_date IS NOT NULL
            ORDER BY year DESC
        ");
        $stmt->execute([$userId]);
        $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
        ApiResponse::success(['years' => $years]);
    }

    // 如果請求月份列表
    if (isset($_GET['months']) && $_GET['months'] == '1') {
        $stmt = $pdo->prepare("
            SELECT DISTINCT DATE_FORMAT(receipt_date, '%Y-%m') as month
            FROM receipts
            WHERE user_id = ? AND receipt_date IS NOT NULL
            ORDER BY month DESC
        ");
        $stmt->execute([$userId]);
        $months = $stmt->fetchAll(PDO::FETCH_COLUMN);
        ApiResponse::success(['months' => $months]);
    }

    // 取得篩選參數
    $search = trim($_GET['search'] ?? '');
    $date = $_GET['date'] ?? '';
    $month = $_GET['month'] ?? '';
    $year = $_GET['year'] ?? '';
    $tagId = (int) ($_GET['tag'] ?? 0);

    // 分頁參數
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $limit = min(100, max(10, (int) ($_GET['limit'] ?? 30)));
    $offset = ($page - 1) * $limit;

    // 建立基本 WHERE 條件
    $whereClause = "WHERE r.user_id = ?";
    $params = [$userId];

    // 關鍵字搜尋
    if (!empty($search)) {
        $whereClause .= " AND (r.company_name LIKE ? OR r.items_summary LIKE ? OR r.payment_method LIKE ?)";
        $searchPattern = '%' . $search . '%';
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
    }

    // 日期篩選
    if (!empty($date)) {
        $whereClause .= " AND r.receipt_date = ?";
        $params[] = $date;
    } elseif (!empty($month)) {
        // 月份格式: YYYY-MM
        $whereClause .= " AND DATE_FORMAT(r.receipt_date, '%Y-%m') = ?";
        $params[] = $month;
    } elseif (!empty($year)) {
        // 年份格式: YYYY
        $whereClause .= " AND YEAR(r.receipt_date) = ?";
        $params[] = $year;
    }

    // Tag 篩選
    if ($tagId > 0) {
        $whereClause .= " AND EXISTS (SELECT 1 FROM receipt_tags rt WHERE rt.receipt_id = r.id AND rt.tag_id = ?)";
        $params[] = $tagId;
    }

    // 先取得符合條件的總數
    $countSql = "SELECT COUNT(*) FROM receipts r $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = (int) $countStmt->fetchColumn();

    // 取得分頁資料
    $sql = "
        SELECT r.id, r.receipt_date, r.receipt_time, r.company_name, r.items_summary,
               r.summary, r.payment_method, r.total_amount, r.ocr_engine, r.image_filename, r.created_at
        FROM receipts r
        $whereClause
        ORDER BY r.created_at DESC
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $receipts = $stmt->fetchAll();

    // 為每筆單據附加 tags
    if (!empty($receipts)) {
        $receiptIds = array_column($receipts, 'id');
        $placeholders = implode(',', array_fill(0, count($receiptIds), '?'));

        $tagStmt = $pdo->prepare("
            SELECT rt.receipt_id, t.id, t.name, t.color
            FROM receipt_tags rt
            INNER JOIN tags t ON rt.tag_id = t.id
            WHERE rt.receipt_id IN ($placeholders)
            ORDER BY t.name ASC
        ");
        $tagStmt->execute($receiptIds);
        $allTags = $tagStmt->fetchAll();

        // 建立 receipt_id => tags 映射
        $tagMap = [];
        foreach ($allTags as $tag) {
            $rid = $tag['receipt_id'];
            if (!isset($tagMap[$rid])) {
                $tagMap[$rid] = [];
            }
            $tagMap[$rid][] = [
                'id' => (int) $tag['id'],
                'name' => $tag['name'],
                'color' => $tag['color']
            ];
        }

        // 附加 tags 到每筆單據
        foreach ($receipts as &$receipt) {
            $receipt['tags'] = $tagMap[$receipt['id']] ?? [];
        }
        unset($receipt);
    }

    // 計算是否還有更多資料
    $hasMore = ($offset + count($receipts)) < $totalCount;

    // 準備回應資料
    $responseData = [
        'success' => true,
        'receipts' => $receipts,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total_count' => $totalCount,
            'has_more' => $hasMore
        ]
    ];
    $jsonResponse = json_encode($responseData, JSON_UNESCAPED_UNICODE);

    // 基於篩選條件和頁碼生成 ETag
    $etagBase = $userId . '|' . $page . '|' . $limit . '|' . $search . '|' . $date . '|' . $month . '|' . $year . '|' . $tagId . '|' . $totalCount;
    $etag = '"' . md5($etagBase . '|' . md5($jsonResponse)) . '"';

    header('Content-Type: application/json; charset=utf-8');
    header("ETag: $etag");
    header('Cache-Control: private, max-age=30, must-revalidate');

    // 檢查客戶端緩存是否有效
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
        http_response_code(304);
        exit;
    }

    echo $jsonResponse;

} catch (PDOException $e) {
    ApiResponse::error('查詢失敗', 500);
}
