<?php
// 資料庫備份 API（Admin）
// 產生 SQL 檔案供下載
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/logger.php';

// 設定記憶體限制（大型資料庫可能需要更多）
ini_set('memory_limit', '256M');
set_time_limit(300);

try {
    $pdo = getDB();

    // 取得所有表格名稱
    $tables = [];
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    // 開始產生 SQL
    $sql = "";
    $sql .= "-- ==========================================\n";
    $sql .= "-- OCR Receipt Scanner Database Backup\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- ==========================================\n\n";
    $sql .= "SET NAMES utf8mb4;\n";
    $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

    foreach ($tables as $table) {
        // 取得表格建立語句
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(PDO::FETCH_NUM);

        $sql .= "-- ------------------------------------------\n";
        $sql .= "-- Table: $table\n";
        $sql .= "-- ------------------------------------------\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $row[1] . ";\n\n";

        // 取得表格資料
        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) > 0) {
            // 取得欄位名稱
            $columns = array_keys($rows[0]);
            $columnList = '`' . implode('`, `', $columns) . '`';

            $sql .= "-- Data for $table\n";

            // 分批插入（每 100 筆一個 INSERT）
            $chunks = array_chunk($rows, 100);

            foreach ($chunks as $chunk) {
                $values = [];
                foreach ($chunk as $row) {
                    $rowValues = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $rowValues[] = 'NULL';
                        } else {
                            $rowValues[] = $pdo->quote($value);
                        }
                    }
                    $values[] = '(' . implode(', ', $rowValues) . ')';
                }
                $sql .= "INSERT INTO `$table` ($columnList) VALUES\n" . implode(",\n", $values) . ";\n";
            }
            $sql .= "\n";
        }
    }

    $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    $sql .= "\n-- Backup completed\n";

    // 記錄備份操作
    logInfo("Database backup downloaded by admin");

    // 設定下載標頭
    $filename = 'ocr_backup_' . date('Y-m-d_His') . '.sql';

    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($sql));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo $sql;
    exit;

} catch (PDOException $e) {
    logError("Database backup error: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '備份失敗：' . $e->getMessage()]);
}
