<?php
// 錯誤日誌模組（降級處理，InfinityFree 友好）

function logError($message)
{
    // 嘗試寫入檔案，失敗則靜默忽略（InfinityFree 可能禁止）
    try {
        $log_dir = __DIR__ . '/../tmp';

        // 嘗試建立目錄（若不存在）
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }

        $log_file = $log_dir . '/error.log';
        $log = date('Y-m-d H:i:s') . " - " . $message . PHP_EOL;

        // 使用 @ 抑制錯誤，避免影響主流程
        @file_put_contents($log_file, $log, FILE_APPEND);
    } catch (Exception $e) {
        // 靜默失敗，不影響主流程
    }
}

function logInfo($message)
{
    // 同樣的降級處理
    try {
        $log_dir = __DIR__ . '/../tmp';
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        $log_file = $log_dir . '/access.log';
        $log = date('Y-m-d H:i:s') . " - " . $message . PHP_EOL;
        @file_put_contents($log_file, $log, FILE_APPEND);
    } catch (Exception $e) {
        // 靜默失敗
    }
}
