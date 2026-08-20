<?php
// 資料庫連線模組
// 使用 PDO 連線 MySQL
// 連線設定統一由 config/secret.php 提供（不入 Git），本檔不含任何憑證

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
        // 讀取敏感設定（帳密只存在 config/secret.php，不入 Git）
        $secretFile = __DIR__ . '/../config/secret.php';
        if (!file_exists($secretFile)) {
            error_log('Database config file missing: config/secret.php');
            die('資料庫設定檔缺失，請聯繫管理員');
        }
        $dbConfig = require $secretFile;

        $host = $dbConfig['db_host'] ?? '';
        $name = $dbConfig['db_name'] ?? '';
        $user = $dbConfig['db_user'] ?? '';
        $pass = $dbConfig['db_pass'] ?? '';
        $charset = 'utf8mb4';

        try {
            $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=' . $charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, $user, $pass, $options);

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
