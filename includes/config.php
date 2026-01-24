<?php
// 設定載入器 - 從 secret.php 載入資料庫設定，從資料庫載入其他設定

$secret_file = __DIR__ . '/../config/secret.php';

if (!file_exists($secret_file)) {
    die('錯誤：設定檔不存在。請複製 config/config.example.php 為 config/secret.php 並填入資料庫連線資訊。');
}

$config = require $secret_file;

// 從資料庫載入設定
try {
    require_once __DIR__ . '/db.php';
    $pdo = getDB();

    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    $dbSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 合併資料庫設定
    $config = array_merge($config, $dbSettings);
} catch (Exception $e) {
    // 資料庫尚未初始化，使用預設值
    error_log('Config: Could not load settings from database - ' . $e->getMessage());
}

// 定義全域常數（從資料庫設定，若不存在則使用預設值）
define('DEEPSEEK_API_KEY', $config['deepseek_api_key'] ?? '');
define('OCR_API_KEY', $config['ocrspace_api_key'] ?? '');
define('OCR_ENGINE', $config['ocr_engine'] ?? '2');
define('MAX_FILES', (int) ($config['max_files_per_upload'] ?? 20));
define('IMAGE_QUALITY', (int) ($config['image_quality'] ?? 60));
define('MAX_IMAGE_SIZE_KB', (int) ($config['max_image_size_kb'] ?? 200));
