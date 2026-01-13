<?php
// 資料庫連線模組
// 使用 PDO 連線 MySQL

// 從安全設定檔讀取資料庫憑證
$secret = require __DIR__ . '/config/secret.php';
define('DB_HOST', $secret['db_host'] ?? 'localhost');
define('DB_NAME', $secret['db_name']);
define('DB_USER', $secret['db_user']);
define('DB_PASS', $secret['db_pass']);
define('DB_CHARSET', 'utf8mb4');

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
        } catch (PDOException $e) {
            // 記錄錯誤但不暴露詳細訊息
            error_log('Database connection failed: ' . $e->getMessage());
            die('資料庫連線失敗，請聯繫管理員');
        }
    }

    return $pdo;
}
