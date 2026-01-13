<?php
// 設定載入器
$secret_file = __DIR__ . '/../config/secret.php';

if (!file_exists($secret_file)) {
    die('錯誤：設定檔不存在。請複製 config/config.example.php 為 config/secret.php 並填入您的 API Key。');
}

$config = require $secret_file;

// 定義全域常數
define('DEEPSEEK_API_KEY', $config['deepseek_api_key']);
define('GOOGLE_APPS_SCRIPT_URL', $config['google_apps_script_url']);
define('OCR_API_KEY', $config['ocr_api_key'] ?? 'K82976490788957'); // 預設使用免費 API Key
define('MAX_FILES', $config['max_files']);
