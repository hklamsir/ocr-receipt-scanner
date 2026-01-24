<?php
// 設定載入器 - 從 secret.php 和資料庫載入設定
// 資料庫設定優先於 secret.php

$secret_file = __DIR__ . '/../config/secret.php';

if (!file_exists($secret_file)) {
    die('錯誤：設定檔不存在。請複製 config/config.example.php 為 config/secret.php 並填入您的 API Key。');
}

$config = require $secret_file;

// 嘗試從資料庫載入設定（覆蓋 secret.php 的值）
try {
    require_once __DIR__ . '/db.php';
    $pdo = getDB();
    
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    $dbSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // 資料庫設定對應到 config key 的映射
    $keyMapping = [
        'deepseek_api_key' => 'deepseek_api_key',
        'ocrspace_api_key' => 'ocr_api_key',
        'ocr_engine' => 'ocr_engine',
        'max_files_per_upload' => 'max_files',
        'image_quality' => 'image_quality',
        'max_image_size_kb' => 'max_image_size_kb'
    ];
    
    foreach ($keyMapping as $dbKey => $configKey) {
        if (isset($dbSettings[$dbKey]) && $dbSettings[$dbKey] !== '') {
            $config[$configKey] = $dbSettings[$dbKey];
        }
    }
} catch (Exception $e) {
    // 資料庫尚未初始化或設定表不存在，使用 secret.php 的值
    error_log('Config: Could not load settings from database - ' . $e->getMessage());
}

// 定義全域常數
define('DEEPSEEK_API_KEY', $config['deepseek_api_key'] ?? '');
define('OCR_API_KEY', $config['ocr_api_key'] ?? 'K82976490788957');
define('OCR_ENGINE', $config['ocr_engine'] ?? '2');
define('MAX_FILES', $config['max_files'] ?? 20);
define('IMAGE_QUALITY', $config['image_quality'] ?? 60);
define('MAX_IMAGE_SIZE_KB', $config['max_image_size_kb'] ?? 200);

// 保留向後兼容（移除 Google Apps Script）
if (isset($config['google_apps_script_url'])) {
    define('GOOGLE_APPS_SCRIPT_URL', $config['google_apps_script_url']);
}
