<?php
// 資料庫連線模組
// 使用 PDO 連線 MySQL

// 資料庫設定（請根據 InfinityFree 提供的資訊修改）
define('DB_HOST', 'sql112.infinityfree.com');
define('DB_NAME', 'if0_35608548_ds_ocr_receipts');  // 請修改
define('DB_USER', 'if0_35608548');  // 請修改
define('DB_PASS', 'LSTzd8bE15o');  // 請修改
define('DB_CHARSET', 'utf8mb4');

// 設定 PHP 時區
date_default_timezone_set('Asia/Hong_Kong');

/**
 * 取得資料庫連線
 * @return PDO
 */
function getDB()
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // 設定 MySQL session 時區為香港時間 (UTC+8)
            $pdo->exec("SET time_zone = '+08:00'");
        } catch (PDOException $e) {
            // 記錄錯誤但不暴露詳細訊息
            error_log('Database connection failed: ' . $e->getMessage());
            die('資料庫連線失敗，請聯繫管理員');
        }
    }

    return $pdo;
}
