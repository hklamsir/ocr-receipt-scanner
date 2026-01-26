<?php
// Excel 匯出 API - 支援自訂欄位與順序
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

try {
    $pdo = getDB();
    $userId = $_SESSION['user_id'];

    // 檢查是否有指定要匯出的 ID
    $ids = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids'])) {
        $ids = json_decode($_POST['ids'], true);
        if (!is_array($ids)) {
            $ids = null;
        }
    }

    // 解析自訂欄位設定
    $columns = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['columns'])) {
        $columns = json_decode($_POST['columns'], true);
        if (!is_array($columns)) {
            $columns = null;
        }
    }

    // 解析排序設定
    $sortBy = $_POST['sort_by'] ?? 'date';
    $sortOrder = strtoupper($_POST['sort_order'] ?? 'DESC');
    if (!in_array($sortOrder, ['ASC', 'DESC'])) {
        $sortOrder = 'DESC';
    }

    // 預設欄位對應
    $defaultColumns = [
        ['key' => 'date', 'label' => '日期'],
        ['key' => 'time', 'label' => '時間'],
        ['key' => 'company', 'label' => '公司名稱'],
        ['key' => 'items', 'label' => '購買物品摘要'],
        ['key' => 'summary', 'label' => '總結'],
        ['key' => 'payment', 'label' => '支付方式'],
        ['key' => 'amount', 'label' => '總金額']
    ];

    // 如果沒有指定欄位，使用預設值
    if (!$columns) {
        $columns = $defaultColumns;
    }

    // 欄位 key 到資料庫欄位的映射 (用於資料讀取，不含表別名前綴)
    $fieldMapping = [
        'date' => 'receipt_date',
        'time' => 'receipt_time',
        'company' => 'company_name',
        'items' => 'items_summary',
        'summary' => 'summary',
        'payment' => 'payment_method',
        'amount' => 'total_amount'
    ];

    // 建立 ORDER BY 子句
    $orderBy = "r.receipt_date DESC, r.receipt_time DESC"; // 預設
    if (isset($fieldMapping[$sortBy])) {
        // 在排序時才加上表別名前綴 r.
        $dbSortField = 'r.' . $fieldMapping[$sortBy];
        if ($sortBy === 'date') {
            $orderBy = "$dbSortField $sortOrder, r.receipt_time $sortOrder";
        } else {
            $orderBy = "$dbSortField $sortOrder";
        }
    }

    // 查詢該用戶的單據（包含標籤）
    if ($ids && count($ids) > 0) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("
            SELECT r.id, r.receipt_date, r.receipt_time, r.company_name, r.items_summary,
                   r.summary, r.payment_method, r.total_amount
            FROM receipts r
            WHERE r.user_id = ? AND r.id IN ($placeholders)
            ORDER BY $orderBy
        ");
        $stmt->execute(array_merge([$userId], $ids));
    } else {
        $stmt = $pdo->prepare("
            SELECT r.id, r.receipt_date, r.receipt_time, r.company_name, r.items_summary,
                   r.summary, r.payment_method, r.total_amount
            FROM receipts r
            WHERE r.user_id = ?
            ORDER BY $orderBy
        ");
        $stmt->execute([$userId]);
    }
    $receipts = $stmt->fetchAll();

    // 檢查是否需要標籤欄位
    $needTags = false;
    foreach ($columns as $col) {
        if ($col['key'] === 'tags') {
            $needTags = true;
            break;
        }
    }

    // 如果需要標籤，查詢所有單據的標籤
    $receiptTags = [];
    if ($needTags && count($receipts) > 0) {
        $receiptIds = array_column($receipts, 'id');
        $placeholders = implode(',', array_fill(0, count($receiptIds), '?'));
        $tagStmt = $pdo->prepare("
            SELECT rt.receipt_id, t.name
            FROM receipt_tags rt
            JOIN tags t ON rt.tag_id = t.id
            WHERE rt.receipt_id IN ($placeholders)
            ORDER BY t.name
        ");
        $tagStmt->execute($receiptIds);
        $tagRows = $tagStmt->fetchAll();

        foreach ($tagRows as $row) {
            if (!isset($receiptTags[$row['receipt_id']])) {
                $receiptTags[$row['receipt_id']] = [];
            }
            $receiptTags[$row['receipt_id']][] = $row['name'];
        }
    }

    // 設定 CSV 下載標頭（含 UTF-8 BOM 使 Excel 正確顯示中文）
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="receipts_' . date('Y-m-d') . '.csv"');

    // 輸出 UTF-8 BOM
    echo "\xEF\xBB\xBF";

    // 輸出標題列（根據自訂欄位順序）
    $headers = array_map(function ($col) {
        return $col['label'];
    }, $columns);
    echo implode(',', $headers) . "\n";

    // 輸出資料
    foreach ($receipts as $row) {
        $rowData = [];
        foreach ($columns as $col) {
            $key = $col['key'];

            // 處理空欄位
            if (strpos($key, 'empty_') === 0) {
                $rowData[] = '';
                continue;
            }

            // 處理標籤欄位
            if ($key === 'tags') {
                $tags = isset($receiptTags[$row['id']]) ? $receiptTags[$row['id']] : [];
                $rowData[] = '"' . str_replace('"', '""', implode(', ', $tags)) . '"';
                continue;
            }

            // 處理一般欄位
            $dbField = isset($fieldMapping[$key]) ? $fieldMapping[$key] : null;
            if ($dbField && isset($row[$dbField])) {
                $value = $row[$dbField];
                // 如果是文字欄位，需要加引號並處理特殊字元
                if (in_array($key, ['company', 'items', 'summary'])) {
                    $rowData[] = '"' . str_replace('"', '""', $value ?? '') . '"';
                } else {
                    $rowData[] = $value ?? '';
                }
            } else {
                $rowData[] = '';
            }
        }
        echo implode(',', $rowData) . "\n";
    }

} catch (PDOException $e) {
    http_response_code(500);
    die('匯出失敗');
}
