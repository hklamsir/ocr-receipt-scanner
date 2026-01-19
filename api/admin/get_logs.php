<?php
// 取得系統日誌 API（Admin）
// 支援讀取 error.log 和 access.log

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/api_response.php';

// 參數
$logType = $_GET['type'] ?? 'error'; // error 或 access
$lines = min((int)($_GET['lines'] ?? 100), 500); // 最多 500 行
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));

// 日誌檔案路徑
$logDir = __DIR__ . '/../../tmp';
$logFile = $logType === 'access' ? 'access.log' : 'error.log';
$filePath = $logDir . '/' . $logFile;

try {
    // 檢查檔案是否存在
    if (!file_exists($filePath)) {
        ApiResponse::success([
            'logs' => [],
            'total' => 0,
            'message' => '日誌檔案不存在或尚未產生'
        ]);
    }

    // 讀取檔案內容
    $content = @file_get_contents($filePath);
    if ($content === false) {
        ApiResponse::success([
            'logs' => [],
            'total' => 0,
            'message' => '無法讀取日誌檔案（可能受主機限制）'
        ]);
    }

    // 分割為行
    $allLines = array_filter(explode("\n", $content), function($line) {
        return trim($line) !== '';
    });

    // 反轉順序（最新的在前）
    $allLines = array_reverse($allLines);

    // 搜尋篩選
    if (!empty($search)) {
        $allLines = array_filter($allLines, function($line) use ($search) {
            return stripos($line, $search) !== false;
        });
        $allLines = array_values($allLines); // 重新索引
    }

    $total = count($allLines);

    // 分頁
    $offset = ($page - 1) * $lines;
    $pagedLines = array_slice($allLines, $offset, $lines);

    // 解析日誌行
    $logs = array_map(function($line) {
        // 嘗試解析格式: "2026-01-15 12:34:56 - message"
        if (preg_match('/^(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\s*-\s*(.*)$/', $line, $matches)) {
            return [
                'timestamp' => $matches[1],
                'message' => $matches[2],
                'raw' => $line
            ];
        }
        return [
            'timestamp' => null,
            'message' => $line,
            'raw' => $line
        ];
    }, $pagedLines);

    // 取得檔案資訊
    $fileSize = filesize($filePath);
    $lastModified = date('Y-m-d H:i:s', filemtime($filePath));

    ApiResponse::success([
        'logs' => $logs,
        'total' => $total,
        'page' => $page,
        'pages' => ceil($total / $lines),
        'file_size' => $fileSize,
        'last_modified' => $lastModified,
        'log_type' => $logType
    ]);

} catch (Exception $e) {
    ApiResponse::error('讀取日誌失敗: ' . $e->getMessage(), 500);
}
